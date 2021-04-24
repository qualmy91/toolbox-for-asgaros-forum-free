<?php


namespace Tfaf\Inc\Base;

class Activate
{

    /**
     * Activate the plugin
     */
    public static function activate()
    {
        flush_rewrite_rules();

        // Check if the used options exists and assign an empty array if not
        $default = array();
        if (!get_option('tfaf_plugin_settings')) {
            update_option('tfaf_plugin_settings', array('version' => TFAF_VERSION, 'notification' => 0, 'notification_message' => ''));
        }

        if (!get_option('tfaf_custom_menu')) {
            update_option('tfaf_custom_menu', $default);
        }

        if (!get_option('tfaf_custom_url')) {
            update_option('tfaf_custom_url', $default);
        }

        if (!get_option('tfaf_image_upload')) {
            update_option('tfaf_custom_url', $default);
        }

    }

}