<?php

/**
 * Plugin Name:     FCN Pressespiegel
 * Plugin URI:      https://github.com/clubfans-united/FCN-Pressespiegel/
 * Description:     FCN Pressespiegel als eigenständiges Plugin (ehemals Teil von Clubfans United)
 * Author:          Stefan Helmer
 * Author URI:      https://github.com/rockschtar
 * Version:         develop
 * Requires at least: 6.4
 * Requires PHP:      8.3
 *
 */

use FCNPressespiegel\Controller\PluginController;

define('FCNP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FCNP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FCNP_PLUGIN_FILE', __FILE__);

require_once __DIR__ . '/vendor/autoload.php';

PluginController::init();
