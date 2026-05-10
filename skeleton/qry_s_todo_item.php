<?php

// this gets one item from the todo list

function qry_s_todo_item($datafile, $item_id)
{
	debug("Loading data from $datafile", __FILE__, __LINE__);
	
	$items = require($datafile);
	
	debug(count($items) . " items found", __FILE__, __LINE__);
	
	foreach ($items as $item)
	{
		if ($item['id'] == $item_id)
		{
			debug("Found correct ID and returning item record", __FILE__, __LINE__);
			return($item);
		}
	}
	
	return(false);
}
