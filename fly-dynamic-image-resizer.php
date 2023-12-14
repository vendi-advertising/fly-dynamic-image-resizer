<?php
/*
Plugin Name: Fly Dynamic Image Resizer
Description: Dynamically create image sizes on the fly!
Version: 2.0.8
Author: Junaid Bhura
Author URI: https://junaid.dev
Text Domain: fly-images
Requires PHP: 8.0
*/

namespace JB\FlyImages;

use WP_CLI;

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Plugin path.
 */
define('JB_FLY_PLUGIN_PATH', __DIR__);

/**
 * Require files.
 */
if (defined('WP_CLI') && WP_CLI) {
    require_once JB_FLY_PLUGIN_PATH.'/src/Fly_CLI.php';
    WP_CLI::add_command('fly-images', __NAMESPACE__.'\\Fly_CLI');
}
require_once JB_FLY_PLUGIN_PATH.'/src/Core.php';
require_once JB_FLY_PLUGIN_PATH.'/inc/helpers.php';
require_once JB_FLY_PLUGIN_PATH.'/vendor/autoload.php';

/**
 * Initialize plugin.
 */
add_action(
    'init',
    static function () {
        $fly_images = \JB\FlyImages\Core::get_instance();
        $fly_images->init();
    }
);
