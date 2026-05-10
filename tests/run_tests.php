#!/usr/bin/env php
<?php

// Firebox test runner
// Usage: php tests/run_tests.php [compiler|http|all]
//
// Compiler tests: pure PHP CLI, no server needed
// HTTP tests:     require test server at 10.0.0.10 with testproject deployed

$mode = isset($argv[1]) ? $argv[1] : 'all';

$run_compiler = in_array($mode, array('all', 'compiler'));
$run_http     = in_array($mode, array('all', 'http'));

echo "Firebox Test Suite\n";
echo str_repeat('=', 50) . "\n";

$compiler_tests = array(
    'Lexer'      => __DIR__ . '/compiler/test_lexer.php',
    'Blocks'     => __DIR__ . '/compiler/test_blocks.php',
    'References' => __DIR__ . '/compiler/test_references.php',
    'Compile'    => __DIR__ . '/compiler/test_compile.php',
);

$http_tests = array(
    'Routing'   => __DIR__ . '/http/test_routing.php',
    'Lifecycle' => __DIR__ . '/http/test_lifecycle.php',
    'Errors'    => __DIR__ . '/http/test_errors.php',
);

if ($run_compiler) {
    echo "\nCOMPILER TESTS\n";
    echo str_repeat('-', 50) . "\n";
    foreach ($compiler_tests as $name => $file) {
        echo "\n=== $name ===\n";
        require($file);
    }
}

if ($run_http) {
    echo "\nHTTP INTEGRATION TESTS\n";
    echo str_repeat('-', 50) . "\n";
    foreach ($http_tests as $name => $file) {
        echo "\n=== $name ===\n";
        require($file);
    }
}

// Print combined summary
$total = $GLOBALS['fbx_test_pass'] + $GLOBALS['fbx_test_fail'];
echo "\n";
echo str_repeat('=', 50) . "\n";
echo "TOTAL: {$GLOBALS['fbx_test_pass']}/$total passed";
if ($GLOBALS['fbx_test_fail'] > 0) {
    echo "  ({$GLOBALS['fbx_test_fail']} FAILED)";
    echo "\n";
    exit(1);
}
echo "\n";
exit(0);
