<?php


namespace Tfaf\Inc\Base;

require_once TFAF_PLUGIN_PATH . 'Inc/Api/SettingsApi.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AdminCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/CmeCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

use Tfaf\Inc\Api\Callbacks\CmeCallbacks;
use Tfaf\Inc\Api\SettingsApi;
use Tfaf\Inc\Api\Callbacks\AdminCallbacks;

class CustomMenuController extends BaseController
{

    public $callbacks;
    public $cme_callbacks;

    public $settings;

    public $custom_menu_entries = array();

    public $subpages = array();

    /**
     * Register the Custom Menu Controller
     */
    public function register()
    {

        // Check if the Custom Menu Controller is activated
        if (!$this->activated('cme_manager')) {
            return;
        }

        // Check if Asgaros Forum is activated
        if (!is_plugin_active('asgaros-forum/asgaros-forum.php')) {
            return;
        }


        $this->settings = new SettingsApi();
        $this->callbacks = new AdminCallbacks();
        $this->cme_callbacks = new CmeCallbacks();

        $this->custom_menu_entries = get_option('tfaf_custom_menu') ?: array();

        $this->setSubPages();

        $this->setSettings();
        $this->setSections();
        $this->setFields();

        $this->settings->addSubPages($this->subpages)->register();


        //   check if header should be hidden
        if (isset(get_option('tfaf_custom_menu_settings')['hide_header'])) {
            add_filter ( 'asgarosforum_filter_show_header', array($this, 'removeHeader'));
            return;
        }

        // Check if menu should be hidden
        if (isset(get_option('tfaf_custom_menu_settings')['hide_menu'])) {
            add_filter ( 'asgarosforum_filter_header_menu', array($this, 'removeHeaderMenu'));
            return;
        }


        // check if some custom menu entries are set
        if (!empty($this->custom_menu_entries)) {
            if (isset(get_option('tfaf_custom_menu_settings')['replace_menu'])){
                add_filter ( 'asgarosforum_filter_header_menu', array($this, 'replaceHeaderMenu'));
            }else{
                add_filter('asgarosforum_custom_header_menu', array($this, 'addHeaderMenu'));
            }
        }
    }

    // Replace Header Menu
    function replaceHeaderMenu( $menu_entries)
    {
        $custom_menu_entries = $this->custom_menu_entries;

        foreach ($custom_menu_entries as $key => $custom_menu_entry){

            // Check login status
            if (isset($custom_menu_entry['menu_only_users']) && $custom_menu_entry['menu_only_users'] == "1") {
                $login_status = 1;
            } elseif (isset($custom_menu_entry['menu_only_users']) && $custom_menu_entry['menu_only_users'] == "2") {
                $login_status = 2;
            } else {
                $login_status = 0;
            }

           $custom_menu_entries[$key]['menu_login_status'] = $login_status;

            // Check for default links
            $menu_url = $custom_menu_entry['menu_url'];

            $pattern_found = preg_match('/\[(.+)\]/', $menu_url, $url_pattern);
            if ($pattern_found === 1 ){
                if (array_key_exists($url_pattern[1], $menu_entries) && isset($menu_entries[$url_pattern[1]]['menu_url'])){
                    $custom_menu_entries[$key]['menu_url'] = $menu_entries[$url_pattern[1]]['menu_url'];
                }else{
                    $redirect_url = preg_match('/\[(.+)\=(.+)\]/', $menu_url, $url_pattern);
                    if ($redirect_url === 1 && $url_pattern[1] == 'logout'){
                        $custom_menu_entries[$key]['menu_url'] = wp_logout_url( home_url() . '/'. $url_pattern[2]);
                    }
                }
            }

        }

        return $custom_menu_entries;
    }


    // remove Header Menu
    function removeHeaderMenu( $menu_entries) {
        wp_enqueue_style('tfaf_hide_header', TFAF_PLUGIN_URL . '/assets/hide-header.css');
        return array();
    }


    // remove Header
    function removeHeader( $show_header ) {
        return false;
    }


    // Add all the custom menu entries to the header menu
    public function addHeaderMenu()
    {

        foreach ($this->custom_menu_entries as $custom_menu_entry) {
            // check the url
            if ($custom_menu_entry['menu_url'][0] == '['){
                $menu_url = home_url();
            }else{
                $menu_url = esc_url($custom_menu_entry['menu_url']);
            }
            $menu_class = (isset($custom_menu_entry['menu_class'])) ? $custom_menu_entry['menu_class'] : '';

            if ($menu_url[0] == '/') {
                $menu_url = get_home_url() . $menu_url;
            }

            // Check login status
            if (isset($custom_menu_entry['menu_only_users']) && $custom_menu_entry['menu_only_users'] == "1") {
                $show_entry = is_user_logged_in();
            } elseif (isset($custom_menu_entry['menu_only_users']) && $custom_menu_entry['menu_only_users'] == "2") {
                $show_entry = ! is_user_logged_in();
            } else {
                $show_entry = true;
            }

            // Check if user is logged in or has to be locked in
            if ($show_entry) {
                $new_tab = (isset($custom_menu_entry['menu_new_tab'])) ? ' target="_blank"' : '';
                echo '<a class="'. $menu_class . '" href="' . $menu_url . '"' . $new_tab . '>' .
                    esc_html($custom_menu_entry['menu_link_text']) . '</a>';
            }
        }
    }

