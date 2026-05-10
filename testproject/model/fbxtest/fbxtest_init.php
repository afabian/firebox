<?php

function fbxtest_init()
{
	if (!isset($GLOBALS['fbx']['fbxtest_call_count'])) $GLOBALS['fbx']['fbxtest_call_count'] = 0;
	$GLOBALS['fbx']['fbxtest_call_count']++;
}
