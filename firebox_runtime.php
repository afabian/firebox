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

// this needs to be defined early now.  working on a fix...

if (!function_exists('fbx_plugin_register')) 
{
	function fbx_plugin_register($phase, $plugin)
	{
		$GLOBALS['fbx']['plugins'][$phase][] = $plugin;
	}
}

// regular start of file

global $fbx, $content, $myself;

// temporary value for these until they're loaded from settings

$fbx['production'] = false;

// start Firebox

$fbx['version'] = '0.6';

fbx_debug("Firebox {$fbx['version']} by Andrew Fabian", __FILE__, __LINE__);
fbx_debug('Request: ' . @$_SERVER['REQUEST_URI'], __FILE__, __LINE__);

// set up our paths

$fbx['fbx_root'] = dirname(__FILE__) . '/';
$fbx['site_root'] = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
fbx_debug('Firebox Root: ' . $fbx['fbx_root'], __FILE__, __LINE__);
fbx_debug('Site Root: ' . $fbx['site_root'], __FILE__, __LINE__);
$myself = $_SERVER['SCRIPT_NAME'] . '?go=';
fbx_debug('$myself: ' . $myself, __FILE__, __LINE__);

// load other parts of firebox

require($fbx['fbx_root'] . 'firebox_controller_functions.php');

// last-resort error handlers

set_error_handler('fbx_error_handler', E_ALL);
function fbx_error_handler($errno, $errstr, $errfile, $errline) {
	if (!(error_reporting() & $errno)) return; // honour @ suppression (PHP 8: clears bit, not sets to 0)
	if ($errno == 8) return;
	if (strpos($errstr, 'Cannot send session cache limiter')) return;
	while (ob_get_level()) ob_end_flush();
	ob_start();
	debug_print_backtrace();
	$output = str_replace(array("#", ",["), array("<br>#", "<br>&nbsp;&nbsp;["), ob_get_contents());
	ob_end_clean();
	die("<br>Handled Error at $errfile:$errline: $errno $errstr<br><pre>$output");
}

register_shutdown_function('fbx_error_shutdown');
function fbx_error_shutdown() {
	$e = error_get_last();
	$fatal = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
	if (!is_null($e) && in_array($e['type'], $fatal)) {
		while (ob_get_level()) ob_end_clean();
		die("<br>Fatal Error at {$e['file']}:{$e['line']}: {$e['message']}");
	}
}

// load our settings

fbx_debug('Loading settings', __FILE__, __LINE__);
$_fbx_settings_file = $fbx['site_root'] . 'settings.php';
if (!file_exists($_fbx_settings_file)) die('Firebox: settings.php not found in ' . $fbx['site_root']);
try { include($_fbx_settings_file); } catch (ParseError $e) { die('Firebox: parse error in settings.php: ' . $e->getMessage()); }
unset($_fbx_settings_file);
if (isset($fbx['settings']) && count($fbx['settings'])) foreach($fbx['settings'] as $setting => $value) fbx_debug("$setting = " . var_export($value, true), __FILE__, __LINE__);
$fbx['production'] = file_exists($fbx['site_root'] . 'parsed/production');
if (fbx_url_option('fbx_develop')) $fbx['production'] = false;
fbx_debug('Mode: ' . ($fbx['production'] ? 'Production' : 'Development'), __FILE__, __LINE__);

// register plugins

if ($fbx['production'])
{
	foreach ($fbx['settings']['production_plugins'] ?? [] as $plugin)
	{
		fbx_debug("Loading production plugin $plugin", __FILE__, __LINE__);
		require($fbx['site_root'] . 'plugins/' . $plugin . '.php');
	}
}
else
{
	foreach ($fbx['settings']['development_plugins'] ?? [] as $plugin)
	{
		fbx_debug("Loading development plugin $plugin", __FILE__, __LINE__);
		require($fbx['site_root'] . 'plugins/' . $plugin . '.php');
	}
}

// run prerequest plugins

fbx_execute_plugins('prerequest');

// figure out what action we're talking

$fbx['action'] = $_REQUEST['go'] ?? '';
if (!$fbx['action']) $fbx['action'] = isset($fbx['settings']['default_action']) ? $fbx['settings']['default_action'] : 'fbx.welcome';
fbx_debug("Chose Action: {$fbx['action']}", __FILE__, __LINE__);
list($fbx['controller'], $fbx['method']) = explode('.', $fbx['action']);

