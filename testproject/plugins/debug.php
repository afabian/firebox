<?php

if (!$GLOBALS['fbx']['production']) fbx_plugin_register('prehtml', 'debug_output');

function debug($message, $filename, $line)
{
	if (!$GLOBALS['fbx']['production']) $GLOBALS['fbx']['user_debug_buffer'][] = array('message' => $message, 'filename' => $filename, 'line' => $line);
}

function debug_output($item_name)
{
	global $fbx, $content;

	if (count($fbx['debug_buffer']))
	{
		$output = array();
		$filename_len = 0;
		for ($i=0; $i<count($fbx['debug_buffer']); $i++) $filename_len = max(strlen(basename($fbx['debug_buffer'][$i]['filename'])), $filename_len);
		foreach ($fbx['debug_buffer'] as $item)
		{
			$output[] = str_pad(basename($item['filename']) . ':' . $item['line'] . ' ', $filename_len + 6, ' ', STR_PAD_RIGHT) . $item['message'];
		}
		$content['debugpanes']['internal'] = str_replace(' ', '&nbsp;', join("<br>", $output));
	}

	if (isset($fbx['user_debug_buffer']) && count($fbx['user_debug_buffer']))
	{
		$output = array();
		$filename_len = 0;
		for ($i=0; $i<count($fbx['user_debug_buffer']); $i++) $filename_len = max(strlen(basename($fbx['user_debug_buffer'][$i]['filename'])), $filename_len);
		foreach ($fbx['user_debug_buffer'] as $item)
		{
			$output[] = str_pad(basename($item['filename']) . ':' . $item['line'] . ': ', $filename_len + 6, ' ', STR_PAD_RIGHT) . $item['message'];
		}
		$content['debugpanes']['user'] = str_replace(' ', '&nbsp;', join("<br>", $output));
	}
}
