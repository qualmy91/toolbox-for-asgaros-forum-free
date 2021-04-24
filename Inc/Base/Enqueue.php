<?php

namespace Tfaf\Inc\Base;

require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

use Tfaf\Inc\Base\BaseController;


class Enqueue extends BaseController
{
    /**
     * Register the Enqueue Class and set the required actions to enqueue all the scripts
     */
    public function register()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin'));

        // Enqueue the Asgaros style only if the Custom Menu is activated
        if ($this->activated('cme_manager')) {
            add_action('asgarosforum_enqueue_css_js', array($this, 'enqueue_asgaros'));
        }

    }

    /**
     * Enqueues all the styles and scripts for the admin page
     */
    function enqueue_admin()
    {
        wp_enqueue_style('mypluginstyle', TFAF_PLUGIN_URL . '/assets/mystyle.css');
        wp_enqueue_script('mypluginscript', TFAF_PLUGIN_URL . '/assets/admin.js');
    }

    /**
     * Enqueues style for the Asgaros Forum in the frontend
     */
    function enqueue_asgaros()
    {
        wp_enqueue_style('asgaros', TFAF_PLUGIN_URL . '/assets/asgaros.css');
    }
}