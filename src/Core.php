<?php

namespace JB\FlyImages;

class Core
{
    private static ?self $_instance = null;
    private array $_image_sizes = [];
    private string $_fly_dir = '';
    private string $_capability = 'manage_options';

    /**
     * Get current instance.
     */
    public static function get_instance(): self
    {
        if (!self::$_instance) {
            $class = __CLASS__;
            self::$_instance = new $class();
        }

        return self::$_instance;
    }

    /**
     * Initialize plugin.
     */
    public function init(): void
    {
        $this->_fly_dir = apply_filters('fly_dir_path', $this->get_fly_dir());
        $this->_capability = apply_filters('fly_images_user_capability', $this->_capability);

        $this->check_fly_dir();

        add_action('admin_menu', [$this, 'admin_menu_item']);
        add_filter('media_row_actions', [$this, 'media_row_action'], 10, 2);
        add_action('delete_attachment', [$this, 'delete_attachment_fly_images']);

        add_action('switch_blog', [$this, 'blog_switched']);
    }

    /**
     * Get the path to the directory where all Fly images are stored.
     */
    public function get_fly_dir(string $path = '', bool $emptyFlyPathFirst = false): string
    {
        if ($emptyFlyPathFirst) {
            $this->_fly_dir = '';
        }

        if (empty($this->_fly_dir)) {
            $wp_upload_dir = wp_upload_dir();

            return $wp_upload_dir['basedir'].DIRECTORY_SEPARATOR.'fly-images'.('' !== $path ? DIRECTORY_SEPARATOR.$path : '');
        }

        return $this->_fly_dir.('' !== $path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Create fly images directory if it doesn't already exist.
     */
    public function check_fly_dir(): void
    {
        if (!is_dir($this->_fly_dir)) {
            wp_mkdir_p($this->_fly_dir);
        }
    }

    /**
     * Check if the Fly images folder exists and is writeable.
     */
    public function fly_dir_writable(): bool
    {
        return is_dir($this->_fly_dir) && wp_is_writable($this->_fly_dir);
    }

    /**
     * Add admin menu item.
     */
    public function admin_menu_item(): void
    {
        add_management_page(
            __('Fly Images', 'fly-dynamic-image-resizer'),
            __('Fly Images', 'fly-dynamic-image-resizer'),
            $this->_capability,
            'fly-images',
            [$this, 'options_page']
        );
    }

    /**
     * Add a new row action to media library items.
     */
    public function media_row_action(array $actions, object $post): array
    {
        if (!str_starts_with($post->post_mime_type, 'image/') || !current_user_can($this->_capability)) {
            return $actions;
        }

        $url = wp_nonce_url(admin_url('tools.php?page=fly-images&delete-fly-image&ids='.$post->ID), 'delete_fly_image', 'fly_nonce');
        $actions['fly-image-delete'] = '<a href="'.esc_url($url).'" title="'.esc_attr(__('Delete all cached image sizes for this image', 'fly-dynamic-image-resizer')).'">'.__('Delete Fly Images', 'fly-dynamic-image-resizer').'</a>';

        return $actions;
    }

    /**
     * Delete all fly images for an attachment.
     */
    public function delete_attachment_fly_images(int $attachment_id = 0): bool
    {
        if (!function_exists('WP_Filesystem')) {
            return false;
        }

        WP_Filesystem();
        global $wp_filesystem;

        return $wp_filesystem->rmdir($this->get_fly_dir($attachment_id), true);
    }

    /**
     * Delete all the fly images.
     */
    public function delete_all_fly_images(): bool
    {
        if (!function_exists('WP_Filesystem')) {
            return false;
        }

        WP_Filesystem();
        global $wp_filesystem;

        if ($wp_filesystem->rmdir($this->get_fly_dir(), true)) {
            $this->check_fly_dir();

            return true;
        }

        return false;
    }

    /**
     * Options page.
     */
    public function options_page(): void
    {
        // Check for actions
        if (
            isset($_POST['fly_nonce']) // Input var okay.
            && wp_verify_nonce(sanitize_key($_POST['fly_nonce']), 'delete_all_fly_images') // Input var okay.
        ) {
            // Delete all fly images.
            $this->delete_all_fly_images();
            echo '<div class="updated"><p>'.esc_html__('All cached images created on the fly have been deleted.', 'fly-dynamic-image-resizer').'</p></div>';
        } elseif (
            isset($_GET['delete-fly-image'], $_GET['ids'], $_GET['fly_nonce']) // Input var okay.
            && wp_verify_nonce(sanitize_key($_GET['fly_nonce']), 'delete_fly_image') // Input var okay.
        ) {
            // Delete all fly images for certain attachments.
            $ids = array_map('intval', array_map('trim', explode(',', sanitize_key($_GET['ids'])))); // Input var okay.
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $this->delete_attachment_fly_images($id);
                }
                echo '<div class="updated"><p>'.esc_html__('Deleted all fly images for this image.', 'fly-dynamic-image-resizer').'</p></div>';
            }
        }

        // Show the template
        load_template(JB_FLY_PLUGIN_PATH.'/admin/options.php');
    }

