<?php

require_once(__DIR__ . '/../helpers.php');
require_once(__DIR__ . '/helpers.php');

// ---------------------------------------------------------------------------

section('Default action');

$r = http('');
assert_equal(200,   $r['status'],            'no ?go= returns 200');
assert_contains('<h1>Firebox To-Do List</h1>', $r['body'], 'default action renders todo list');

section('Explicit action');

$r = http('todo.show_list');
assert_equal(200, $r['status'],              'todo.show_list returns 200');
assert_contains('<h1>Firebox To-Do List</h1>', $r['body'], 'todo.show_list renders correct page');

section('Missing controller');

$r = http('nonexistent_ctrl.show');
assert_equal(200,                $r['status'], 'missing controller returns 200 (handled error)');
assert_contains("Error:",        $r['body'],   'missing controller shows error message');
assert_contains('nonexistent_ctrl', $r['body'], 'error identifies the missing controller');

section('Missing method');

$r = http('todo.nonexistent_method');
assert_equal(200,           $r['status'],   'missing method returns 200 (handled error)');
assert_contains("Error:",   $r['body'],     'missing method shows error message');
assert_contains('nonexistent_method', $r['body'], 'error identifies missing method');

section('fbx.* admin actions');

$r = http('fbx.about');
assert_equal(200, $r['status'],      'fbx.about returns 200');
assert_contains('Firebox', $r['body'], 'fbx.about renders about page');

section('Controller and method parsed correctly');

$r = http('todo.show_list');
// Verify internal routing by checking that the todo controller ran
assert_contains('To-Do', $r['body'], 'controller.method routing: todo controller ran');

section('?go= with extra query params');

$r = http('todo.show_item_detail&item_id=1');
// item_detail needs an item_id; may show error or form depending on data
assert_equal(200, $r['status'], 'action with extra query params returns 200');

section('Production mode toggle');

// Start production mode
$r = http('fbx.start_production');
assert_equal(200, $r['status'], 'fbx.start_production returns 200');
assert_contains('production mode', strtolower($r['body']), 'start_production confirms mode');

// Hit app in production — should still work
$r = http('todo.show_list');
assert_equal(200, $r['status'],              'app works in production mode');
assert_contains('<h1>Firebox To-Do List</h1>', $r['body'], 'correct output in production mode');

// Stop production mode
$r = http('fbx.stop_production');
assert_equal(200, $r['status'], 'fbx.stop_production returns 200');

// Confirm back in dev mode (debug bar visible)
$r = http('todo.show_list');
assert_contains('fbx_debug_bar', $r['body'], 'debug bar visible again after stopping production');

section('?fbx_develop forces dev mode');

// First enable production
http('fbx.start_production');

$r = http('todo.show_list', null, false, array('fbx_develop' => 1, 'fbx_pass' => 'test'));
assert_contains('fbx_debug_bar', $r['body'], '?fbx_develop overrides production mode');

// Cleanup
http('fbx.stop_production');
