<?php

// all query, action, display, and layout fn's have global variables $fbx and $content available

function define_datafile()
{
	return($fbx['site_root'] . 'todo.data');
}

function test_define_datafile()
{
	$passing = true;
	
	$datafile = define_datafile();
	if (!file_exists($datafile))
	{
		$passing = false;
		fbx_debug("$datafile doesn't exist on disk", __FILE__, __LINE__);
	}
	
	return($passing);
}