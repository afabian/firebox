<?php

/* Firebox - PHP Web Application Framework
   Copyright (C) 2007 Andrew J. Fabian
   
   Andrew J. Fabian can be reached via email at afabian@anjero.com, or at this address:
   209 S. Knollwood Dr.
   Suite 1301
   Blacksburg, VA 24060

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function display($filename)
{
	$args = func_get_args(); array_unshift($args, false); array_unshift($args, 'view'); array_unshift($args, 'display');
	return(call_user_func_array('fbx_run_file', $args));
}

function layout($filename)
{
	$args = func_get_args(); array_unshift($args, false); array_unshift($args, 'layout'); array_unshift($args, 'layout');
	return(call_user_func_array('fbx_run_file', $args));
}

function action_once($filename)
{
	$args = func_get_args(); array_unshift($args, true); array_unshift($args, 'model'); array_unshift($args, 'action');
	return(call_user_func_array('fbx_run_file', $args));
}

function action($filename)
{
	$args = func_get_args(); array_unshift($args, false); array_unshift($args, 'model'); array_unshift($args, 'action');
	return(call_user_func_array('fbx_run_file', $args));
}

function query($filename)
{
	$args = func_get_args(); array_unshift($args, false); array_unshift($args, 'model'); array_unshift($args, 'query');
	return(call_user_func_array('fbx_run_file', $args));
}

function relocate($url)
{
	fbx_execute_plugins('prerelocate', $url);
	if (headers_sent()) echo "\n<script>\ndocument.location.href=" . json_encode($url, JSON_HEX_TAG) . ";\n</script>\n";
	else header("Location: $url");
	exit();
}

function setlink($name, $action)
{
	$target = fbx_execute_plugins('setlink', $action);	
	if ($target == null)
	{
		$target = $GLOBALS['myself'] . (!strpos($action, '.') ? $GLOBALS['fbx']['call_stack'][count($GLOBALS['fbx']['call_stack'])-1]['filename'] . '.' . $action : $action);
	}
	fbx_debug("Storing link for $name to $target", __FILE__, __LINE__);
	$GLOBALS['fbx']['links'][$name] = $target;
}

function linkto($name)
{
	if (!isset($GLOBALS['fbx']['links'][$name])) return(false);
	return($GLOBALS['fbx']['links'][$name]);
}

function linkaction($name)
{
	if (!isset($GLOBALS['fbx']['links'][$name])) return(false);
	preg_match('|go=([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)|', $GLOBALS['fbx']['links'][$name], $matches);
	return($matches[1]);
}

function control($action)
{
	global $fbx;
	
	// figure out the filename and function name we're compiling
	
	if (strpos($action, '.'))
	{
		list($filename, $method) = explode('.', $action, 2);
	}
	else 
	{
		$filename = $fbx['call_stack'][count($fbx['call_stack'])-1]['filename'];
		$method = $action;
	}
	$prefix = $fbx['production'] ? 'prod' : 'dev';
	$is_plugin = substr($action, 0, 7) == 'plugin.';
	
	// update our debug displays
	
	$fbx['debug_indent']++;
	$fbx['call_stack'][] = array('filename' => $filename, 'method' => $method);
	
	// run precontrol plugins
	
	fbx_execute_plugins('precontrol', $action);
	
	// decide if we need to compile this function, and do so if necessary
	
	if (!$is_plugin)
	{
		if (!file_exists($fbx['site_root'] . 'controller/' . $filename . '.php')) fbx_error('Controller file ' . $filename . ' doesn\'t exist.');	
		if (!$fbx['production'] || !file_exists($fbx['site_root'] . "parsed/$prefix/$action.php")) 
		{
			require_once($fbx['fbx_root'] . 'firebox_compiler.php');
			fbx_compile("controller/$filename.php");
		}
		
		// load our compiled file, to define our controller function and any test functions
		
		fbx_debug("Running controller action $action from file controller/$filename.php as parsed/$prefix/controller." . $filename . '.php', __FILE__, __LINE__);
		require_once($fbx['site_root'] . "parsed/$prefix/controller.$filename.php");
	}
	
	// run unit/functional tests if available and we want to
	
	if (!$is_plugin && !$fbx['production'] && !fbx_url_option('fbx_skip_tests'))
	{
		if (!isset($fbx['control_tests_run']) || false === @array_search($action, $fbx['control_tests_run']))
		{
			$fbx['debug_indent']++;
			$fbx['control_tests_run'][] = $action;
			$functionname = 'test_controller_' . str_replace(array('.php','/','.'), '_', $action);
			fbx_debug("Running tests for controller in function $functionname", __FILE__, __LINE__);
			if (!function_exists($functionname)) fbx_debug('Warning: no tests exist.', __FILE__, __LINE__);
			elseif (!$functionname()) fbx_error("Tests failed for $action", __FILE__, __LINE__);
			$fbx['debug_indent']--;
		}
	}
	
	// finally run the controller function
	
	if ($is_plugin)
	{
		$functionname = $method;	
	}
	else 
	{
		$functionname = 'controller_' . str_replace(array('.php','/','.'), '_', $filename) . '_' . $method;
	}
	fbx_debug("Compiled controller function is $functionname", __FILE__, __LINE__);
	if (!function_exists($functionname)) fbx_error('Controller function ' . $method . ', compiled as ' . $functionname . ', doesn\'t exist in file ' . $filename . '.');
	$function_args = func_get_args(); array_shift($function_args);
	$output = @call_user_func_array($functionname, $function_args);
	
	// run postcontrol plugins
	
	fbx_execute_plugins('postcontrol', $action);
	
	// clean up and return our controller's output
	$fbx['debug_indent']--;
	array_pop($fbx['call_stack']);
	return($output);
}

function fbx_run_file($type, $path, $only_once, $filename)
{
	global $fbx;

	// if only_once and it's already been run, return
	
	if ($only_once && isset($fbx['files_run']) && (array_search($path.$filename, $fbx['files_run']) !== false))
	{
		fbx_debug("Skipping repeat of $path.$filename", __FILE__, __LINE__);
		return;
	}
	else 
	{
		$fbx['files_run'][] = $path.$filename;
	}
	
	$fbx['debug_indent']++;
		
	// run pre[this file type] plugins
	
	fbx_execute_plugins("pre$type", $filename);
	
	// figure out the filename and function we're compiling
	
	if (strpos($filename, '.'))
	{
		list($controller, $filename) = explode('.', $filename, 2);
	}
	else 
	{
		$controller = $fbx['call_stack'][count($fbx['call_stack'])-1]['filename'];
	}
	
	$fbx['call_stack'][] = array('filename' => $controller, 'method' => $filename);

	$prefix = $fbx['production'] ? 'prod' : 'dev';
	fbx_debug("Running $type file $path/$controller/$filename.php", __FILE__, __LINE__);
	if (!file_exists($GLOBALS['fbx']['site_root'] . $path . '/' . $controller . '/' . $filename . '.php')) fbx_error("Can't find $type file $path/$controller/$filename.php");
	$outputfile = $fbx['site_root'] . "parsed/$prefix/$path.$controller." . str_replace("/", ".", $filename) . ".php";
	
	// compile the file if we need to do so
	
	if (!$fbx['production'] || !file_exists($outputfile)) 
	{
		require_once($fbx['fbx_root'] . 'firebox_compiler.php');
		fbx_compile($path . '/' . $controller . '/' . $filename . '.php');
	}
	
	// model and view files can be run a few different ways, depending on if they define a function,
	// and if that function produces output or not.  here's our first try: run the file.  it will
	// either produce output we can use, or it'll define a function we can use.
	
	$output = fbx_require_output($outputfile);
	
	// if the file defined any test functions, and we're in development mode, run them now
	
	if (!$fbx['production'] && !fbx_url_option('fbx_skip_tests'))
	{
		if (!isset($fbx['mv_tests_run']) || false === array_search($filename, $fbx['mv_tests_run']))
		{
			$fbx['mv_tests_run'][] = $filename;
			$functionname = 'test_' . str_replace(array('.php','/','.'), '_', $path . '_' . $controller . '_' . $filename. '_' . $filename);
			fbx_debug("Running tests for file in function $functionname", __FILE__, __LINE__);
			$fbx['debug_indent']++;
			if (!function_exists($functionname)) fbx_debug('Warning: no tests exist.', __FILE__, __LINE__);
			elseif (!$functionname()) fbx_error("Tests failed for $functionname", __FILE__, __LINE__);
			$fbx['debug_indent']--;
		}
	}
	
	// if the file defined a main function, run that now and capture both it's return value and its
	// direct output.  Use the return value if it exists, otherwise its output
	
	$functionname = str_replace(array('.php','/','.'), '_', $path . '_' . $controller . '_' . $filename . '_' . $filename);
	if (function_exists($functionname)) 
	{
		fbx_debug("File defined a function.  Running that function with provided arguements.", __FILE__, __LINE__);
		$function_args = func_get_args(); array_shift($function_args); array_shift($function_args); array_shift($function_args); array_shift($function_args);
		ob_start();
		$output = call_user_func_array($functionname, $function_args);
		if ($path != 'model' && !$output)
		{
			fbx_debug("View/Layout function didn't return anything.  Using output buffer instead.", __FILE__, __LINE__);
			$output = ob_get_contents();
			if (empty($output)) fbx_debug("Warning: Output buffer is empty", __FILE__, __LINE__);
		}
		ob_end_clean();
	}

	// run post[this file type] plugins
	
	fbx_execute_plugins("post$type", $filename);
	
	// clean up and return our output
	
	$fbx['debug_indent']--;
	
	array_pop($fbx['call_stack']);

	return($output);
}
