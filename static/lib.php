<?php

namespace Minify\StaticService;

/**
 * Build a URI for the static cache
 *
 * @param string $static_uri E.g. "/min/static"
 * @param string $query E.g. "b=scripts&f=1.js,2.js"
 * @return string
 */
function build_uri($static_uri, $query) {
    $static_uri = rtrim($static_uri, '/');
    $query = ltrim($query, '?');

    $cache_time = get_cache_time();

    return "$static_uri/$cache_time/$query";
}

/**
 * Get the name of the current cache directory within static/. E.g. "1467089473"
 *
 * @param bool $auto_create Automatically create the directory if missing?
 * @return null|string null if missing or can't create
 */
function get_cache_time($auto_create = true) {
    foreach (scandir(__DIR__) as $entry) {
        if (ctype_digit($entry)) {
            return $entry;
            break;
        }
    }

    if (!$auto_create) {
        return null;
    }

    $time = (string)time();
    if (!mkdir(__DIR__ . "/$time")) {
        return null;
    }

    return $time;
}

function flush_cache() {
    $time = get_cache_time(false);
    if ($time) {
        remove_tree(__DIR__ . "/$time");
    }
}

function remove_tree($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        is_dir("$dir/$file") ? remove_tree("$dir/$file") : unlink("$dir/$file");
    }

    return rmdir($dir);
}

/**
 * Strictly parse a query string
 *
 * we basically want canonical querystrings because we make a file for each one.
 * manual parsing is the only way to do this. The MinApp controller will validate
 * these parameters anyway.
 *
 * @param string $query E.g. "b=min/foo&f=hello.js"
 * @return array|false false on failure
 */
function parse_parameters($query) {
    $ret = array();

    foreach (explode('&', $query) as $piece) {
        if (false === strpos($piece, '=')) {
            return false;
        }

        list($key, $value) = explode('=', $piece, 2);
        if (!in_array($key, array('f', 'g', 'b', 'z'))) {
            return false;
        }

        if (isset($ret[$key])) {
            // already used
            return false;
        }

        if ($key === 'z' && !preg_match('~^\.(css|js)$~', $value, $m)) {
            return false;
        }

        $ret[$key] = urldecode($value);
    }

    return $ret;
}
