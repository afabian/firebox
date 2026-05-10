<?php

// Tests @ error suppression survives compilation.

function show()
{
	$arr = array();
	$val = @$arr['no_such_key'];
	$content['body'] = 'ok';
}

function post()
{
	return $content['body'];
}
