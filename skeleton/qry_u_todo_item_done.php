<?php

function qry_u_todo_item_done($datafile, $item_id, $value)
{
	debug("Loading data from $datafile", __FILE__, __LINE__);	
	$items = require($datafile);
	debug(count($items) . " items found", __FILE__, __LINE__);

	for ($i=0; $i<count($items); $i++)
	{
		if ($items[$i]['id'] == $item_id)
		{
			break;
		}
	}

	debug("Found item at array index $i", __FILE__, __LINE__);
	
	$items[$i]['done'] = $value;
		
	debug("Writing out new $datafile", __FILE__, __LINE__);
	$fh = fopen($datafile, 'w');
	fputs($fh, "<?php return(" . var_export($items, true) . ");\n");
	fclose($fh);
}
