<?php

/* Firebox - PHP Web Application Framework
   Copyright (C) 2007 Andrew J. Fabian
   Released under the GNU General Public License v3. See license.txt.
*/

function fbx_compile($filename)
{
	global $fbx, $content;
	
	$is_controller = substr($filename, 0, 10) == 'controller';
	$is_model      = substr($filename, 0, 5)  == 'model';

	// load the list of library functions and their source files
	
	if (!isset($fbx['libs']))
	{
		$fbx['libs'] = fbx_load_libs();
	}
	
	if (fbx_url_option('fbx_compiler_debug')) $content['debugpanes']['compiler'] .= "<br><br>Compiling $filename<br><br>";
	
	// if we've already compiled this file during this page request, quit immediately
	
	if (isset($fbx['compiled']) && false !== array_search($filename, $fbx['compiled'])) return;
	else $fbx['compiled'][] = $filename;

	// figure out what filename we're writing to
	
	$outputfile = $fbx['site_root'] . 'parsed/' . ($fbx['production'] ? 'prod' : 'dev') . '/' . str_replace("/", ".", $filename);

	// if that file exists and is up-to-date, return now
	
	$sourcefile = $fbx['site_root'] . $filename;

	if (file_exists($outputfile) && filemtime($sourcefile) <= filemtime($outputfile))
	{
		fbx_debug("Skipping compile of $filename because it's parsed copy is still current.", __FILE__, __LINE__);
		return;
	}

	fbx_debug("Compiling $filename", __FILE__, __LINE__);

	// read in the source file

	fbx_debug("Reading file", __FILE__, __LINE__);
	if (!file_exists($sourcefile)) fbx_error("Source file not found: $sourcefile");
	$input = file_get_contents($sourcefile);
		
	// turn the file into lexemes
	
	fbx_debug("Running lexical parser", __FILE__, __LINE__);
	$lex = fbx_lexical_parser($input);
		
	if (fbx_url_option('fbx_compiler_debug'))
	{
		$content['debugpanes']['compiler'] .= "FBX_Lexical_Parser<br>";
		$content['debugpanes']['compiler'] .= "CurrentState        Pos   Type                Content<br>";
		$content['debugpanes']['compiler'] .= "------------------- ----- ------------------- ----------------------------------<br>";
		foreach ($lex as $lexeme) $content['debugpanes']['compiler'] .= str_pad($lexeme['state'], 20, ' ', STR_PAD_RIGHT) 
										                             . str_pad($lexeme['pos'], 6, ' ', STR_PAD_RIGHT)
																  	 . str_pad($lexeme['type'], 20, ' ', STR_PAD_RIGHT)
																	 . str_replace("\n", "<br>", htmlentities(wordwrap(str_replace("\n", ' ', $lexeme['content']), 80, "\n" . str_repeat(' ', 46)))) . "<br>";
	}
	
	// build a list of code blocks, and where they start and end
	
	fbx_debug("Identifying code blocks", __FILE__, __LINE__);
	$blocks = fbx_get_blocks_from_lex($lex);
		
	if (fbx_url_option('fbx_compiler_debug'))
	{
		$content['debugpanes']['compiler'] .= "<br>FBX_Get_Blocks_From_Lex<br>";
		$content['debugpanes']['compiler'] .= "BlockType      Detail           Depth StartIndex,Pos EndIndex,Pos<br>";
		$content['debugpanes']['compiler'] .= "-------------- ---------------- ----- -------------- ---------------<br>";
		foreach ($blocks as $block) $content['debugpanes']['compiler'] .= str_pad($block['typeindex'] . ':' . $block['type'], 15, ' ', STR_PAD_RIGHT) 
																	   . @str_pad($block['nameindex'] . ':' . $block['name'], 20, ' ', STR_PAD_RIGHT)
																	   . str_pad($block['depth'], 3, ' ', STR_PAD_RIGHT)
																	   . str_pad($block['startindex'] . ':' . $block['start'], 15, ' ', STR_PAD_RIGHT)
																	   . str_pad($block['endindex'] . ':' . $block['end'], 15, ' ', STR_PAD_RIGHT) . "<br>";
	}
	
	// look for each time a function is called
	
	fbx_debug("Identifying function references", __FILE__, __LINE__);
	$refs = fbx_get_function_references_from_lex($lex);
	if (fbx_url_option('fbx_compiler_debug'))
	{
		$content['debugpanes']['compiler'] .= "<br>FBX_Get_Function_References_From_Lex<br>";
		$content['debugpanes']['compiler'] .= "Function            Pos<br>";
		$content['debugpanes']['compiler'] .= "------------------- -----<br>";
		foreach ($refs as $ref) $content['debugpanes']['compiler'] .= str_pad($ref['function'], 20, ' ', STR_PAD_RIGHT) 
									 							    . $ref['index'] . ':' . $ref['pos'] . "<br>";
	}
	
	// find the contents of our pre and post functions, if they exist; and where to cut to remove them from the output

	fbx_debug("Getting contents of pre() and post() functions, if they exist.", __FILE__, __LINE__);
		   
	$pre = '';
	$post = '';
	
	foreach ($blocks as $block)
	{
		if ($block['type'] == 'function' && $block['name'] == 'pre')
		{
			$pre = substr($input, $block['start']+1, $block['end'] - $block['start'] - 1);
			$prestartindex = $block['typeindex'];
			$preendindex = $block['endindex'];
		}
		if ($block['type'] == 'function' && $block['name'] == 'post')
		{
			$post = substr($input, $block['start']+1, $block['end'] - $block['start'] - 1);
			$poststartindex = $block['typeindex'];
			$postendindex = $block['endindex'];
		}
	}
	
	// translate names in our function definitions now, and insert our global variables and !function_exists() blocks.
	// if this is a model or view file, only translate function names matching the file name.  that way the file can still define
	// it's own functions for internal or site use.  also translate it's test.
	
	// first, the library requirements for pre- and post-functions, since they apply to all other functions
	
	$pre_post_requires = array();
	foreach ($blocks as $block)
	{
		if ($is_controller && $block['type'] == 'function' && ($block['name'] == 'pre' || $block['name'] == 'post'))
		{
			foreach($refs as $ref)
			{
				if ($ref['index'] > $block['startindex'] && $ref['index'] < $block['endindex'])
				{
					if (isset($fbx['libs'][$ref['function']]))
					{
						$pre_post_requires[$ref['function']] = "\trequire_once('{$fbx['libs'][$ref['function']]}');\n";
					}
				}
			}
		}
	}
	
	// now, the main iteration through all functions
	
	$functions = array();
	foreach ($blocks as $block)
	{
		if ($block['type'] == 'function' && $block['name'] != 'pre' && $block['name'] != 'post')
		{
			if ($is_controller || $block['name'] == substr(basename($filename), 0, -4) || $block['name'] == 'test_' . substr(basename($filename), 0, -4))
			{
				$newname = str_replace(array('.php','/','.'), '_', $filename) . $block['name'];
				if (substr($block['name'], 0, 5) == 'test_') $newname = 'test_' . str_replace(array('.php','/','.'), '_', $filename) . substr($block['name'], 5);
				$functions[$block['name']] = $newname;
				$requires = $pre_post_requires;
				foreach($refs as $ref)
				{
					if ($ref['index'] > $block['startindex'] && $ref['index'] < $block['endindex'])
					{
						if (isset($fbx['libs'][$ref['function']]))
						{
							$requires[$ref['function']] = "\trequire_once('{$fbx['libs'][$ref['function']]}');\n";
						}
					}
				}
				fbx_debug("Renaming function {$block['name']} to $newname", __FILE__, __LINE__);
				$lex[$block['typeindex']]['content'] = "if (!function_exists('$newname')) { function";
				$lex[$block['nameindex']]['content'] = $newname;
				$lex[$block['startindex']]['content'] = "{\n\tglobal \$fbx, \$content;\n" . join('', $requires) . "$pre\n";
				$lex[$block['endindex']]['content'] = "\n$post\n}}";
			}
			else 
			{
				fbx_debug("Not renaming function {$block['name']} because it's name doesn't match the file name and this isn't a controller.", __FILE__, __LINE__);
			}
		}
	}
	
	// translate names in function references

	foreach ($refs as $ref)
	{
		if (isset($functions[$ref['function']]))
		{
			fbx_debug("Updating function reference to {$ref['function']}", __FILE__, __LINE__);
			$lex[$ref['index']]['content'] = $functions[$ref['function']];
		}
	}
	
	// if the file doesn't define any functions, it still needs the firebox global variables
	
	if (!count($functions))
	{
		array_unshift($lex, array('state' => 'php', 'type' => 'o_php1', 'content' => '<?php global $fbx, $content; ?>', 'pos' => 0));
	}
	
	// build out output by combining all of the modified lexemes back together.
	// o_php2 ('<?') is upgraded to '<?php' so compiled output never relies on short_open_tag.
	// o_php_echo ('<?=') is preserved as-is — it's always enabled regardless of short_open_tag.

	fbx_debug("Building and writing output", __FILE__, __LINE__);
	$output = '';
	for ($i=0; $i<count($lex); $i++)
	{
		if ( !($pre && $i >= $prestartindex && $i<=$preendindex) && !($post && $i >= $poststartindex && $i <= $postendindex) )
		{
			$output .= ($lex[$i]['type'] == 'o_php2') ? '<?php' : $lex[$i]['content'];
		}
	}
		
	// compile the tree back into a string, and write out compiled file
	
	$fh = fopen($outputfile, 'w') or fbx_error("Couldn't open $outputfile for writing");
	fbx_debug("Writing out $outputfile", __FILE__, __LINE__);
	fputs($fh, $output);
	fclose($fh);
	touch($outputfile, filemtime($sourcefile));
	
	if (!empty($content['debugpanes']['compiler']))
	{	
		$content['debugpanes']['compiler'] = str_replace(' ', '&nbsp;', $content['debugpanes']['compiler']);
		if (fbx_url_option('fbx_debug_to_screen'))
		{
			echo $content['debugpanes']['compiler'];
			$content['debugpanes']['compiler'] = '';
		}
	}
}

