<?php
/**
 *  Trigger this file on Plugin uninstall
 *
 */

// die, if file is not called by Wordpress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Delete all options
delete_option('tfaf_plugin_settings');
delete_option('tfaf_custom_menu');
delete_option('tfaf_custom_url');
