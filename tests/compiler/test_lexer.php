<?php

require_once(__DIR__ . '/../helpers.php');
require_once(__DIR__ . '/../bootstrap.php');

// Helpers
function lex($input)         { return fbx_lexical_parser($input); }
function lex_types($input)   { return array_column(lex($input), 'type'); }
function lex_states($input)  { return array_column(lex($input), 'state'); }

function tokens_of_type($input, $type)
{
    return array_filter(lex($input), function($t) use ($type) { return $t['type'] == $type; });
}

function first_content($input, $type)
{
    foreach (lex($input) as $t) if ($t['type'] == $type) return $t['content'];
    return null;
}

function all_contents($input, $type)
{
    $out = array();
    foreach (lex($input) as $t) if ($t['type'] == $type) $out[] = $t['content'];
    return $out;
}

// ---------------------------------------------------------------------------

section('PHP block entry/exit');

$lex = lex('before<?phpafter?>end');
assert_true(in_array('o_php1', lex_types('<?php $x;')), 'long open tag produces o_php1');
assert_true(in_array('o_php2', lex_types('<? $x;')),    'short open tag produces o_php2');
assert_true(in_array('c_php',  lex_types('<?php ?>')) ,  'close tag produces c_php');

$mixed = lex('<html><?php $x; ?></html>');
$states = array_column($mixed, 'state');
assert_true(in_array('html', $states), 'html state present in mixed file');
assert_true(in_array('php',  $states), 'php state present in mixed file');

section('HTML passthrough');

$lex = lex('<p>hello world</p>');
assert_equal('html', $lex[0]['state'],   'plain HTML is in html state');
assert_contains('<p>hello world</p>', $lex[0]['content'], 'HTML content preserved verbatim');

section('PHP word tokens');

assert_equal('function', first_content('<?php function foo() {}', 'word'), 'function keyword tokenised as word');
assert_equal('foo',      all_contents('<?php function foo() {}', 'word')[1], 'function name tokenised as word');
assert_equal('echo',     first_content('<?php echo $x;', 'word'), 'echo tokenised as word');

section('PHP operator tokens');

assert_true(in_array('equaltype',    lex_types('<?php $a===1;')), '=== produces equaltype');
assert_true(in_array('notequaltype', lex_types('<?php $a!==1;')), '!== produces notequaltype');
assert_true(in_array('equal',        lex_types('<?php $a==1;')),  '== produces equal');
assert_true(in_array('notequal',     lex_types('<?php $a!=1;')),  '!= produces notequal');
assert_true(in_array('inc',          lex_types('<?php $a++;')),   '++ produces inc');
assert_true(in_array('dec',          lex_types('<?php $a--;')),   '-- produces dec');
assert_true(in_array('method',       lex_types('<?php $a->b;')), '-> produces method');
assert_true(in_array('keyvalue',     lex_types('<?php $a=>$b;')), '=> produces keyvalue');
assert_true(in_array('concatto',     lex_types('<?php $a.=$b;')), '.= produces concatto');
assert_true(in_array('assign',       lex_types('<?php $a=1;')),   '= produces assign');
assert_true(in_array('and',          lex_types('<?php $a&&$b;')), '&& produces and');
assert_true(in_array('or',           lex_types('<?php $a||$b;')), '|| produces or');
assert_true(in_array('scope',        lex_types('<?php A::B;')),   ':: produces scope');

section('Numeric literals');

assert_true(in_array('int',       lex_types('<?php 42;')),        'integer literal');
assert_true(in_array('float1',    lex_types('<?php .5;')),        'float starting with dot');
assert_true(in_array('float2',    lex_types('<?php 1.;')),        'float ending with dot');
assert_true(in_array('exponent1', lex_types('<?php 1e5;')),       'scientific notation int');
assert_true(in_array('exponent2', lex_types('<?php .5e3;')),      'scientific notation float1');

section('Single-quoted strings');

$lex = lex("<?php 'hello';");
assert_true(in_array('singlequote', lex_types("<?php 'hello';")), 'single quote token present');
assert_true(in_array('text',        lex_types("<?php 'hello';")), 'text inside single quote');

// escaped quote inside single-quoted string
$lex = lex("<?php 'it\\'s';");
assert_true(in_array('escape',      lex_types("<?php 'it\\'s';")), 'escape token in single-quoted string');

section('Double-quoted strings');

assert_true(in_array('doublequote', lex_types('<?php "hello";')),    'double quote token present');
assert_true(in_array('text',        lex_types('<?php "hello";')),    'text inside double quote');
assert_true(in_array('dollar',      lex_types('<?php "$var";')),     'dollar token inside double quote');

section('Line comments');

$lex = lex("<?php // this is a comment\n\$x=1;");
assert_true(in_array('linecomment', lex_types("<?php // comment\n")), 'line comment token');
assert_true(in_array('newline',     lex_types("<?php // comment\n")), 'newline ends line comment');

section('Block comments');

assert_true(in_array('o_comment', lex_types("<?php /* comment */ \$x;")), 'block comment open token');
assert_true(in_array('c_comment', lex_types("<?php /* comment */ \$x;")), 'block comment close token');

// Block comment should NOT produce word tokens for its contents
$words = all_contents("<?php /* function foo() */ \$x;", 'word');
assert_false(in_array('foo', $words), 'words inside block comment not tokenised');

section('Curly braces');

assert_true(in_array('o_curly', lex_types('<?php {')), 'opening curly produces o_curly');
assert_true(in_array('c_curly', lex_types('<?php }')), 'closing curly produces c_curly');

section('Position tracking');

$lex = lex('abc<?php $x; ?>def');
$php_open = null;
foreach ($lex as $t) { if ($t['type'] == 'o_php1') { $php_open = $t; break; } }
assert_equal(3, $php_open['pos'], 'PHP open tag starts at correct position');

section('Backtick strings');

assert_true(in_array('backtickquote', lex_types('<?php `ls`;')), 'backtick quote token present');