    /**
     * Add image sizes to be created on the fly.
     */
    public function add_image_size(string $size_name, int $width = 0, int $height = 0, bool|array $crop = false): bool
    {
        if (empty($size_name) || !$width || !$height) {
            return false;
        }

        $this->_image_sizes[$size_name] = [
            'size' => [$width, $height],
            'crop' => $crop,
        ];

        return true;
    }

    /**
     * Gets a previously declared image size.
     */
    public function get_image_size(string $size_name = ''): array
    {
        if (empty($size_name) || !isset($this->_image_sizes[$size_name])) {
            return [];
        }

        return $this->_image_sizes[$size_name];
    }

    /**
     * Get all declared images sizes.
     */
    public function get_all_image_sizes(): array
    {
        return $this->_image_sizes;
    }

    /**
     * Gets a dynamically generated image URL from the Fly_Images class.
     */
    public function get_attachment_image_src(int $attachment_id = 0, string|array $size = '', array|bool $crop = null): array
    {
        if ($attachment_id < 1 || empty($size)) {
            return [];
        }

        // If size is 'full', we don't need a fly image
        if ('full' === $size) {
            return wp_get_attachment_image_src($attachment_id, 'full');
        }

        // Get the attachment image
        if ($image = wp_get_attachment_metadata($attachment_id)) {

            // Filter
            if (!apply_filters('fly_mime_type', true, get_post_mime_type($attachment_id))) {
                return [];
            }

            // Determine width and height based on size
            switch (gettype($size)) {
                case 'string':
                    $image_size = $this->get_image_size($size);
                    if (empty($image_size)) {
                        return [];
                    }
                    $width = $image_size['size'][0];
                    $height = $image_size['size'][1];
                    $crop = $crop ?? $image_size['crop'];
                    break;
                case 'array':
                    $width = $size[0];
                    $height = $size[1];
                    break;
                default:
                    return [];
            }

            // Get the file path
            $fly_dir = $this->get_fly_dir($attachment_id);
            $fly_file_path = $fly_dir.DIRECTORY_SEPARATOR.$this->get_fly_file_name(basename($image['file']), $width, $height, $crop);

            // Check if file exists
            if (file_exists($fly_file_path)) {
                $image_size = getimagesize($fly_file_path);
                if (!empty($image_size)) {
                    return [
                        'src' => $this->get_fly_path($fly_file_path),
                        'width' => $image_size[0],
                        'height' => $image_size[1],
                    ];
                }

                return [];
            }

            // Check if images directory is writeable
            if (!$this->fly_dir_writable()) {
                return [];
            }

            // File does not exist, let's check if directory exists
            $this->check_fly_dir();

            // Get WP Image Editor Instance
            $image_path = apply_filters(
                'fly_attached_file',
                get_attached_file($attachment_id),
                $attachment_id,
                $size,
                $crop
            );
            $image_editor = wp_get_image_editor($image_path);
            if (!is_wp_error($image_editor)) {
                // Create new image
                $image_editor->resize($width, $height, $crop);
                $image_editor->save($fly_file_path);

                // Trigger action
                do_action('fly_image_created', $attachment_id, $fly_file_path);

                // Image created, return its data
                $image_dimensions = $image_editor->get_size();

                return [
                    'src' => $this->get_fly_path($fly_file_path),
                    'width' => $image_dimensions['width'],
                    'height' => $image_dimensions['height'],
                ];
            }
        }

        // Something went wrong
        return [];
    }

