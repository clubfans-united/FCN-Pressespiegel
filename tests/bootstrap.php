<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('FCNP_PLUGIN_FILE')) {
    define('FCNP_PLUGIN_FILE', dirname(__DIR__) . '/fcn-pressespiegel.php');
}

require_once __DIR__ . '/Stubs/WP_Post.php';
require_once __DIR__ . '/Stubs/WP_Query.php';
