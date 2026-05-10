<?php

fbx_plugin_register('preexec', 'profiler_start_timer');

fbx_plugin_register('prehtml', 'profiler_output');

fbx_plugin_register('predisplay', 'profiler_enter');
fbx_plugin_register('prelayout', 'profiler_enter');
fbx_plugin_register('preaction', 'profiler_enter');
fbx_plugin_register('prequery', 'profiler_enter');
fbx_plugin_register('precontrol', 'profiler_enter');

fbx_plugin_register('postdisplay', 'profiler_exit');
fbx_plugin_register('postlayout', 'profiler_exit');
fbx_plugin_register('postaction', 'profiler_exit');
fbx_plugin_register('postquery', 'profiler_exit');
fbx_plugin_register('postcontrol', 'profiler_exit');

function profiler_start_timer($item_name)
{
	global $fbx, $content;
	$fbx['profiler']['start_time'] = microtime(true);
}

function profiler_enter($item_name)
{
	global $fbx, $content;
	$fbx['profiler']['stack'][] = array('index' => $fbx['profiler']['index']++, 'item_name' => $item_name, 'start_time' => microtime(true));
}

function profiler_exit($item_name)
{
	global $fbx, $content;
	$item = array_pop($fbx['profiler']['stack']);
	$item['elapsed'] = microtime(true) - $item['start_time'];
	$item['nesting'] = count($fbx['profiler']['stack']) - 1;
	$fbx['profiler']['output'][] = $item;
}

function profiler_output($item_name)
{
	global $fbx, $content;
	if (count($fbx['profiler']['output']))
	{
		for ($i=0; $i<count($fbx['profiler']['output']); $i++)
		{
			$changed = false;
			for ($j=$i+1; $j<count($fbx['profiler']['output']); $j++)
			{
				if ($fbx['profiler']['output'][$i]['index'] > $fbx['profiler']['output'][$j]['index'])
				{
					$changed = true;
					$temp = $fbx['profiler']['output'][$i];
					$fbx['profiler']['output'][$i] = $fbx['profiler']['output'][$j];
					$fbx['profiler']['output'][$j] = $temp;
				}
			}
		}		
		for ($i=0; $i<count($fbx['profiler']['output']); $i++)
		{
			$item = $fbx['profiler']['output'][$i];
			$output[] = sprintf("%3u %1.4f", $item['index'], $item['elapsed']) . ' ' . str_repeat(' ', max($item['nesting'] * 2, 0)) . $item['item_name'];
		}
		$output[] = "----------<br>&nbsp;&nbsp;&nbsp;&nbsp;" . sprintf("%1.4f", microtime(true) - $fbx['profiler']['start_time']);
		$content['debugpanes']['profiler'] = str_replace(' ', '&nbsp;', join("<br>", $output));
	}
}