    /**
     * Set settings for Custom Menu Controller
     */
    public function setSettings()
    {
        $args = array(
            array(
                'option_group' => 'tfaf_custom_menu_settings',
                'option_name' => 'tfaf_custom_menu',
                'callback' => array($this->cme_callbacks, 'cmeSanitize')
            ),
            array(
                'option_group' => 'tfaf_custom_menu_general_settings',
                'option_name' => 'tfaf_custom_menu_settings',
                'callback' => array($this->cme_callbacks, 'cmeSettingsSanitize')
            )
        );

        $this->settings->setSettings($args);
    }

    /**
     * Set section for Custom Menu Controller
     */
    public function setSections()
    {
        $args = array(
            array(
                'id' => 'tfaf_cme_index',
                'title' => esc_html__('Custom Menu Entry', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'cmeEditSection'),
                'page' => 'tfaf_cme'
            ),
            array(
                'id' => 'tfaf_cme_settings',
                'title' => esc_html__('Custom Menu Entry', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'cmeSettingsSection'),
                'page' => 'tfaf_cme_settings'
            )
        );

        $this->settings->setSections($args);
    }

    /**
     * Set fields for Custom Menu Controller
     */
    public function setFields()
    {

        $args = array(
            array(
                'id' => 'menu_position',
                'title' => esc_html__('Menu Position', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'position'),
                'page' => 'tfaf_cme',
                'section' => 'tfaf_cme_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu',
                    'label_for' => 'menu_position',
                    'placeholder' => ''
                )
            ),
            array(
                'id' => 'menu_link_text',
                'title' => esc_html__('Link Text', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'textField'),
                'page' => 'tfaf_cme',
                'section' => 'tfaf_cme_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu',
                    'label_for' => 'menu_link_text',
                    'required' => 'required',
                    'placeholder' => esc_html__('e.g. User Guidelines', 'toolbox-for-asgaros-forum')
                )
            ),
            array(
                'id' => 'menu_url',
                'title' => esc_html__('Url', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'urlField'),
                'page' => 'tfaf_cme',
                'section' => 'tfaf_cme_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu',
                    'label_for' => 'menu_url',
                    'placeholder' => esc_html__('e.g. /guidelines or https://www.google.de', 'toolbox-for-asgaros-forum')
                )
            ),
            array(
                'id' => 'menu_class',
                'title' => esc_html__('Menu Class', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'textField'),
                'page' => 'tfaf_cme',
                'section' => 'tfaf_cme_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu',
                    'label_for' => 'menu_class',
                    'placeholder' => esc_html__('e.g. home-link', 'toolbox-for-asgaros-forum')
                )
            ),
            array(
                'id' => 'menu_new_tab',
                'title' => esc_html__('Open in new Tab', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'checkboxField'),
                'page' => 'tfaf_cme',
                'section' => 'tfaf_cme_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu',
                    'label_for' => 'menu_new_tab',
                    'class' => 'ui-toggle'
                )
            ),
            array(
                'id' => 'menu_only_users',
                'title' => esc_html__('Show menu entry to', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'radioField'),
                'page' => 'tfaf_cme',
                'section' => 'tfaf_cme_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu',
                    'label_for' => 'menu_only_users',
                )
            ),
            array(
                'id' => 'replace_menu',
                'title' => esc_html__('Replace Forum Menu', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'settingsCheckboxField'),
                'page' => 'tfaf_cme_settings',
                'section' => 'tfaf_cme_settings',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu_settings',
                    'label_for' => 'replace_menu',
                    'class' => 'ui-toggle'
                )
            ),
            array(
                'id' => 'hide_menu',
                'title' => esc_html__('Hide Forum Menu', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'settingsCheckboxField'),
                'page' => 'tfaf_cme_settings',
                'section' => 'tfaf_cme_settings',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu_settings',
                    'label_for' => 'hide_menu',
                    'class' => 'ui-toggle'
                )
            ),
            array(
                'id' => 'hide_header',
                'title' => esc_html__('Hide forum header (menu + breadcrumbs)', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cme_callbacks, 'settingsCheckboxField'),
                'page' => 'tfaf_cme_settings',
                'section' => 'tfaf_cme_settings',
                'args' => array(
                    'option_name' => 'tfaf_custom_menu_settings',
                    'label_for' => 'hide_header',
                    'class' => 'ui-toggle'
                )
            ),
        );

        $this->settings->setFields($args);
    }

    /**
     * Set subpages for Custom Menu Controller to admin menu
     */
    public function setSubPages()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'toolbox_asgaros',
                'page_title' => esc_html__('Custom Header Menu', 'toolbox-for-asgaros-forum'),
                'menu_title' => esc_html__('Custom Menu', 'toolbox-for-asgaros-forum'),
                'capability' => 'manage_options',
                'menu_slug' => 'tfaf_custom_menu',
                'callback' => array($this->callbacks, 'customMenu')
            )
        );
    }

}