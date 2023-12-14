<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Fly_Dynamic_Image_Resizer
 */

if (!defined('FS_METHOD')) {
    define('FS_METHOD', 'direct');
}
define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH'));

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir.'/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
tests_add_filter(
    'muplugins_loaded',
    static function () {
        require dirname(__FILE__, 2).'/fly-dynamic-image-resizer.php';
    }
);

// Start up the WP testing environment.
require $_tests_dir.'/includes/bootstrap.php';
