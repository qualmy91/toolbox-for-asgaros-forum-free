<?php

namespace Tfaf\Inc\Base;

if (!defined('ABSPATH')) {
    exit;
}

require_once TFAF_PLUGIN_PATH . 'Inc/Api/SettingsApi.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AdminCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/IuCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

use Tfaf\Inc\Api\SettingsApi;
use Tfaf\Inc\Api\Callbacks\AdminCallbacks;
use Tfaf\Inc\Api\Callbacks\IuCallbacks;

class ImageUploadController extends BaseController
{

    public $callbacks;
    public $iu_callbacks;

    public $settings;

    public $iu_options;

    public $subpages = array();

    /**
     * Register Image Upload Controller
     */
    public function register()
    {

        // Check if the Controller is activated
        if (!$this->activated('iu_manager')) {
            return;
        }

        // Check if Asgaros Forum is activated
        if (!is_plugin_active('asgaros-forum/asgaros-forum.php')) {
            return;
        }

        $this->settings = new SettingsApi();
        $this->callbacks = new AdminCallbacks();
        $this->iu_callbacks = new IuCallbacks();

        $this->iu_options = get_option('tfaf_image_upload') ?: array();

        $this->setSettings();
        $this->setSections();
        $this->setFields();

        $this->setSubPages();
        $this->settings->addSubPages($this->subpages)->register();

        add_filter('tiny_mce_before_init', array($this, 'tinymce_settings'));
        add_filter(
            'no_texturize_tags',
            array($this, 'tfaf_no_texturize_editor_content')
        );

        add_action('wp_enqueue_scripts', array($this, 'tfaf_enqueue_scripts'));
        add_action('init', array($this, 'tfaf_handle_upload'));

        add_action('asgarosforum_filter_content_before_insert', array($this, 'replace_temp_pics'));
        add_action('tfaf_clean_temp_dir', array($this, 'clean_tmp_dir'));

    }


    /**
     * Prevent WordPress from converting quotation marks
     * inside editor textarea content.
     *
     * @param array $tags HTML tags excluded from texturization.
     * @return array
     */
    public function tfaf_no_texturize_editor_content($tags)
    {
        if (!in_array('textarea', $tags, true)) {
            $tags[] = 'textarea';
        }

        return $tags;
    }

    /**
     * Enqueue scripts for inline image upload
     */
    function tfaf_enqueue_scripts()
    {
        wp_enqueue_script(
            'tfaf_tinyMCE',
            TFAF_PLUGIN_URL . 'assets/image-upload.js',
            array('jquery'),
            filemtime(TFAF_PLUGIN_PATH . 'assets/image-upload.js'),
            false
        );

        if ($this->can_upload_forum_image()) {
            wp_localize_script('tfaf_tinyMCE', 'tfafUploadData', array(
                'nonce' => wp_create_nonce('tfaf_image_upload'),
            ));
        }
    }

    private function can_upload_forum_image()
    {
        global $asgarosforum;

        if (!is_user_logged_in() || !is_object($asgarosforum)) {
            return false;
        }

        // Older Asgaros releases expose the same permission checks as static methods.
        $permissions = isset($asgarosforum->permissions)
            ? $asgarosforum->permissions
            : 'AsgarosForumPermissions';
        $userId = get_current_user_id();
        $isBanned = array($permissions, 'isBanned');
        if (!is_callable($isBanned) || call_user_func($isBanned, $userId)) {
            return false;
        }

        if (isset($asgarosforum->options['upload_permission']) &&
            $asgarosforum->options['upload_permission'] === 'moderator') {
            $isModerator = array($permissions, 'isModerator');
            return is_callable($isModerator) && call_user_func($isModerator, $userId);
        }

        return true;
    }


    /**
     *
     * Add file browser callback to tinyMCE settings
     *
     * @param $settings
     * @return mixed
     */
    function tinymce_settings($settings)
    {

        $settings['file_browser_callback'] = 'function(field_id){tfaf_file_upload(field_id);}';

        return $settings;
    }