// this turns a string into a series of lexemes

function fbx_lexical_parser($input)
{
	global $fbx;
	
	// load patterns for lexical analyzer
	
	require_once($fbx['fbx_root'] . 'firebox_compiler_data.php');

	// this holds what kind of block we're currently in

	$stack = array('html');
	
	// this is the position our current block starts at
	
	$left = 0;
		
	do
	{
		// shortcut to top of stack
	
		$state = $stack[count($stack)-1];
		
		// find the next point of interest	
		if (!count($fbx['lex'][$state])) fbx_error("Compiler error: no data for state $state at offset $left.");	
		$right = fbx_lexical_parser_next_lexeme($input, ($fbx['lex'][$state]), $left, $match, $contents);

		if ($right !== false)
		{
			
			$lex[] = array('state' => $state, 'type' => $match, 'content' => $contents, 'pos' => $right);
			
			// figure out what state we're in now
			
			if (is_array($fbx['lex'][$state][$match]) && $fbx['lex'][$state][$match][1] == 'POP')
			{
				array_pop($stack);
			}
			
			else if (is_array($fbx['lex'][$state][$match]) && $fbx['lex'][$state][$match][1] == 'POP2')
			{
				array_pop($stack); array_pop($stack);
			}
			
			else if (is_array($fbx['lex'][$state][$match]) && $fbx['lex'][$state][$match][1])
			{
				array_push($stack, $fbx['lex'][$state][$match][1]);
			}
			
			if ($match == 'heredocident')
			{
				$fbx['lex']['heredoc']['text'] = array('/((.|\n)+?)\n' . $contents . '/', false, 1);
				$fbx['lex']['heredoc']['endofdoc'] = array('/\n(' . $contents . ')/', 'POP2', 1);
			}
			
			// get ready to start again
			
			$left = $right + strlen($contents);
		}
			
	} while ($right !== false);

	return($lex);
}

