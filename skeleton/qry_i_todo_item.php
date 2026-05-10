<?php

function qry_i_todo_item($datafile, $title, $due, $details)
{
	debug("Loading data from $datafile", __FILE__, __LINE__);	
	$items = require($datafile);
	debug(count($items) . " items found", __FILE__, __LINE__);

	$max_id = 0;
	foreach($items as $item)
	{
		if ($item['id'] > $max_id)
		{
			$max_id = $item['id'];
		}
	}
	
	$year = substr($due, 0, 4);
	$month = substr($due, 5, 2);
	$day = substr($due, 8, 2);
	$hour = substr($due, 11, 2);
	$minute = substr($due, 14, 2);
	$second = substr($due, 17, 2);
	$duetime = mktime($hour, $minute, $second, $month, $day, $year);
	
	debug("Assigning new item ID " . $max_id + 1, __FILE__, __LINE__);
	$items[] = array('id' => $max_id + 1, 'title' => $title, 'due' => $duetime, 'done' => false, 'details' => $details);
	
	debug("Writing out new $datafile", __FILE__, __LINE__);
	$fh = fopen($datafile, 'w');
	fputs($fh, "<?php return(" . var_export($items, true) . ");\n");
	fclose($fh);
}
