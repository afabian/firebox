<?php

require_once(__DIR__ . '/../helpers.php');
require_once(__DIR__ . '/../bootstrap.php');

function refs($php)
{
    $lex = fbx_lexical_parser($php);
    return fbx_get_function_references_from_lex($lex);
}

function ref_names($php)
{
    return array_column(refs($php), 'function');
}

// ---------------------------------------------------------------------------

section('Basic function call detection');

assert_true(in_array('foo',     ref_names('<?php foo();')),           'simple function call detected');
assert_true(in_array('bar',     ref_names('<?php foo(bar());')),      'nested call arg detected');
assert_true(in_array('my_func', ref_names('<?php my_func($x);')),    'function with arg detected');

section('Multiple calls');

$names = ref_names('<?php foo(); bar(); baz();');
assert_true(in_array('foo', $names), 'first of three calls detected');
assert_true(in_array('bar', $names), 'second of three calls detected');
assert_true(in_array('baz', $names), 'third of three calls detected');

section('Language keywords excluded');

foreach (array('if','else','while','for','foreach','switch','case','break') as $kw) {
    assert_false(in_array($kw, ref_names("<?php $kw;")), "$kw keyword not treated as function ref");
}

section('Variable function calls excluded');

// $foo() — dollar sign immediately before the word
assert_false(in_array('myfunc', ref_names('<?php $myfunc();')),       '$myfunc() not a ref');
assert_false(in_array('cb',     ref_names('<?php $cb();')),           '$cb() not a ref');

section('Function definitions excluded');

// The 'function' keyword followed by the name: name should be excluded as a ref
// because it's preceded by 'function ' (word + space)
assert_false(in_array('mydef', ref_names('<?php function mydef() {}')), 'function definition name excluded');

section('Reference position tracking');

$r = refs('<?php foo(); bar();');
assert_true(isset($r[0]['index']), 'reference has index field');
assert_true(isset($r[0]['pos']),   'reference has pos field');
assert_true($r[1]['pos'] > $r[0]['pos'], 'second ref has higher position than first');

section('Calls inside strings not detected');

// Inside double-quoted strings, word tokens are not in php state
assert_false(in_array('strfunc', ref_names('<?php $x = "strfunc()";')), 'call inside string not detected');

section('Method calls');

// $obj->method() — method is still detected as a ref (known limitation; harmless
// since it won't be in the libs table unless intentionally added)
$names = ref_names('<?php $obj->method();');
// We just verify this doesn't crash, not asserting either way on ->method
assert_true(is_array($names), 'method call does not crash reference detector');

section('Calls in comments not detected');

// Block comment: words inside should not produce php-state word tokens
assert_false(in_array('commented_func', ref_names('<?php /* commented_func(); */ $x;')),
    'function call inside block comment not detected');