// run preexec plugins

fbx_execute_plugins('preexec');

// run preexec actions

foreach ($fbx['settings']['pre'] ?? [] as $action)
{
	fbx_debug("Running Global PreAction $action via control()", __FILE__, __LINE__);
	echo control($action);
}

// run the action

if (substr($fbx['action'], 0, 4) == 'fbx.')
{
	fbx_debug("Running FBX Action from admin/" . substr($fbx['action'], 4) . '.php', __FILE__, __LINE__);
	$content['body'] = fbx_require_output($fbx['fbx_root'] . 'admin/' . substr($fbx['action'], 4) . '.php');
	if (!$content['body']) fbx_error('FBX Action not found');
	require($fbx['fbx_root'] . 'skeleton/lay_html.php');
}

elseif (substr($fbx['action'], 0, 7) == 'plugin.')
{
	fbx_debug("Running Plugin Action via control()", __FILE__, __LINE__);
	$content['body'] = control($fbx['action']);
	if (!$content['body']) fbx_error('FBX Plugin Action not found');
	require($fbx['fbx_root'] . 'skeleton/lay_html.php');	
}

else 
{
	fbx_debug("Running Action via control()", __FILE__, __LINE__);
	if (!isset($fbx['action_parameters']) || !is_array($fbx['action_parameters'])) $fbx['action_parameters'] = array();
	array_unshift($fbx['action_parameters'], $fbx['action']);
	$content['html'] = call_user_func_array('control', $fbx['action_parameters']);
        fbx_execute_plugins('posthtml');
        echo $content['html'];
}

// run postexec actions

foreach ($fbx['settings']['post'] ?? [] as $action)
{
	fbx_debug("Running Global PostAction $action via control()", __FILE__, __LINE__);
	echo control($action);
}

// run postexec plugins

fbx_execute_plugins('postexec');

// done!

return;

// helper functions

function fbx_require_output($filename)
{
	if (!file_exists($filename)) return(false);
	ob_start();
	global $fbx, $xfa, $content, $myself;
	require($filename);
	$output = ob_get_contents();
	ob_end_clean();
	return($output);
}

function fbx_error($message)
{
	fbx_debug("Dying with error message: $message", __FILE__, __LINE__);
	global $fbx, $content;
	$fbx['error_message'] = $message;
	fbx_execute_plugins('error', $message);
	$content['body'] = fbx_require_output($fbx['fbx_root'] . 'admin/error.php');
	require($fbx['fbx_root'] . 'skeleton/lay_html.php');
	die();
}

function fbx_debug($message, $filename, $line)
{
    if (!isset($GLOBALS['fbx']['debug_indent'])) $GLOBALS['fbx']['debug_indent'] = 0;
	$message = str_repeat(' ', $GLOBALS['fbx']['debug_indent'] * 2) . $message;
	if (fbx_url_option('fbx_debug_to_screen')) { 
		if (isset($_SERVER['REMOTE_ADDR'])) {
			echo $message . str_repeat(' ', 4096) . '<br>'; flush(); 
		} else {
			echo $message . "\n"; 
		}
	}
	$GLOBALS['fbx']['debug_buffer'][] = array('message' => $message, 'filename' => $filename, 'line' => $line);
	if (count($GLOBALS['fbx']['debug_buffer']) > 1000) array_shift($GLOBALS['fbx']['debug_buffer']);
}

// these functions handle plugins
// phases are: prerequest, preexec, postexec, preaction, postaction, pre/post-query/action/display/layout user-defined: prelayout, ...

function fbx_execute_plugins($phase, $item_name = '')
{
	$output = null;
	if (isset($GLOBALS['fbx']['plugins'][$phase]) && count($GLOBALS['fbx']['plugins'][$phase])) {
		foreach ($GLOBALS['fbx']['plugins'][$phase] as $plugin)
		{
			fbx_debug("Running $phase plugin $plugin", __FILE__, __LINE__);
			$output = $plugin($item_name);
		}
	}
	return($output);
}

function fbx_url_option($option)
{
	global $fbx;
	if ((isset($fbx) && !$fbx['production']) || (isset($_REQUEST['fbx_pass']) && $_REQUEST['fbx_pass'] == $fbx['settings']['password']))
	{
		return (isset($_REQUEST[$option]) ? $_REQUEST[$option] : false);
	}
	else 
	{
		return(false);
	}
}

