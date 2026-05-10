<?php

require_once(__DIR__ . '/../helpers.php');
require_once(__DIR__ . '/../bootstrap.php');

// ---------------------------------------------------------------------------
// Helper: compile a source string as a given path type and return the output

function compile_str($dir, $relpath, $content)
{
    write_source($dir, $relpath, $content);
    $GLOBALS['fbx']['compiled'] = array();   // reset per-request dedup
    fbx_compile(ltrim($relpath, '/'));
    return read_compiled($dir, $relpath);
}

// ---------------------------------------------------------------------------

section('Controller: function renaming');

$dir = make_test_project();

$out = compile_str($dir, 'controller/myctrl.php', '<?php
function show_list() {
    echo "hello";
}
');
assert_contains('controller_myctrl_show_list', $out, 'controller function renamed');
assert_not_contains('function show_list', $out,      'original name not in output');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Controller: global injection');

$dir = make_test_project();

$out = compile_str($dir, 'controller/myctrl.php', '<?php
function foo() { echo 1; }
');
assert_contains('global $fbx, $content;', $out, 'globals injected into function');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Controller: function_exists wrapper');

$dir = make_test_project();

$out = compile_str($dir, 'controller/myctrl.php', '<?php
function foo() { echo 1; }
');
assert_contains("if (!function_exists('controller_myctrl_foo'))", $out, 'function_exists wrapper added');
assert_contains('}}', $out, 'double-close ends wrapper+function');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Controller: pre() inlined into methods');

$dir = make_test_project();

$out = compile_str($dir, 'controller/myctrl.php', '<?php
function pre()
{
    $setup = 1;
}
function action()
{
    echo $setup;
}
function post()
{
    return "done";
}
');
assert_contains('$setup = 1;', $out, 'pre() body inlined into compiled method');
assert_not_contains('function pre()', $out, 'pre() not in output as standalone function');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Controller: post() inlined into methods');

$dir = make_test_project();

$out = compile_str($dir, 'controller/myctrl.php', '<?php
function pre()
{
}
function action()
{
    echo "body";
}
function post()
{
    return layout("main");
}
');
assert_contains('return layout("main");', $out, 'post() body inlined into compiled method');
assert_not_contains('function post()', $out, 'post() not in output as standalone function');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Controller: multiple methods all get pre/post');

$dir = make_test_project();

$out = compile_str($dir, 'controller/myctrl.php', '<?php
function pre() { $pre = true; }
function first() { echo 1; }
function second() { echo 2; }
function post() { return "end"; }
');
// Both first and second should contain pre and post bodies
$first_start  = strpos($out, 'controller_myctrl_first');
$second_start = strpos($out, 'controller_myctrl_second');
$pre_count    = substr_count($out, '$pre = true;');
$post_count   = substr_count($out, 'return "end";');
assert_equal(2, $pre_count,  'pre() body appears in each method');
assert_equal(2, $post_count, 'post() body appears in each method');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Model: only matching function renamed');

$dir = make_test_project();

$out = compile_str($dir, 'model/test/my_query.php', '<?php
function my_query($arg) {
    return $arg;
}
function helper_not_renamed() {
    return 1;
}
');
assert_contains('model_test_my_query_my_query', $out,   'model main function renamed');
assert_contains('function helper_not_renamed',  $out,   'helper function NOT renamed');
assert_not_contains('model_test_my_query_helper_not_renamed', $out, 'helper not given model name');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Model: test function renamed');

$dir = make_test_project();

$out = compile_str($dir, 'model/test/my_query.php', '<?php
function my_query() { return 1; }
function test_my_query() { return true; }
');
assert_contains('test_model_test_my_query_my_query', $out, 'test function renamed correctly');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('View: function renamed, output path correct');

$dir = make_test_project();

$out = compile_str($dir, 'view/test/my_view.php', '<?php function my_view($data) { echo $data; }');
assert_contains('view_test_my_view_my_view', $out, 'view function renamed');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('No-function file: global injected at top');

$dir = make_test_project();

$out = compile_str($dir, 'layout/test/my_layout.php',
    "<?php fbx_execute_plugins('prehtml'); ?>\n<html><?=\$content['body']?></html>\n");
assert_contains('<?php global $fbx, $content; ?>', $out, 'global injected for function-less file');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Caching: compiled file not rewritten when up-to-date');

$dir = make_test_project();

$src = '<?php function foo() { echo 1; }';
write_source($dir, 'controller/cache_test.php', $src);
fbx_compile('controller/cache_test.php');

$compiled_path = $dir . '/parsed/dev/controller.cache_test.php';
$mtime1 = filemtime($compiled_path);

sleep(1);                      // ensure clock moves
$GLOBALS['fbx']['compiled'] = array();   // reset dedup so compile is considered again
fbx_compile('controller/cache_test.php');
$mtime2 = filemtime($compiled_path);

assert_equal($mtime1, $mtime2, 'compiled file not rewritten when source unchanged');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Caching: recompiled when source is newer');

$dir = make_test_project();

$src = '<?php function foo() { echo 1; }';
write_source($dir, 'controller/stale_test.php', $src);
fbx_compile('controller/stale_test.php');

$compiled_path = $dir . '/parsed/dev/controller.stale_test.php';
$mtime1 = filemtime($compiled_path);

sleep(1);
// Touch the source to make it newer than compiled
touch($dir . '/controller/stale_test.php');
$GLOBALS['fbx']['compiled'] = array();
clearstatcache();
fbx_compile('controller/stale_test.php');
clearstatcache();
$mtime2 = filemtime($compiled_path);

assert_true($mtime2 > $mtime1, 'compiled file rewritten after source touched');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Dedup: same file not compiled twice in one request');

$dir = make_test_project();

write_source($dir, 'controller/dedup_test.php', '<?php function foo() { echo 1; }');
fbx_compile('controller/dedup_test.php');
$mtime1 = filemtime($dir . '/parsed/dev/controller.dedup_test.php');

// Do NOT reset $fbx['compiled'] — simulates second call in same request
touch($dir . '/controller/dedup_test.php'); // make source newer
fbx_compile('controller/dedup_test.php');
$mtime2 = filemtime($dir . '/parsed/dev/controller.dedup_test.php');

assert_equal($mtime1, $mtime2, 'file not recompiled when already in $fbx[compiled] this request');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Error: missing source file');

$dir = make_test_project();

assert_throws(function() {
    fbx_compile('controller/does_not_exist.php');
}, 'missing source file throws via fbx_error');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Output file path: correct naming convention');

$dir = make_test_project();

write_source($dir, 'model/todo/qry_list.php', '<?php function qry_list() { return array(); }');
fbx_compile('model/todo/qry_list.php');

$expected = $dir . '/parsed/dev/model.todo.qry_list.php';
assert_true(file_exists($expected), 'compiled model file at correct path');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Library: function name indexing');

$dir = make_test_project();

write_source($dir, 'lib/utils.php', '<?php
function util_sort($arr) { return $arr; }
function util_format($s) { return $s; }
');
$GLOBALS['fbx']['libs'] = null;  // force reload
$libs = fbx_load_libs();

assert_true(isset($libs['util_sort']),   'lib function util_sort indexed');
assert_true(isset($libs['util_format']), 'lib function util_format indexed');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('Library: require_once injected for used lib functions');

$dir = make_test_project();

write_source($dir, 'lib/utils.php', '<?php function util_do($x) { return $x; }');
$GLOBALS['fbx']['libs'] = null;

$out = compile_str($dir, 'controller/myctrl.php', '<?php
function action() {
    return util_do("hello");
}
');
assert_contains("require_once", $out,     'require_once injected for lib function');
assert_contains('utils.php',   $out,      'correct lib file referenced');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('fbx_dir_tree_files: returns files matching pattern');

$dir = make_test_project();
write_source($dir, 'lib/a.php', '<?php');
write_source($dir, 'lib/b.php', '<?php');
write_source($dir, 'lib/sub/c.php', '<?php');
write_source($dir, 'lib/ignore.txt', 'text');

$files = fbx_dir_tree_files($dir . '/lib/', '/.*\.php$/');
$basenames = array_map('basename', $files);
assert_true(in_array('a.php', $basenames), 'a.php found');
assert_true(in_array('b.php', $basenames), 'b.php found');
assert_true(in_array('c.php', $basenames), 'c.php found in subdir');
assert_false(in_array('ignore.txt', $basenames), 'non-php file excluded');

teardown_test_project($dir);

// ---------------------------------------------------------------------------

section('fbx_dir_tree_files: missing directory returns empty array');

$result = fbx_dir_tree_files('/tmp/fbx_test_nonexistent_' . uniqid() . '/');
assert_equal(array(), $result, 'missing directory returns empty array');
