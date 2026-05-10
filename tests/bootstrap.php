<?php

// Minimal bootstrap for compiler unit tests.
// Sets up $fbx globals and stubs framework functions without executing the
// request lifecycle. Safe to require multiple times (guards with function_exists).

global $fbx, $content;

$fbx['version']      = '0.6';
$fbx['fbx_root']     = realpath(__DIR__ . '/..') . '/';
$fbx['site_root']    = realpath(__DIR__ . '/../testproject') . '/';
$fbx['production']   = false;
$fbx['debug_indent'] = 0;
$fbx['debug_buffer'] = array();
$fbx['compiled']     = array();
$fbx['libs']         = null;

if (!function_exists('fbx_debug')) {
    function fbx_debug($message, $filename = '', $line = 0)
    {
        if (!isset($GLOBALS['fbx']['debug_indent'])) $GLOBALS['fbx']['debug_indent'] = 0;
        $GLOBALS['fbx']['debug_buffer'][] = $message;
    }
}

if (!function_exists('fbx_url_option')) {
    function fbx_url_option($option)
    {
        return false;
    }
}

if (!function_exists('fbx_error')) {
    // In tests, fbx_error throws so we can catch and assert on it.
    function fbx_error($message)
    {
        throw new RuntimeException("fbx_error: $message");
    }
}

if (!function_exists('fbx_plugin_register')) {
    function fbx_plugin_register($phase, $plugin)
    {
        $GLOBALS['fbx']['plugins'][$phase][] = $plugin;
    }
}

require_once($fbx['fbx_root'] . 'firebox_compiler.php');

// ---- Helpers used by compile tests ----------------------------------------

function make_test_project()
{
    $dir = sys_get_temp_dir() . '/fbx_test_' . uniqid();
    foreach (array('/parsed/dev', '/parsed/prod', '/controller', '/model/test',
                   '/view/test', '/layout/test', '/lib') as $sub) {
        mkdir($dir . $sub, 0777, true);
    }
    $GLOBALS['fbx']['site_root'] = $dir . '/';
    $GLOBALS['fbx']['compiled']  = array();
    $GLOBALS['fbx']['libs']      = null;
    return $dir;
}

function teardown_test_project($dir)
{
    // Recursively delete temp directory
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getRealPath()) : unlink($f->getRealPath());
    }
    rmdir($dir);
    $GLOBALS['fbx']['site_root'] = realpath(__DIR__ . '/../testproject') . '/';
    $GLOBALS['fbx']['compiled']  = array();
    $GLOBALS['fbx']['libs']      = null;
}

function write_source($dir, $relpath, $content)
{
    $full = $dir . '/' . ltrim($relpath, '/');
    $parent = dirname($full);
    if (!is_dir($parent)) mkdir($parent, 0777, true);
    file_put_contents($full, $content);
}

function read_compiled($dir, $relpath)
{
    $out = $dir . '/parsed/dev/' . str_replace('/', '.', ltrim($relpath, '/'));
    return file_exists($out) ? file_get_contents($out) : null;
}
