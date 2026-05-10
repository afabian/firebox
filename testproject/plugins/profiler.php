<?php

fbx_plugin_register('preexec',     'profiler_start_timer');
fbx_plugin_register('prehtml',     'profiler_output');
fbx_plugin_register('predisplay',  'profiler_enter');
fbx_plugin_register('prelayout',   'profiler_enter');
fbx_plugin_register('preaction',   'profiler_enter');
fbx_plugin_register('prequery',    'profiler_enter');
fbx_plugin_register('precontrol',  'profiler_enter');
fbx_plugin_register('postdisplay', 'profiler_exit');
fbx_plugin_register('postlayout',  'profiler_exit');
fbx_plugin_register('postaction',  'profiler_exit');
fbx_plugin_register('postquery',   'profiler_exit');
fbx_plugin_register('postcontrol', 'profiler_exit');

function profiler_start_timer($item_name)
{
	global $fbx;
	$fbx['profiler']['start_time'] = microtime(true);
}

function profiler_enter($item_name)
{
	global $fbx;
	if (!isset($fbx['profiler']['index'])) $fbx['profiler']['index'] = 0;
	$fbx['profiler']['stack'][] = array('index' => $fbx['profiler']['index']++, 'item_name' => $item_name, 'start_time' => microtime(true));
}

function profiler_exit($item_name)
{
	global $fbx;
	$item = array_pop($fbx['profiler']['stack']);
	$item['elapsed'] = microtime(true) - $item['start_time'];
	$item['nesting'] = count($fbx['profiler']['stack']) - 1;
	$fbx['profiler']['output'][] = $item;
}

function profiler_output($item_name)
{
	global $fbx, $content;
	if (!isset($fbx['profiler']['output']) || !count($fbx['profiler']['output'])) return;

	$out = $fbx['profiler']['output'];
	usort($out, function($a, $b) { return $a['index'] - $b['index']; });

	$lines = array();
	foreach ($out as $item)
	{
		$lines[] = sprintf("%3u %1.4f", $item['index'], $item['elapsed']) . ' ' . str_repeat(' ', max($item['nesting'] * 2, 0)) . $item['item_name'];
	}
	$lines[] = "----------<br>&nbsp;&nbsp;&nbsp;&nbsp;" . sprintf("%1.4f", microtime(true) - $fbx['profiler']['start_time']);
	$content['debugpanes']['profiler'] = str_replace(' ', '&nbsp;', join("<br>", $lines));
}
