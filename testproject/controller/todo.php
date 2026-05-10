<?php

function pre()
{
	$datafile = action('define_datafile');
}

function show_list()
{
	setlink('create_form', 'show_create_item');
	setlink('edit_item', 'show_item_detail');
	setlink('done_item', 'change_item_done');
	$todo = query('qry_s_todo_list', $datafile);
	$todo = action('sort_array_by_key', $todo, 'due');
	$content['body'] = display('todo_list', $todo);
}

function show_item_detail()
{
	setlink('home', 'show_list');
	setlink('save', 'update_item');
	$item = query('qry_s_todo_item', $datafile, $_REQUEST['item_id']);
	$content['body'] = display('item_detail', $item);
}

function show_create_item()
{
	setlink('save', 'create_item');
	$content['body'] = display('item_detail', null);
}

function create_item()
{
	setlink('home', 'show_list');
	query('qry_i_todo_item', $datafile, $_REQUEST['title'], $_REQUEST['due'], $_REQUEST['details']);
	relocate(linkto('home'));
}

function update_item()
{
	setlink('home', 'show_list');
	query('qry_u_todo_item', $datafile, $_REQUEST['title'], $_REQUEST['due'], $_REQUEST['details'], $_REQUEST['item_id']);
	relocate(linkto('home'));
}

function change_item_done()
{
	setlink('home', 'show_list');
	query('qry_u_todo_item_done', $datafile, $_REQUEST['item_id'], true);
	relocate(linkto('home'));
}

function post()
{
	return(layout('lay_html'));
}
