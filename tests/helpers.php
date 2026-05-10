<?php

// Simple assertion library for Firebox tests

$GLOBALS['fbx_test_pass'] = 0;
$GLOBALS['fbx_test_fail'] = 0;
$GLOBALS['fbx_test_section'] = '';

function section($name)
{
    $GLOBALS['fbx_test_section'] = $name;
    echo "\n[$name]\n";
}

function pass($message)
{
    echo "  PASS: $message\n";
    $GLOBALS['fbx_test_pass']++;
}

function fail($message, $detail = '')
{
    echo "  FAIL: $message\n";
    if ($detail) echo "        $detail\n";
    $GLOBALS['fbx_test_fail']++;
}

function assert_true($value, $message)
{
    $value ? pass($message) : fail($message, 'Expected: true, Got: ' . var_export($value, true));
    return (bool)$value;
}

function assert_false($value, $message)
{
    !$value ? pass($message) : fail($message, 'Expected: false, Got: ' . var_export($value, true));
    return !$value;
}

function assert_equal($expected, $actual, $message)
{
    if ($expected === $actual) {
        pass($message);
        return true;
    }
    fail($message, "Expected: " . var_export($expected, true) . "\n        Actual:   " . var_export($actual, true));
    return false;
}

function assert_contains($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) !== false) {
        pass($message);
        return true;
    }
    fail($message, "Expected to find: " . var_export($needle, true) . "\n        In: " . var_export(substr($haystack, 0, 300), true));
    return false;
}

function assert_not_contains($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) === false) {
        pass($message);
        return true;
    }
    fail($message, "Did not expect to find: " . var_export($needle, true));
    return false;
}

function assert_throws($callable, $message)
{
    try {
        $callable();
        fail($message, 'No exception was thrown');
        return false;
    } catch (Exception $e) {
        pass($message);
        return true;
    }
}

function assert_matches($pattern, $subject, $message)
{
    if (preg_match($pattern, $subject)) {
        pass($message);
        return true;
    }
    fail($message, "Pattern $pattern did not match: " . var_export(substr($subject, 0, 200), true));
    return false;
}

function test_summary()
{
    $total = $GLOBALS['fbx_test_pass'] + $GLOBALS['fbx_test_fail'];
    echo "\n";
    echo str_repeat('=', 50) . "\n";
    echo "Results: {$GLOBALS['fbx_test_pass']}/$total passed";
    if ($GLOBALS['fbx_test_fail'] > 0) echo "  ({$GLOBALS['fbx_test_fail']} FAILED)";
    echo "\n";
    return $GLOBALS['fbx_test_fail'] === 0;
}
