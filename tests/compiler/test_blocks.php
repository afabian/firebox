<?php

require_once(__DIR__ . '/../helpers.php');
require_once(__DIR__ . '/../bootstrap.php');

function blocks($php)
{
    $lex = fbx_lexical_parser($php);
    return fbx_get_blocks_from_lex($lex);
}

function function_blocks($php)
{
    return array_values(array_filter(blocks($php), function($b) { return $b['type'] == 'function'; }));
}

function function_names($php)
{
    return array_column(function_blocks($php), 'name');
}

// ---------------------------------------------------------------------------

section('Single function detection');

$b = function_blocks('<?php function foo() {}');
assert_equal(1, count($b),    'one function block found');
assert_equal('foo', $b[0]['name'], 'function name extracted');

section('Multiple functions');

$b = function_blocks('<?php function foo() {} function bar() {} function baz() {}');
assert_equal(3, count($b), 'three function blocks found');
assert_equal(array('foo','bar','baz'), array_column($b, 'name'), 'all names extracted in order');

section('pre() and post() detection');

$src = '<?php function pre() { $x=1; } function show() {} function post() { return 1; }';
$b   = function_blocks($src);
$names = array_column($b, 'name');
assert_true(in_array('pre',  $names), 'pre() detected');
assert_true(in_array('post', $names), 'post() detected');
assert_true(in_array('show', $names), 'regular method detected alongside pre/post');

section('Function with parameters');

$b = function_blocks('<?php function do_thing($a, $b, $c) {}');
assert_equal(1,         count($b),        'function with params: one block');
assert_equal('do_thing', $b[0]['name'],   'function with params: name correct');

section('Nested if block inside function');

$src = '<?php function foo() { if (true) { echo 1; } }';
$all = blocks($src);
$types = array_column($all, 'type');
assert_true(in_array('function', $types), 'function block found in nested structure');
assert_true(in_array('if',       $types), 'if block found inside function');
assert_equal(2, count($all), 'exactly 2 blocks total (function + if)');

section('Depth tracking');

$src  = '<?php function outer() { if (true) { while (true) {} } }';
$all  = blocks($src);
$fn   = array_filter($all, function($b) { return $b['type'] == 'function'; });
$fn   = array_values($fn);
assert_equal(0, $fn[0]['depth'], 'outer function at depth 0');

$inner = array_filter($all, function($b) { return $b['type'] == 'while'; });
$inner = array_values($inner);
assert_equal(2, $inner[0]['depth'], 'while block at depth 2');

section('Block start/end positions');

$src = '<?php function foo() { echo 1; }';
$b   = function_blocks($src);
assert_true($b[0]['start'] !== false,  'start position set');
assert_true($b[0]['end']   !== false,  'end position set');
assert_true($b[0]['end']   > $b[0]['start'], 'end after start');

section('Allman vs K&R brace style');

$allman = "<?php\nfunction foo()\n{\n    echo 1;\n}\n";
$knr    = "<?php\nfunction foo() {\n    echo 1;\n}\n";
assert_equal(array('foo'), function_names($allman), 'Allman style: name detected');
assert_equal(array('foo'), function_names($knr),    'K&R style: name detected');

section('Indented closing brace');

$src = "<?php\nfunction foo()\n{\n    echo 1;\n    }\n";
$b   = function_blocks($src);
assert_equal(1, count($b), 'indented closing brace: function still detected');
assert_true($b[0]['end'] !== false, 'indented closing brace: end position set');

section('Non-keyword curly brace (class)');

// 'class' is not in block_identifiers — block should get type=null, not crash
$b = blocks('<?php class Foo { function bar() {} }');
$types = array_column($b, 'type');
assert_true(in_array('function', $types), 'function inside class still detected');
assert_true(in_array(null, $types),       'class block has null type (not a keyword)');

section('No stale type from previous block');

// Previously, a non-keyword { would inherit type from the previous iteration
$src = '<?php function foo() { if (true) {} } class Bar { function baz() {} }';
$fns = function_names($src);
assert_equal(array('foo','baz'), $fns, 'only real functions detected — no ghost function from class {}');

section('Empty function body');

$b = function_blocks('<?php function empty_fn() {}');
assert_equal(1, count($b), 'empty function body: block detected');

section('Anonymous function (closure)');

// A closure gets a block with type=function but name is the word before 'function'
// (which is whatever $word was in the outer scope). We just verify it doesn't crash.
$b = blocks('<?php $fn = function() { echo 1; };');
assert_true(is_array($b), 'anonymous function does not crash block detector');
