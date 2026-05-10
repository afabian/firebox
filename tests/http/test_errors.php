<?php

require_once(__DIR__ . '/../helpers.php');
require_once(__DIR__ . '/helpers.php');

// ---------------------------------------------------------------------------

section('Missing controller shows error');

$r = http('no_such_ctrl.method');
assert_equal(200,          $r['status'],        'missing controller: 200 status');
assert_contains('Error:',  $r['body'],           'missing controller: error message shown');
assert_not_contains('<h1>Firebox To-Do List</h1>', $r['body'], 'missing controller: todo list not shown');

section('Missing method shows error');

$r = http('todo.no_such_method');
assert_equal(200,                    $r['status'], 'missing method: 200 status');
assert_contains('Error:',            $r['body'],   'missing method: error message shown');
assert_contains('no_such_method',    $r['body'],   'missing method: method name in error');

section('Error page renders properly');

$r = http('todo.no_such_method');
// Error page should be HTML, not a fatal crash
assert_contains('</html>', $r['body'],            'error page is valid HTML');
assert_not_contains('Fatal Error', $r['body'],    'handled error does not show as fatal');

section('Missing fbx.* action shows error');

$r = http('fbx.nonexistent_admin_page');
assert_equal(200,         $r['status'],           'missing fbx action: 200 status');
assert_contains('Error:', $r['body'],              'missing fbx action: error shown');

section('Built-in model tests run and pass');

// In dev mode, test_ functions run automatically on first access.
// A passing test produces no error; the page renders normally.
// We verify by checking the todo list renders (all its model tests pass).
clear_parsed_cache();
$r = http('todo.show_list');
assert_not_contains('Tests failed', $r['body'],    'built-in model tests pass');
assert_contains('<h1>Firebox To-Do List</h1>', $r['body'], 'page renders after tests pass');

section('Test failure aborts request with error');

// Uses testproject/controller/fbxtest_fail.php (permanent file with test_show() returning false)
$r = http('fbxtest_fail.show');
assert_contains('Tests failed',         $r['body'], 'failing test aborts with error');
assert_not_contains('should not appear', $r['body'], 'page output not rendered after test failure');

section('?fbx_skip_tests bypasses test execution');

$r = http('fbxtest_fail.show', null, false, array('fbx_skip_tests' => 1));
assert_not_contains('Tests failed', $r['body'], '?fbx_skip_tests bypasses failing tests');

section('@-suppression respected by error handler');

// Uses testproject/controller/fbxtest_at.php (permanent file — @ must survive compilation)
$r = http('fbxtest_at.show');
assert_equal(200,     $r['status'],  '@ suppression: 200 status');
assert_contains('ok', $r['body'],   '@ suppression: page renders without error');