// this function returns the position of the first match from an array of search strings.  It also sets an variable with the matching string.

function fbx_lexical_parser_next_lexeme($haystack, $needles, $offset, &$match, &$lexcontent)
{
	$bestpos = false;
	$bestmatch = false;
	$bestcontent = false;

	foreach ($needles as $name => $data)
	{
		if (is_array($data)) 
		{
			$pattern = $data[0];
		}
		else 
		{
			$pattern = $data;
		}
				
		if ($pattern[0]=='/')
		{
			$mypos = preg_match($pattern, $haystack, $mycontent, 0, $offset);
			if ($mypos)
			{
			    if (is_array($data)) {
                    $mycontent = $mycontent[count($data) == 3 ? $data[2] : 0];
                }
                else {
                    $mycontent = $mycontent[0];
                }
				$mypos = strpos($haystack, $mycontent, $offset);
			}
			else 
			{
				$mypos = false;
			}
		}
		else
		{ 
			$mypos = strpos($haystack, $pattern, $offset);
			if ($mypos !== false) $mycontent = substr($haystack, $mypos, strlen($pattern));
		}
		
		if ($mypos !== false)
		{
			if ($mypos < $bestpos || $bestpos === false)
			{
				$bestpos = $mypos;
				$bestmatch = $name;
				$bestcontent = $mycontent;
			}
		}
	}

	
	$match = $bestmatch;
	$lexcontent = $bestcontent;
	return($bestpos);
}