    /**
     * Handles uploaded images from tinyMCE and save it in tmp dir
     */
    function tfaf_handle_upload()
    {
        if (empty($_GET['tfaf_do_upload']))
            return;

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !isset($_POST['tfaf_upload_nonce']) ||
            !is_string($_POST['tfaf_upload_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tfaf_upload_nonce'])), 'tfaf_image_upload')) {
            $this->tfaf_upload_error(null, 'Invalid upload request.');
            return;
        }

        if (!$this->can_upload_forum_image()) {
            $this->tfaf_upload_error(null, 'User is not allowed to upload forum images.');
            return;
        }

        if (!isset($_FILES['tfaf_file']['name'], $_FILES['tfaf_file']['tmp_name'], $_FILES['tfaf_file']['error']) ||
            !is_string($_FILES['tfaf_file']['name']) ||
            !is_string($_FILES['tfaf_file']['tmp_name']) ||
            $_FILES['tfaf_file']['error'] !== UPLOAD_ERR_OK) {
            $this->tfaf_upload_error(null, 'Invalid file upload.');
            return;
        }

        // PHP verifies the original temporary path against its registry of HTTP uploads.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The exact path is checked with is_uploaded_file() before use.
        $tmpName = $_FILES['tfaf_file']['tmp_name'];
        if (!is_uploaded_file($tmpName)) {
            $this->tfaf_upload_error(null, 'Invalid file upload.');
            return;
        }

        $size = filesize($tmpName);
        if ($size === false || $size === 0) {
            $this->tfaf_upload_error(null, 'Unable to read the uploaded file size.');
            return;
        }

        $max_upload = isset($this->iu_options['max_upload']) ? $this->iu_options['max_upload'] : 5;
        if ($size > ($max_upload * 1048576)) {
            $this->tfaf_upload_error($max_upload);
            return;
        }

        $image = @getimagesize($tmpName);
        if (!$image || !in_array($image[2], array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG), true)) {
            $this->tfaf_upload_error(null, 'Unsupported image format.');
            return;
        }

        $file = array(
            'name' => sanitize_file_name($_FILES['tfaf_file']['name']),
            'tmp_name' => $tmpName,
            'size' => $size,
            'error' => UPLOAD_ERR_OK,
            'type' => $image['mime'],
        );

        // Create temp directory
        $uploadDir = wp_upload_dir();
        $tempUploadDir = $uploadDir['basedir'] . '/tfaf/tmp';
        if (!wp_mkdir_p($tempUploadDir)) {
            $this->tfaf_upload_error(null, 'Not able to create directory -' . $tempUploadDir);
            return;
        }


        // Define allowed mimes
        $allowedMimes = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
        );

        $fileInfo = wp_check_filetype($file['name'], $allowedMimes);
        if (empty($fileInfo['type']) || $fileInfo['type'] !== $file['type']) {
            $this->tfaf_upload_error(null, 'The filename extension does not match the image format.');
            return;
        }

        // Change upload path to temp folder
        add_filter('upload_dir', array($this, 'tfaf_upload_dir'));

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadInfo = wp_handle_upload($file, array(
            'test_form' => false,
            'mimes' => $allowedMimes,
        ));

        // Always remove the temporary upload directory filter.
        remove_filter('upload_dir', array($this, 'tfaf_upload_dir'));

        // Check whether the upload succeeded.
        if (!is_array($uploadInfo) || isset($uploadInfo['error'])) {
            $error = is_array($uploadInfo) && isset($uploadInfo['error'])
                ? $uploadInfo['error']
                : 'Unknown upload error';

            $this->tfaf_upload_error(null, 'Upload was not successful - ' . $error);
            return;
        }

        // Generate temp filename
        $year = gmdate('Y');
        $month = gmdate('m');
        $day = gmdate('d');
        $fileNumber = 1;
        $length = 6;

        do {
            $tempName = $year . $month . $day . substr(str_repeat(0, $length) . $fileNumber, -$length);
            $fileNumber++;
            $duplicates = glob("{$tempUploadDir}/{$tempName}.*");
        } while (!empty($duplicates));

        $tempName .= '.' . $fileInfo['ext'];

        // Rename File
        // Upload files are already on the local filesystem. Keep the move atomic.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
        if (!rename($uploadInfo['file'], $tempUploadDir . '/' . $tempName)) {
            $this->tfaf_upload_error(null, 'Unable to rename the File - ' . $uploadInfo['file']);
            return;
        }


        echo esc_url($uploadDir['baseurl'] . '/tfaf/tmp/' . $tempName);
        exit;
    }

    /*
     * Tmp Dir for uploads
     */
    function tfaf_upload_dir($dir)
    {
        return array(
                'path' => $dir['basedir'] . '/tfaf/tmp',
                'url' => $dir['baseurl'] . '/tfaf/tmp',
                'subdir' => '/tfaf/tmp',
            ) + $dir;
    }


    /**
     * Return upload error to tinyMCE
     * @param int $size_limit
     * @param string $error_message
     */
    function tfaf_upload_error($size_limit = null, $error_message = null)
    {
        $debugging = isset($this->iu_options['debug']) ? $this->iu_options['debug'] : false;

        if ($size_limit !== null) {
            echo("size-error[" . esc_html($size_limit) . "]");
        } else if ($error_message !== null && $debugging && current_user_can('manage_options')) {
            echo '[[tfaf_debugging_mode]]';
            echo("upload-error[" . esc_html($error_message) . "]");

        } else {
            echo("upload-error");
        }

        exit;
    }


    /**
     * Move tmp files to the final location after post is submitted
     *
     * @param $message string Message from forum post
     * @return string
     *
     */
    function replace_temp_pics($message)
    {

        preg_match_all('/\/tfaf\/tmp\/(.+)[\\\"\']/iU', $message, $images);


        // Check if images are found in post
        if (empty($images[1]))
            return $message;

        $uploadDir = wp_upload_dir();
        $baseDir = realpath($uploadDir['basedir']);
        if ($baseDir === false) {
            return $message;
        }

        $tempUploadDir = $baseDir . '/tfaf/tmp';
        if (wp_normalize_path(realpath($tempUploadDir) ?: '') !== wp_normalize_path($tempUploadDir)) {
            return $message;
        }

        $datePath = gmdate('Y/m/d');
        $finalUploadDir = $baseDir . '/tfaf/' . $datePath;

        foreach (array_unique($images[1]) as $match) {
            // Accept only the temporary image filenames generated by the uploader.
            if (!preg_match('/\A[0-9]{14}\.(jpg|jpeg|jpe|png|gif)\z/i', $match, $filenameParts)) {
                continue;
            }

            $sourceFile = $tempUploadDir . '/' . $match;
            if (is_link($sourceFile) || !is_file($sourceFile)) {
                continue;
            }

            if (!wp_mkdir_p($finalUploadDir) ||
                wp_normalize_path(realpath($finalUploadDir) ?: '') !== wp_normalize_path($finalUploadDir)) {
                return $message;
            }

            // Use an independent name for each move so concurrent posts do not reuse a sequence number.
            $fileName = wp_generate_password(32, false, false) . '.' . strtolower($filenameParts[1]);
            // Keep the validated local move atomic without filesystem credentials.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
            if (!@rename($sourceFile, $finalUploadDir . '/' . $fileName)) {
                continue;
            }

            $message = str_replace('/tfaf/tmp/' . $match, '/tfaf/' . $datePath . '/' . $fileName, $message);

            // Increase counter for notifications
            if (!isset($this->iu_options['upload_counter'])) {
                $this->iu_options['upload_counter'] = 0;
            }

            $this->iu_options['upload_counter']++;
            update_option('tfaf_image_upload', $this->iu_options);

            // Check if notification should be created
            $iu_counter =$this->iu_options['upload_counter'];

            $notifications = array(
                20 => 'iu_20',
                50 => 'iu_50',
                100 => 'iu_100',
                250 => 'iu_250',
                500 => 'iu_500'
            );

            if ( isset($notifications[$iu_counter])){
                $plugin_options = get_option('tfaf_plugin_settings');
                $plugin_options['notification_message'] = $notifications[$iu_counter];
                update_option('tfaf_plugin_settings', $plugin_options);
            }

        }


        $this->clean_tmp_dir();

        return $message;
    }

    /**
     * Clean tmp dir and delete old tmp files
     */
    function clean_tmp_dir()
    {
        $baseDir = realpath(wp_upload_dir()['basedir']);
        if ($baseDir === false) {
            return;
        }

        $uploadDir = $baseDir . '/tfaf/tmp';
        if (wp_normalize_path(realpath($uploadDir) ?: '') !== wp_normalize_path($uploadDir)) {
            return;
        }

        foreach (scandir($uploadDir) ?: array() as $name) {
            if (!preg_match('/\A[0-9]{14}\.(?:jpg|jpeg|jpe|png|gif)\z/i', $name)) {
                continue;
            }

            $file = $uploadDir . '/' . $name;
            if (!is_link($file) && is_file($file) && filemtime($file) < time() - 86400) {
                wp_delete_file($file);
            }
        }
    }

    /**
     * Set settings for Image Upload Controller
     */
    public function setSettings()
    {
        $args = array(
            array(
                'option_group' => 'tfaf_image_upload_settings',
                'option_name' => 'tfaf_image_upload',
                'callback' => array($this->iu_callbacks, 'iuSanitize')
            )
        );

        $this->settings->setSettings($args);
    }

    /**
     * Set sections for Image Upload Controller
     */
    public function setSections()
    {
        $args = array(
            array(
                'id' => 'tfaf_iu_index',
                'title' => esc_html__('Maximum image size', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->iu_callbacks, 'iuSectionManager'),
                'page' => 'tfaf_iu'
            ),
            array(
                'id' => 'tfaf_iu_debugging',
                'title' => esc_html__('Activate debugging', 'toolbox-for-asgaros-forum'),
                'callback' => array(),
                'page' => 'tfaf_iu'
            )

        );

        $this->settings->setSections($args);
    }


    /**
     * Set fields for Image Upload Controller
     */
    public function setFields()
    {

        $args = array(
            array(
                'id' => 'max_upload',
                'title' => esc_html__('Max image size', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->iu_callbacks, 'numberField'),
                'page' => 'tfaf_iu',
                'section' => 'tfaf_iu_index',
                'args' => array(
                    'option_name' => 'tfaf_image_upload',
                    'label_for' => 'max_upload',
                    'placeholder' => '5',
                    'field_extension' => esc_html__(' MB', 'toolbox-for-asgaros-forum'),
                )
            ),
            array(
                'id' => 'debug',
                'title' => esc_html__('Debug Mode', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->iu_callbacks, 'checkboxField'),
                'page' => 'tfaf_iu',
                'section' => 'tfaf_iu_debugging',
                'args' => array(
                    'option_name' => 'tfaf_image_upload',
                    'label_for' => 'debug',
                    'class' => 'ui-toggle'
                )
            )

        );

        $this->settings->setFields($args);
    }

    /**
     * Register Subpage for Admin Menu
     */
    public function setSubPages()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'toolbox_asgaros',
                'page_title' => esc_html__('Inline Image Upload', 'toolbox-for-asgaros-forum'),
                'menu_title' => esc_html__('Inline Image', 'toolbox-for-asgaros-forum'),
                'capability' => 'manage_options',
                'menu_slug' => 'tfaf_image_upload',
                'callback' => array($this->callbacks, 'imageUpload')
            )
        );
    }
}
