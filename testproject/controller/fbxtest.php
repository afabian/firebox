<?php

// Permanent test controller for Firebox framework lifecycle tests.

function pre()
{
	$pre_var = 'from_pre';
	action_once('fbxtest_init');
}

function show_pre_var()
{
	$content['body'] = $pre_var;
}

function show_post_body()
{
	$content['body'] = 'body_content';
}

function show_action_once_count()
{
	// pre() called action_once('fbxtest_init'); this uses action() (not once) — should run again
	action('fbxtest_init');
	$content['body'] = $GLOBALS['fbx']['fbxtest_call_count'];
}

function show_direct_body()
{
	$content['body'] = 'direct_body';
}

function show_setlink()
{
	setlink('home', 'show_pre_var');
	$content['body'] = linkto('home');
}

function show_linkaction()
{
	setlink('home', 'fbxtest.show_pre_var');
	$content['body'] = linkaction('home');
}

function post()
{
	return $content['body'];
}
