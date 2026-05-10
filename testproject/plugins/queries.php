<?php

// mysqli_safe_query() must be used in model files instead of mysqli_query() for this plugin to log queries.

fbx_plugin_register('prehtml', 'queries_output');

function mysqli_safe_query($query, $filename, $line)
{
	global $fbx;

	if (!isset($fbx['mysqli_connected']) || !$fbx['mysqli_connected'])
	{
		$dbh = mysqli_connect($fbx['settings']['mysqli_host'], $fbx['settings']['mysqli_user'], $fbx['settings']['mysqli_pass']);
		if (false === $dbh) fbx_error("Couldn't connect to database: " . mysqli_connect_error());
		$status = mysqli_select_db($dbh, $fbx['settings']['mysqli_database']);
		if (false === $status) fbx_error("Couldn't switch to database: " . mysqli_error($dbh));
		$fbx['mysqli_connected'] = true;
		$GLOBALS['fbx']['dbh'] = $dbh;
	}

	$GLOBALS['fbx']['queries'][] = array('query' => $query, 'filename' => $filename, 'line' => $line);

	$start_time = microtime(true);
	$result = mysqli_query($GLOBALS['fbx']['dbh'], $query);
	$elapsed = microtime(true) - $start_time;

	if ($result !== false)
	{
		$idx = count($GLOBALS['fbx']['queries']) - 1;
		$GLOBALS['fbx']['queries'][$idx]['rows'] = mysqli_affected_rows($GLOBALS['fbx']['dbh']);
		$GLOBALS['fbx']['queries'][$idx]['time'] = $elapsed;
	}
	else
	{
		fbx_error("Query failed: " . mysqli_error($GLOBALS['fbx']['dbh']));
	}

	return($result);
}

function queries_output($item_name)
{
	global $fbx, $content;
	if (!isset($fbx['queries']) || !count($fbx['queries'])) return;

	$output = array();
	foreach ($fbx['queries'] as $query)
	{
		$output[] = basename($query['filename']) . ":" . $query['line'] . ' Rows: ' . $query['rows'] . ', time: ' . sprintf('%1.4f', $query['time']) . '<br>&nbsp;&nbsp;' . wordwrap($query['query'], 150, '<br>&nbsp;&nbsp;') . '<br>';
	}
	$content['debugpanes']['queries'] = join("<br>", $output);
}
