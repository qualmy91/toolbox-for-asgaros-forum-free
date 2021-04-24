<?php

namespace Tfaf\Inc\Base;

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

        add_action('wp_enqueue_scripts', array($this, 'tfaf_enqueue_scripts'));
        add_action('init', array($this, 'tfaf_handle_upload'));

        add_action('asgarosforum_filter_content_before_insert', array($this, 'replace_temp_pics'));
        add_action('tfaf_clean_temp_dir', array($this, 'clean_tmp_dir'));

    }


    /**
     * Enqueue scripts for inline image upload
     */
    function tfaf_enqueue_scripts()
    {
        wp_enqueue_script('tfaf_tinyMCE', TFAF_PLUGIN_URL . '/assets/image-upload.js', array('jquery'));
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

        // Check capabilities
        if (!is_user_logged_in()) {
            $this->tfaf_upload_error(null, 'User is not logged in!');
            return;
        }

        // Check file upload
        if (!isset($_FILES['tfaf_file'])) {
            $this->tfaf_upload_error(null, 'File is not set');
            return;
        }

        if (!@getimagesize($_FILES['tfaf_file']['tmp_name'])) {
            $this->tfaf_upload_error(null, "Can't calculate Image size. Probably a wrong format.");
            return;
        }

        // Check Filesize
        $max_upload = isset($this->iu_options['max_upload']) ? $this->iu_options['max_upload'] : 5;
        if ($_FILES['tfaf_file']['size'] > ($max_upload * 1048576)) {
            $this->tfaf_upload_error($max_upload);
            return;
        }

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

        $fileInfo = wp_check_filetype(basename($_FILES['tfaf_file']['name']));

        if (empty($fileInfo['type']))
            $this->tfaf_upload_error(null, 'error while checking the filetype');


        // Change upload path to temp folder
        add_filter('upload_dir', array($this, 'tfaf_upload_dir'));

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadInfo = wp_handle_upload($_FILES['tfaf_file'], array(
            'test_form' => false,
            'mimes' => $allowedMimes,
        ));

        // Check if upload was successful
        if (!$uploadInfo || isset($uploadInfo['error'])) {
            $this->tfaf_upload_error(null, 'Upload was not successful - ' . $uploadInfo['error']);
            return;
        }

        // Remove temp path
        remove_filter('upload_dir', 'tfaf_upload_dir');

        // Generate temp filename
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $fileNumber = 1;
        $length = 6;

        do {
            $tempName = $year . $month . $day . substr(str_repeat(0, $length) . $fileNumber, -$length);
            $fileNumber++;
            $duplicates = glob("{$tempUploadDir}/{$tempName}.*");
        } while (!empty($duplicates));

        $tempName .= '.' . $fileInfo['ext'];

        // Rename File
        if (!rename($uploadInfo['file'], $tempUploadDir . '/' . $tempName)) {
            $this->tfaf_upload_error(null, 'Unable to rename the File - ' . $uploadInfo['file']);
            return;
        }


        @unlink($_FILES['tfaf_file']['tmp_name']);
        echo($uploadDir['baseurl'] . '/tfaf/tmp/' . $tempName);
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
            echo("size-error[" . $size_limit . "]");
        } else if ($error_message !== null && $debugging && current_user_can('manage_options')) {
            echo '[[tfaf_debugging_mode]]';
            echo("upload-error[" . $error_message . "]");

        } else {
            echo("upload-error");
        }
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

        // Create upload folder if not exist
        $year = date('Y');
        $month = date('m');
        $day = date('d');

        $finalUploadDir = wp_upload_dir()['basedir'] . '/tfaf/' . $year . '/' . $month . '/' . $day;
        $tempUploadDir = wp_upload_dir()['basedir'] . '/tfaf/tmp';
        wp_mkdir_p($finalUploadDir);

        // Generate Filename
        $fileNumber = 1;
        $length = 6;

        do {
            $fileName = $year . $month . $day . substr(str_repeat(0, $length) . $fileNumber, -$length);
            $fileNumber++;
            $duplicates = glob("{$finalUploadDir}/{$fileName}.*");
        } while (!empty($duplicates));

        foreach (array_unique($images[1]) as $match) {
            if (strpos($match, '/') || strpos($match, '\\')) {
                continue;
            }

            $fileName = $year . $month . $day . substr(str_repeat(0, $length) . ($fileNumber - 1), -$length);
            $fileNumber++;

            // Add file extension
            $dotPos = strrpos($match, '.');
            if ($dotPos)
                $fileName .= substr($match, $dotPos);

            @rename($tempUploadDir . '/' . $match, $finalUploadDir . '/' . $fileName);

            // replace path in post
            $message = str_replace('/tfaf/tmp/' . $match, '/tfaf/' . $year . '/' . $month . '/' . $day . '/' . $fileName, $message);

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
        $uploadDir = wp_upload_dir()['basedir'] . '/tfaf/tmp';

        foreach (scandir($uploadDir) as $file) {
            $file = $uploadDir . '/' . $file;
            if (is_file($file) && filemtime($file) < time() - 86400)
                @unlink($file);
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