<?php

/* Firebox - PHP Web Application Framework
   Copyright (C) 2007 Andrew J. Fabian
   Released under the GNU General Public License v3. See license.txt.
*/

// Public API available inside all controller, model, view, and layout files.
// Also contains control(), fbx_run_file(), and fbx_relative_path() which are
// framework internals called by firebox_runtime.php.

// ---- Public API -----------------------------------------------------------

// Render view/$controller/$filename.php and return its HTML.
function display($filename)
{
	$args = func_get_args(); array_unshift($args, false); array_unshift($args, 'view'); array_unshift($args, 'display');
	return(call_user_func_array('fbx_run_file', $args));
}

// Render layout/$controller/$filename.php and return its HTML.
function layout($filename)
{
	$args = func_get_args(); array_unshift($args, false); array_unshift($args, 'layout'); array_unshift($args, 'layout');
	return(call_user_func_array('fbx_run_file', $args));
}

// Execute model/$controller/$filename.php at most once per request. Returns its value.
function action_once($filename)
{
	$args = func_get_args(); array_unshift($args, true); array_unshift($args, 'model'); array_unshift($args, 'action');
	return(call_user_func_array('fbx_run_file', $args));
}

// Execute model/$controller/$filename.php and return its value.
function action($filename)
{
	$args = func_get_args(); array_unshift($args, false); array_unshift($args, 'model'); array_unshift($args, 'action');
	return(call_user_func_array('fbx_run_file', $args));
}

// Execute model/$controller/$filename.php and return its value. Alias for action().
function query($filename)
{
	$args = func_get_args(); array_unshift($args, false); array_unshift($args, 'model'); array_unshift($args, 'query');
	return(call_user_func_array('fbx_run_file', $args));
}

// Redirect to $url and exit. Falls back to a JS redirect if headers already sent.
function relocate($url)
{
	fbx_execute_plugins('prerelocate', $url);
	if (headers_sent()) echo "\n<script>\ndocument.location.href=" . json_encode($url, JSON_HEX_TAG) . ";\n</script>\n";
	else header("Location: $url");
	exit();
}

// Store a named link pointing to $action. $action can be 'controller.method'
// (absolute) or just 'method' (relative — current controller is prepended).
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

// Return the full URL stored by setlink($name), or false if not found.
function linkto($name)
{
	if (!isset($GLOBALS['fbx']['links'][$name])) return(false);
	return($GLOBALS['fbx']['links'][$name]);
}

// Return the 'controller.method' action string from a stored link, or false if not found.
function linkaction($name)
{
	if (!isset($GLOBALS['fbx']['links'][$name])) return(false);
	preg_match('|go=([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)|', $GLOBALS['fbx']['links'][$name], $matches);
	return($matches[1]);
}

// ---- Framework internals --------------------------------------------------

// Execute a controller action. $action is 'controller.method' or just 'method'
// (relative to the currently-executing controller). Extra arguments are passed
// through to the compiled controller function. Returns the function's output.
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
	$output = call_user_func_array($functionname, $function_args);

	// run postcontrol plugins

	fbx_execute_plugins('postcontrol', $action);

	// clean up and return our controller's output
	$fbx['debug_indent']--;
	array_pop($fbx['call_stack']);
	return($output);
}

// Execute a compiled model, view, or layout file and return its output.
// $type:      'action', 'query', 'display', or 'layout' (used for plugin phase names)
// $path:      directory prefix: 'model', 'view', or 'layout'
// $only_once: if true, skip execution if this file has already run this request
// $filename:  bare filename (no extension) or 'controller.filename' for cross-controller calls
// Extra arguments beyond $filename are passed to the file's main function if it defines one.
//
// Return value priority:
//   1. The file's main function return value (if it defines one and returns non-null)
//   2. The file's echo output captured via output buffer (views/layouts only)
//   3. null for models that return nothing
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

// Computes the relative path from $from_dir to $to_dir. Both must be absolute.
// Used by admin/create_skeleton.php and admin/create_todo.php to build index.php.
function fbx_relative_path($from_dir, $to_dir)
{
	if (!(isset($_SERVER['WINDIR']) && $from_dir[1] == ':' && $to_dir[1] == ':') && !(empty($_SERVER['WINDIR']) && $from_dir[0] == '/' && $to_dir[0] == '/'))
		fbx_error("Non-absolute path given to fbx_relative_path: $from_dir, $to_dir");

	$to_array   = explode('/', str_replace('\\', '/', $to_dir));
	$from_array = explode('/', str_replace('\\', '/', $from_dir));

	if (empty($to_array[count($to_array)-1]))     array_pop($to_array);
	if (empty($from_array[count($from_array)-1])) array_pop($from_array);

	$path  = '';
	$stack = array();

	while (count($from_array) > count($to_array)) { $path .= '../'; array_pop($from_array); }
	while (count($to_array) > count($from_array))   $stack[] = array_pop($to_array);

	if (isset($_SERVER['WINDIR']))
	{
		for ($i = 0; $i < count($to_array);   $i++) $to_array[$i]   = strtolower($to_array[$i]);
		for ($i = 0; $i < count($from_array); $i++) $from_array[$i] = strtolower($from_array[$i]);
	}

	while ($to_array != $from_array)
	{
		$path   .= '../';
		$stack[] = array_pop($to_array);
		array_pop($from_array);
	}

	return $path . implode('/', $stack);
}
