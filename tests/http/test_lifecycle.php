<?php

require_once(__DIR__ . '/../helpers.php');
require_once(__DIR__ . '/helpers.php');

// Uses testproject/controller/fbxtest.php and testproject/model/fbxtest/fbxtest_init.php
// These are permanent files in the repo — no dynamic deployment needed.

// ---------------------------------------------------------------------------

section('pre() variables available in methods');

$r = http('fbxtest.show_pre_var');
assert_equal(200, $r['status'],        'pre() test action returns 200');
assert_contains('from_pre', $r['body'], 'variable set in pre() is available in method body');

section('post() return value becomes page output');

$r = http('fbxtest.show_post_body');
assert_equal(200, $r['status'],            'post() test returns 200');
assert_contains('body_content', $r['body'], 'post() return value used as page output');

section('action_once() runs model exactly once per request');

$r = http('fbxtest.show_action_once_count');
// pre() calls action_once('fbxtest_init') — runs once
// method body calls action('fbxtest_init') — runs again (action(), not action_once())
// total call count should be 2
assert_equal(200, $r['status'],  'action_once test returns 200');
assert_contains('2', $r['body'], 'action_once in pre() + action() in method = 2 total calls');

section('$content[body] used for page output');

$r = http('fbxtest.show_direct_body');
assert_equal(200, $r['status'],         '$content[body] test returns 200');
assert_contains('direct_body', $r['body'], '$content[body] used as page output');

section('setlink() generates correct URL');

$r = http('fbxtest.show_setlink');
assert_equal(200, $r['status'],              'setlink test returns 200');
assert_contains('?go=fbxtest.show_pre_var', $r['body'], 'setlink with relative action generates correct URL');

section('linkaction() extracts controller.method from stored link');

$r = http('fbxtest.show_linkaction');
assert_equal(200, $r['status'],                  'linkaction test returns 200');
assert_contains('fbxtest.show_pre_var', $r['body'], 'linkaction returns controller.method string');

section('Plugin phases: debug pane visible in dev mode');

$r = http('todo.show_list');
assert_contains('fbx_debug_bar',  $r['body'], 'debug plugin registered and rendered');
assert_contains('Profiler',       $r['body'], 'profiler plugin registered and rendered');
assert_contains('Internal',       $r['body'], 'internal debug pane present');

section('Profiler plugin tracks execution');

$r = http('todo.show_list');
assert_contains('define_datafile', $r['body'], 'profiler shows define_datafile timing');
assert_contains('qry_s_todo_list', $r['body'], 'profiler shows query timing');

section('setlink / linkto generate correct URLs in todo app');

$r = http('todo.show_list');
assert_contains('?go=todo.show_create_item', $r['body'], 'setlink(create_form) → correct URL');
assert_contains('?go=todo.show_item_detail', $r['body'], 'setlink(edit_item) → correct URL');

section('Redirect after POST');

$unique_title = 'TestItem_' . uniqid();
$r = http('todo.create_item', array('title' => $unique_title, 'due' => '2026-06-01 00:00:00', 'details' => 'test'));
assert_equal(302, $r['status'],              'create_item POST returns 302 redirect');
assert_contains('show_list', $r['location'], 'redirect goes to show_list');

$after = http('todo.show_list');
assert_contains($unique_title, $after['body'], 'new item title appears in list after redirect');

// Restore original seed data to keep tests idempotent
shell_exec("ssh 10.0.0.10 'php -r \"\\\$items=require(\\\"/var/www/html/firebox-test/todo.data\\\");\\\$items=array_values(array_filter(\\\$items,function(\\\$i){return strpos(\\\$i[\\\"title\\\"],\\\"TestItem_\\\")===false;}));file_put_contents(\\\"/var/www/html/firebox-test/todo.data\\\",\\\"<?php return(\\\".var_export(\\\$items,true).\\\");\\\");\"' 2>/dev/null");

section('Nested controller calls via control()');

$r = http('todo.show_list');
assert_contains('todo_list_item', $r['body'], 'nested display() call renders sub-view');
