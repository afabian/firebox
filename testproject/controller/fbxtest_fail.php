<?php

// Deliberately failing test function — used by test_errors.php to verify
// that a failing test_ function aborts the request.

function show()
{
	$content['body'] = 'should not appear';
}

function test_show()
{
	return false;
}

function post()
{
	return $content['body'];
}