// this builds a table of code blocks {.....}; where they start and end, 
// what type they are, what their nesting depth is, and their character positions

function fbx_get_blocks_from_lex($lex)
{
	$blocks = array();
	$counter = 0;
	$depth = 0;
	$word = '';
	$wordindex = 0;

	$block_identifiers = array('if', 'else', 'while', 'do', 'for', 'foreach', 'function', 'switch', 'try', 'catch');
	
	foreach ($lex as $lexeme)
	{
		if ($lexeme['state'] == 'php' && $lexeme['type'] == 'o_curly')
		{
			// reset before each scan — prevents stale values from a previous iteration
			// being used when the backward scan finds no matching keyword
			$type = null;
			$typeindex = null;
			$name = null;
			$nameindex = null;

			$backdepth = 0;
			for ($i=$counter; $i>=0; $i--)
			{
				if ($lex[$i]['state'] == 'php')
				{
					if ($lex[$i]['type'] == 'o_paren' || $lex[$i]['type'] == 'o_bracket')
					{
						$backdepth++;
					}
					if ($lex[$i]['type'] == 'c_paren' || $lex[$i]['type'] == 'c_bracket')
					{
						$backdepth--;
					}
					if ($lex[$i]['type'] == 'word' && $backdepth == 0)
					{
						$lastword = $word;
						$lastwordindex = $wordindex;
						$wordindex = $i;
						$word = $lex[$i]['content'];
						if (false !== array_search($word, $block_identifiers))
						{
							$type = $word;
							$typeindex = $i;
							if ($type == 'function')
							{
								$name = $lastword;
								$nameindex = $lastwordindex;
							}
							break;
						}
					}
				}
			}

			$blocks[] = array('start' => $lexeme['pos'], 'startindex' => $counter, 'type' => $type, 'typeindex' => $typeindex, 'name' => ($type == 'function' ? $name : null), 'nameindex' => ($type == 'function' ? $nameindex : null), 'depth' => $depth, 'end' => false, 'endindex' => false);
			$depth++;
		}
		
		if ($lexeme['state'] == 'php' && $lexeme['type'] == 'c_curly')
		{
			$depth--;
			for ($i=count($blocks)-1; $i>=0; $i--)
			{
				if ($blocks[$i]['depth'] == $depth)
				{
					$blocks[$i]['end'] = $lexeme['pos'];
					$blocks[$i]['endindex'] = $counter;
					break;
				}
			}
		}		
		$counter++;
	}
	return($blocks);
}

// given an array of lexemes, this ruturns a list of all of the function references contained within,
// and their index in the array and character position

function fbx_get_function_references_from_lex($lex)
{
	$refs = array();
	$block_identifiers = array('if', 'else', 'while', 'do', 'for', 'foreach', 'function', 'switch', 'case', 'break');
	
	for ($i=0; $i<count($lex); $i++)
	{
		if ($lex[$i]['state'] == 'php' && $lex[$i]['type'] == 'word')
		{
			if ( !($lex[$i-1]['state'] == 'php' && $lex[$i-1]['type'] == 'dollar') &&
			     !($lex[$i-1]['state'] == 'php' && $lex[$i-1]['type'] == 'space' && $lex[$i-2]['state'] == 'php' && $lex[$i-2]['type'] == 'dollar') &&
			     (false === array_search($lex[$i]['content'], $block_identifiers)) &&
			     !($lex[$i-1]['state'] == 'php' && $lex[$i-1]['type'] == 'space' && $lex[$i-2]['state'] == 'php' && $lex[$i-2]['content'] == 'function')
			   )
			{
			   	$refs[] = array('function' => $lex[$i]['content'], 'index' => $i, 'pos' => $lex[$i]['pos']);
			}
		}
	}

	return($refs);
}

// this scans the libs directory, figures out which functions are defined in which files
// it then "compiles" then into parsed, by updating the paths in include and require

function fbx_load_libs()
{
	global $fbx;

	$libs = array();

	if (fbx_url_option('fbx_compiler_debug')) $content['debugpanes']['compiler'] .= "Scanning for updated libraries<br>";

	$files = fbx_dir_tree_files($fbx['site_root'] . 'lib/', '/.*\.php$/');
	foreach ($files as $lib)
	{
		// fbx_get_function_names handles its own mtime-based caching
		$libs = array_merge($libs, fbx_get_function_names($lib));
	}

	return($libs);
}

// this function compiles libraries, writes out the parsed file, and returns a list of functions defined, pointing to that parsed file