    /**
     * Get a dynamically generated image HTML from the Fly_Images class.
     *
     * Based on /wp-includes/media.php -> wp_get_attachment_image()
     */
    public function get_attachment_image(int $attachment_id = 0, array|string $size = '', bool $crop = null, array $attr = []): string
    {
        if ($attachment_id < 1 || empty($size)) {
            return '';
        }

        // If size is 'full', we don't need a fly image
        if ('full' === $size) {
            return wp_get_attachment_image($attachment_id, $size, $attr);
        }

        $html = '';
        $image = $this->get_attachment_image_src($attachment_id, $size, $crop);
        if ($image) {
            $hwstring = image_hwstring($image['width'], $image['height']);
            $size_class = $size;
            if (is_array($size_class)) {
                $size_class = implode('x', $size);
            }
            $attachment = get_post($attachment_id);
            $default_attr = [
                'src' => $image['src'],
                'class' => "attachment-$size_class",
                'alt' => trim(strip_tags(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))),
            ];
            if (empty($default_attr['alt'])) {
                $default_attr['alt'] = trim(strip_tags($attachment->post_excerpt));
            }
            if (empty($default_attr['alt'])) {
                $default_attr['alt'] = trim(strip_tags($attachment->post_title));
            }

            $attr = wp_parse_args($attr, $default_attr);
            $attr = apply_filters('fly_get_attachment_image_attributes', $attr, $attachment, $size);
            $attr = array_map('esc_attr', $attr);
            $html = rtrim("<img $hwstring");
            foreach ($attr as $name => $value) {
                $html .= " $name=".'"'.$value.'"';
            }
            $html .= ' />';
        }

        return $html;
    }

    /**
     * Get a file name based on parameters.
     */
    public function get_fly_file_name(string $file_name, string $width, string $height, bool|array $crop): string
    {
        $file_name_only = pathinfo($file_name, PATHINFO_FILENAME);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $crop_extension = '';
        if (true === $crop) {
            $crop_extension = '-c';
        } elseif (is_array($crop)) {
            $crop_extension = '-'.implode(
                    '',
                    array_map(
                        static function ($position) {
                            return $position[0];
                        },
                        $crop
                    )
                );
        }

        /**
         * Note: intval() for width and height is based on Image_Processor::resize()
         */
        return sprintf("%s-%dx%d%s.%s", $file_name_only, (int)$width, (int)$height, $crop_extension, $file_extension);
    }

    /**
     * Get the full path of an image based on its absolute path.
     */
    public function get_fly_path(string $absolute_path = ''): string
    {
        $wp_upload_dir = wp_upload_dir();
        $path = $wp_upload_dir['baseurl'].str_replace($wp_upload_dir['basedir'], '', $absolute_path);

        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    /**
     * Get the absolute path of an image based on it's full path.
     */
    public function get_fly_absolute_path(string $path = ''): string
    {
        $wp_upload_dir = wp_upload_dir();

        return $wp_upload_dir['basedir'].str_replace($wp_upload_dir['baseurl'], '', $path);
    }

    /**
     * Update Fly Dir when a blog is switched.
     *
     * @return void
     */
    public function blog_switched(): void
    {
        $this->_fly_dir = apply_filters('fly_dir_path', $this->get_fly_dir(emptyFlyPathFirst: true));
    }
}
