<?php

namespace JB\FlyImages;

/**
 * Autoloader.
 *
 * @param string $class_name
 */

spl_autoload_register(
    static function ($class_name = '') {
        if (str_starts_with($class_name, 'JB\\FlyImages')) {
            $file = str_replace('\\', DIRECTORY_SEPARATOR, strtolower($class_name));
            require_once JB_FLY_PLUGIN_PATH.'/inc/class-'.basename($file).'.php';
        }
    }
);
