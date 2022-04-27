<?php

namespace Tfaf\Inc\Base;

class BaseController
{

    public $managers = array();

    /**
     * BaseController constructor.
     *
     * Defines all the Managers that are used and assign the label for the admin dashboard
     *
     */
    public function __construct()
    {
        $this->managers = array(
            'cme_manager' => esc_html__('Activate Custom Menu Entry Manager', 'toolbox-for-asgaros-forum'),
            'cu_manager' => esc_html__('Activate Custom URL Manager', 'toolbox-for-asgaros-forum'),
            'um_shortcode_manager' => esc_html__('Activate Integration for Ultimate Member', 'toolbox-for-asgaros-forum'),
            'ap_shortcode_manager' => esc_html__('Activate Shortcodes for WP Author Page', 'toolbox-for-asgaros-forum'),
            'iu_manager' => esc_html__('Activate Inline Image Upload', 'toolbox-for-asgaros-forum'),
            'ai_manager' => esc_html__('Activate Autosubscribtion', 'toolbox-for-asgaros-forum'),
        );
    }

    /**
     * Check if a manager is activated
     *
     * @param string $key name of the manager to check
     *
     * @return bool
     */
    public function activated($key)
    {
        $option = get_option('tfaf_plugin_settings');

        return isset($option[$key]) ? $option[$key] : false;
    }
}