function fbx_get_function_names($filename)
{
	global $fbx;
	
	// figure out what filename we're writing to
	
	$outputfile = $fbx['site_root'] . 'parsed/' . ($fbx['production'] ? 'prod' : 'dev') . '/' . str_replace("/", ".", substr($filename, strlen($fbx['site_root'])));
	
	// if that file exists and is up-to-date, run it and return the results immediately
	
	if (file_exists($outputfile) && filemtime($filename) <= filemtime($outputfile))
	{
		fbx_debug("Skipping compile of $filename because it's parsed copy is still current.", __FILE__, __LINE__);
		return(require($outputfile));
	}

	fbx_debug("Compiling library $filename", __FILE__, __LINE__);

	// read in the source file
	
	fbx_debug("Reading file", __FILE__, __LINE__);
	$input = file_get_contents($filename);

	// turn the file into lexemes

	fbx_debug("Running lexical parser", __FILE__, __LINE__);
	$lex = fbx_lexical_parser($input);
		
	if (fbx_url_option('fbx_compiler_debug'))
	{
		$content['debugpanes']['compiler'] .= "FBX_Lexical_Parser<br>";
		$content['debugpanes']['compiler'] .= "CurrentState        Pos   Type                Content<br>";
		$content['debugpanes']['compiler'] .= "------------------- ----- ------------------- ----------------------------------<br>";
		foreach ($lex as $lexeme) $content['debugpanes']['compiler'] .= str_pad($lexeme['state'], 20, ' ', STR_PAD_RIGHT) 
										                             . str_pad($lexeme['pos'], 6, ' ', STR_PAD_RIGHT)
																  	 . str_pad($lexeme['type'], 20, ' ', STR_PAD_RIGHT)
																	 . str_replace("\n", "<br>", htmlentities(wordwrap(str_replace("\n", ' ', $lexeme['content']), 80, "\n" . str_repeat(' ', 46)))) . "<br>";
	}

	// build a list of code blocks, and where they start and end
	
	fbx_debug("Identifying code blocks", __FILE__, __LINE__);
	$blocks = fbx_get_blocks_from_lex($lex);
		
	if (fbx_url_option('fbx_compiler_debug'))
	{
		$content['debugpanes']['compiler'] .= "<br>FBX_Get_Blocks_From_Lex<br>";
		$content['debugpanes']['compiler'] .= "BlockType      Detail           Depth StartIndex,Pos EndIndex,Pos<br>";
		$content['debugpanes']['compiler'] .= "-------------- ---------------- ----- -------------- ---------------<br>";
		foreach ($blocks as $block) $content['debugpanes']['compiler'] .= str_pad($block['typeindex'] . ':' . $block['type'], 15, ' ', STR_PAD_RIGHT) 
																	   . @str_pad($block['nameindex'] . ':' . $block['name'], 20, ' ', STR_PAD_RIGHT)
																	   . str_pad($block['depth'], 3, ' ', STR_PAD_RIGHT)
																	   . str_pad($block['startindex'] . ':' . $block['start'], 15, ' ', STR_PAD_RIGHT)
																	   . str_pad($block['endindex'] . ':' . $block['end'], 15, ' ', STR_PAD_RIGHT) . "<br>";
	}
	
	// identify all of the function definitions
	$functions = array();
	foreach ($blocks as $block)
	{
		if ($block['type'] == 'function')
		{
		 	$functions[$block['name']] = $filename;
		}
	}
	
	// write out the parsed file
	
	$fh = fopen($outputfile, 'w') or die("Couldn't open output file");
	fputs($fh, '<?php return(' . var_export($functions, true) . ');');
	fclose($fh);
	
	// return our array of functions
	
	return(require($outputfile));
		
}

// this generates an array of all the files under a directory

function fbx_dir_tree_files($dir, $match_regex='')
{
	 $files = array();
	 if (!is_dir($dir)) return($files);
	 $dh = opendir($dir) or fbx_error("Couldn't open directory: $dir");
	 while ($rec = readdir($dh))
	 {
	 	if ($rec !== false && $rec != '.' && $rec != '..')
	 	{
	 		if (is_dir($dir . $rec))
	 		{
	 			$files = array_merge($files, fbx_dir_tree_files($dir . $rec . '/', $match_regex));
	 		}
	 		elseif (is_file($dir . $rec))
	 		{
				if (empty($match_regex) || preg_match($match_regex, $rec))
				{
	 				$files[] = $dir . $rec;
				}
	 		}
	 	}
	 }
	 closedir($dh);
	 return($files);
}
