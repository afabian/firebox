<?php

// for this plugin to work, mysqli_safe_query must be used instead of mysqli_query
// I suppose other non-mysql versions could be added here

fbx_plugin_register('prehtml', 'queries_output');

function mysqli_safe_query($query, $filename, $line)
{
	global $fbx;
	
	if (!$fbx['mysqli_connected'])
	{
		$dbh = mysqli_connect($fbx['settings']['mysqli_host'], $fbx['settings']['mysqli_user'], $fbx['settings']['mysqli_pass']);
		if (false === $dbh) fbx_error("Couldn't connect to database: " . mysqli_error($dbh), __FILE__, __LINE__);
		$status = mysqli_select_db($dbh, $fbx['settings']['mysqli_database']);
		if (false === $status) fbx_error("Couldn't switch to database: " . mysqli_error($dbh), __FILE__, __LINE__);
		$fbx['mysqli_connected'] = true;
		$GLOBALS['fbx']['dbh'] = $dbh;
	}
	
	$GLOBALS['fbx']['queries'][] = array('query' => $query, 'filename' => $filename, 'line'=> $line);
	
	$start_time = microtime(true);
	$result = mysqli_query($GLOBALS['fbx']['dbh'], $query);
	$elapsed = microtime(true) - $start_time;
	
	if ($result !== false)
	{
		$GLOBALS['fbx']['queries'][count($GLOBALS['fbx']['queries'])-1]['rows'] = mysqli_affected_rows($GLOBALS['fbx']['dbh']);
		$GLOBALS['fbx']['queries'][count($GLOBALS['fbx']['queries'])-1]['time'] = $elapsed;
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
	if (count($fbx['queries']))
	{
		foreach ($fbx['queries'] as $query)
		{
			$output[] = basename($query['filename']) . ":" . $query['line'] . ' Rows returned/affected: ' 
				  . $query['rows'] . ', time: ' . sprintf('%1.4f', $query['time']) . '<br>&nbsp;&nbsp;' 
				  . wordwrap($query['query'], 150, '<br>&nbsp;&nbsp;') . '<br>';
		}
		$content['debugpanes']['queries'] = join("<br>", $output);
	}
}
