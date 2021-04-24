<?php

namespace Tfaf\Inc\Base;

require_once TFAF_PLUGIN_PATH . 'Inc/Api/SettingsApi.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AdminCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/CuCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

use Tfaf\Inc\Api\Callbacks\CuCallbacks;
use Tfaf\Inc\Api\SettingsApi;
use Tfaf\Inc\Api\Callbacks\AdminCallbacks;
use WP_User;

class CustomUrlController extends BaseController
{

    public $callbacks;
    public $cu_callbacks;

    public $settings;

    public $custom_url_entries = array();

    public $subpages = array();

    /**
     * Register the Custom URL Controller
     */
    public function register()
    {

        if (!$this->activated('cu_manager')) {
            return;
        }

        // Check if Asgaros Forum is activated
        if (!is_plugin_active('asgaros-forum/asgaros-forum.php')) {
            return;
        }

        $this->settings = new SettingsApi();

        $this->callbacks = new AdminCallbacks();
        $this->cu_callbacks = new CuCallbacks();

        $this->custom_url_entries = get_option('tfaf_custom_url') ?: array();

        $this->setSubPages();

        $this->setSettings();
        $this->setSections();
        $this->setFields();

        // register the subpages and create menu entries
        $this->settings->addSubPages($this->subpages)->register();

        // Map the option keys with the filter functions
        $option_keys = array(
            'login_url' => ['login_url', 3],
            'register_url' => ['register_url', 1],
            'member_list_url' => ['asgarosforum_filter_members_link', 1],
            'profile_url' => ['asgarosforum_filter_profile_link', 2],
            'edit_profile_url' => ['edit_profile_url', 3]
        );

        // Add Filters for all option keys
        foreach ($option_keys as $key => $value) {

            // Check if a custom value is set by the user
            $custom_url = isset($this->custom_url_entries[$key]) ? $this->custom_url_entries[$key] : '';

            if ($custom_url != '') {
                add_filter($value[0], array($this, 'replace_' . $key), 10, $value[1]);
            }

        }
    }

    /**
     * Return the custom url for the login page
     *
     * @param $login_url actual url for login page
     * @param $redirect actual url for redirect after login
     * @param $force_reauth value if reauthentication will be forced
     *
     * @return string url to set as custom url
     */
    public function replace_login_url($login_url, $redirect, $force_reauth)
    {
        return home_url($this->custom_url_entries['login_url']);
    }

    /**
     * Return the custom url for the register page
     *
     * @param $register_url actual url for register page
     *
     * @return string url to set as custom url
     */
    public function replace_register_url($register_url)
    {
        return home_url($this->custom_url_entries['register_url']);
    }

    /**
     * Return the custom url for the members list
     *
     * @param $members_list_url actual url for members list
     *
     * @return string url to set as custom url
     */
    public function replace_member_list_url($members_list_url)
    {
        return home_url($this->custom_url_entries['member_list_url']);
    }

    /**
     * Return the custom url for user profiles and add the username to the url
     *
     * @param $profile_url actual url for user profile
     * @param WP_User $user_object userobject of the user for the profile
     *
     * @return string url to set as custom url
     */
    public function replace_profile_url($profile_url, $user_object)
    {
        $replace_array = [
            "(--user-id--)" => $user_object->ID,
            "(--username--)" => $user_object->user_nicename,
        ];
        $url = str_replace(array_keys($replace_array), array_values($replace_array), $this->custom_url_entries['profile_url']);
        return home_url($url);
    }

    /**
     * Return the custom url for a user profile edit page and add the user id
     *
     * @param $url actual url for edit user profile
     * @param $user_id id of the user for the profile edit page
     * @param $scheme
     *
     * @return string url to set as custom url
     */
    public function replace_edit_profile_url($url, $user_id, $scheme)
    {
        $user_object = get_user_by('id', $user_id);

        $replace_array = [
            "(--user-id--)" => $user_id,
            "(--username--)" => $user_object->user_nicename,
        ];

        $url = str_replace(array_keys($replace_array), array_values($replace_array), $this->custom_url_entries['edit_profile_url']);
        return home_url($url);
    }


    /**
     * Set settings for Custom URL Controller
     */
    public function setSettings()
    {
        $args = array(
            array(
                'option_group' => 'tfaf_custom_url_settings',
                'option_name' => 'tfaf_custom_url',
                'callback' => array($this->cu_callbacks, 'cuSanitize')
            )
        );

        $this->settings->setSettings($args);
    }

    /**
     * Set sections for Custom URL Controller
     */
    public function setSections()
    {
        $args = array(
            array(
                'id' => 'tfaf_cu_index',
                'title' => esc_html__('Custom URL Manager', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cu_callbacks, 'cuSectionManager'),
                'page' => 'tfaf_cu'
            )
        );

        $this->settings->setSections($args);
    }

    /**
     * Set fields for Custom URL Controller
     */
    public function setFields()
    {

        $args = array(
            array(
                'id' => 'login_url',
                'title' => esc_html__('Login URL (WordPress)', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cu_callbacks, 'urlField'),
                'page' => 'tfaf_cu',
                'section' => 'tfaf_cu_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_url',
                    'label_for' => 'login_url',
                    'placeholder' => '',
                    'url_extension' => ''
                )
            ),
            array(
                'id' => 'register_url',
                'title' => esc_html__('Register URL (WordPress)', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cu_callbacks, 'urlField'),
                'page' => 'tfaf_cu',
                'section' => 'tfaf_cu_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_url',
                    'label_for' => 'register_url',
                    'placeholder' => '',
                    'url_extension' => ''
                )
            ),

            array(
                'id' => 'member_list_url',
                'title' => esc_html__('Member List URL', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cu_callbacks, 'urlField'),
                'page' => 'tfaf_cu',
                'section' => 'tfaf_cu_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_url',
                    'label_for' => 'member_list_url',
                    'placeholder' => '',
                    'url_extension' => ''
                )
            ),

            array(
                'id' => 'profile_url',
                'title' => esc_html__('Profile URL', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cu_callbacks, 'urlField'),
                'page' => 'tfaf_cu',
                'section' => 'tfaf_cu_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_url',
                    'label_for' => 'profile_url',
                    'placeholder' => '',
                    'url_extension' => esc_html__('Use "(--user-id--)" or "(--username--)" to insert the user id or username in the slug', 'toolbox-for-asgaros-forum')
                )
            ),

            array(
                'id' => 'edit_profile_url',
                'title' => esc_html__('Edit Profile URL', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->cu_callbacks, 'urlField'),
                'page' => 'tfaf_cu',
                'section' => 'tfaf_cu_index',
                'args' => array(
                    'option_name' => 'tfaf_custom_url',
                    'label_for' => 'edit_profile_url',
                    'placeholder' => '',
                    'url_extension' => esc_html__('Use "(--user-id--)" or "(--username--)" to insert the user id or username in the slug', 'toolbox-for-asgaros-forum')
                )
            ),

        );

        $this->settings->setFields($args);
    }

    /**
     * Set subpages for URL Custom Controller to admin menu
     */
    public function setSubPages()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'toolbox_asgaros',
                'page_title' => esc_html__('Custom URL', 'toolbox-for-asgaros-forum'),
                'menu_title' => esc_html__('Custom URL', 'toolbox-for-asgaros-forum'),
                'capability' => 'manage_options',
                'menu_slug' => 'tfaf_custom_url',
                'callback' => array($this->callbacks, 'customUrl')
            )
        );
    }

}