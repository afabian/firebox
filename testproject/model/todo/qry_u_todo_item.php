<?php

function qry_u_todo_item($datafile, $title, $due, $details, $item_id)
{
	debug("Loading data from $datafile", __FILE__, __LINE__);
	$items = require($datafile);
	debug(count($items) . " items found", __FILE__, __LINE__);

	for ($i=0; $i<count($items); $i++)
	{
		if ($items[$i]['id'] == $item_id) break;
	}
	debug("Found item at array index $i", __FILE__, __LINE__);

	$year   = substr($due, 0, 4);
	$month  = substr($due, 5, 2);
	$day    = substr($due, 8, 2);
	$hour   = substr($due, 11, 2);
	$minute = substr($due, 14, 2);
	$second = substr($due, 17, 2);
	$duetime = mktime($hour, $minute, $second, $month, $day, $year);

	$items[$i]['title']   = $title;
	$items[$i]['due']     = $duetime;
	$items[$i]['details'] = $details;

	debug("Writing out new $datafile", __FILE__, __LINE__);
	$fh = fopen($datafile, 'w');
	fputs($fh, "<?php return(" . var_export($items, true) . ");\n");
	fclose($fh);
	if (function_exists('opcache_invalidate')) opcache_invalidate($datafile, true);
}
