<?php

// HTTP test helpers — uses PHP stream wrappers so no curl dependency

define('TEST_BASE', 'http://10.0.0.10/firebox-test/');

function http($go = '', $post = null, $follow = false, $extra = array())
{
    $params = $extra;
    if ($go) $params = array_merge(array('go' => $go), $params);
    $url = TEST_BASE . ($params ? '?' . http_build_query($params) : '');

    $opts = array(
        'http' => array(
            'ignore_errors' => true,
            'follow_location' => $follow ? 1 : 0,
            'max_redirects'   => $follow ? 5 : 0,
        )
    );

    if ($post !== null) {
        $opts['http']['method']  = 'POST';
        $opts['http']['content'] = http_build_query($post);
        $opts['http']['header']  = 'Content-Type: application/x-www-form-urlencoded';
    }

    $ctx  = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);
    $raw  = $http_response_header ?? array();

    $status = 0;
    foreach ($raw as $h) {
        if (preg_match('/HTTP\/\d\.?\d? (\d+)/', $h, $m)) $status = (int)$m[1];
    }

    $location = '';
    foreach ($raw as $h) {
        if (stripos($h, 'Location:') === 0) $location = trim(substr($h, 9));
    }

    return array('status' => $status, 'body' => $body ?: '', 'location' => $location, 'headers' => $raw);
}

function clear_parsed_cache()
{
    // Clear compiled files on server so tests see fresh compilation
    shell_exec('ssh 10.0.0.10 "rm -f /var/www/html/firebox-test/parsed/dev/*.php" 2>/dev/null');
}
