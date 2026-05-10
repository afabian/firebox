<?php

function qry_s_todo_list($datafile)
{
	debug("Loading data from $datafile", __FILE__, __LINE__);
	$items = require($datafile);
	debug("Returning " . count($items) . " items", __FILE__, __LINE__);
	return($items);
}

function test_qry_s_todo_list()
{
	$passing = true;

	$datafile = action('define_datafile');
	$todo = qry_s_todo_list($datafile);

	$expected_keys = array('id', 'title', 'due', 'done', 'details');

	if (isset($todo[0]))
	{
		if (array_keys($todo[0]) != $expected_keys)
		{
			$passing = false;
			fbx_debug("Data does not have the proper field types.", __FILE__, __LINE__);
			fbx_debug("  Wanted: " . var_export($expected_keys, true), __FILE__, __LINE__);
			fbx_debug("  Got:    " . var_export(array_keys($todo[0]), true), __FILE__, __LINE__);
		}
	}

	return($passing);
}
