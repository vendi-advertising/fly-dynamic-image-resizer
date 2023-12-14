<?php

if (!function_exists('fly_add_image_size')) {
    /**
     * Add image sizes to the JB\FlyImages\Core class.
     */
    function fly_add_image_size(string $size_name = '', int $width = 0, int $height = 0, bool $crop = false): bool
    {
        return JB\FlyImages\Core::get_instance()->add_image_size($size_name, $width, $height, $crop);
    }
}

if (!function_exists('fly_get_attachment_image_src')) {
    /**
     * Get a dynamically generated image URL from the JB\FlyImages\Core class.
     */
    function fly_get_attachment_image_src(int $attachment_id = 0, array|string $size = '', bool $crop = null): array
    {
        return JB\FlyImages\Core::get_instance()->get_attachment_image_src($attachment_id, $size, $crop);
    }
}

if (!function_exists('fly_get_attachment_image')) {
    /**
     * Get a dynamically generated image HTML from the JB\FlyImages\Core class.
     */
    function fly_get_attachment_image(int $attachment_id = 0, array|string $size = '', bool $crop = null, array $attr = []): string
    {
        return JB\FlyImages\Core::get_instance()->get_attachment_image($attachment_id, $size, $crop, $attr);
    }
}

if (!function_exists('fly_get_image_size')) {
    /**
     * Get a previously declared image size from the JB\FlyImages\Core class.
     */
    function fly_get_image_size(string $size_name = ''): array
    {
        return JB\FlyImages\Core::get_instance()->get_image_size($size_name);
    }
}

if (!function_exists('fly_get_all_image_sizes')) {
    /**
     * Get all declared images sizes from the JB\FlyImages\Core class.
     */
    function fly_get_all_image_sizes(): array
    {
        return JB\FlyImages\Core::get_instance()->get_all_image_sizes();
    }
}
