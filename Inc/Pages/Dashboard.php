<?php


namespace Tfaf\Inc\Pages;

require_once TFAF_PLUGIN_PATH . 'Inc/Api/SettingsApi.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AdminCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/ManagerCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AsCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

use Tfaf\Inc\Api\Callbacks\AsCallbacks;
use Tfaf\Inc\Api\SettingsApi;
use Tfaf\Inc\Api\Callbacks\AdminCallbacks;
use Tfaf\Inc\Api\Callbacks\ManagerCallbacks;
use Tfaf\Inc\Base\BaseController;

class Dashboard extends BaseController
{

    public $settings;
    public $callbacks;
    public $callbacks_mngr;
    public $as_callbacks;
    public $pages = array();

    /**
     * Register the Dashboard
     */
    public function register()
    {
        $this->settings = new SettingsApi();

        $this->callbacks = new AdminCallbacks();
        $this->callbacks_mngr = new ManagerCallbacks();

        $this->setPages();

        $this->setSections();
        $this->setSettings();
        $this->setFields();

        if ($this->activated('ai_manager')) {
            $this->registerAs();
        }

        $this->settings->addPages($this->pages)->withSubPage('Dashboard')->register();
    }

    /**
     * Set the main plugin page at the admin menu
     */
    public function setPages()
    {
        $this->pages = array(
            array(
                'page_title' => esc_html__('Toolbox for Asgaros', 'toolbox-for-asgaros-forum'),
                'menu_title' => esc_html__('Toolbox for Asgaros', 'toolbox-for-asgaros-forum'),
                'capability' => 'manage_options',
                'menu_slug' => 'toolbox_asgaros',
                'callback' => array($this->callbacks, 'adminDashboard'),
                'icon_url' => 'dashicons-admin-tools',
                'position' => 110
            )
        );
    }

    /**
     * Set the settings for the Dashboard
     */
    public function setSettings()
    {
        $args = array(
            array(
                'option_group' => 'tfaf_plugin_settings',
                'option_name' => 'tfaf_plugin_settings',
                'callback' => array($this->callbacks_mngr, 'checkboxSanitize')
            )
        );

        $this->settings->setSettings($args);
    }

    /**
     * Set the settings for the Dashboard
     */
    public function setSections()
    {
        $args = array(
            array(
                'id' => 'tfaf_admin_index',
                'title' => esc_html__('Tool Manager', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->callbacks_mngr, 'adminSectionManager'),
                'page' => 'tfaf_plugin'
            )
        );

        $this->settings->setSections($args);
    }

    /**
     * Set the fields for the Dashboard
     */
    public function setFields()
    {
        $args = array();

        foreach ($this->managers as $key => $value) {

            $args[] = array(
                'id' => $key,
                'title' => $value,
                'callback' => array($this->callbacks_mngr, 'checkboxField'),
                'page' => 'tfaf_plugin',
                'section' => 'tfaf_admin_index',
                'args' => array(
                    'option_name' => 'tfaf_plugin_settings',
                    'label_for' => $key,
                    'class' => 'ui-toggle'
                )
            );
        }

        $this->settings->setFields($args);
    }

    /**
     * Register Fields and Sections for Autosubscription manager
     */
    private function registerAs()
    {
        $this->as_callbacks = new AsCallbacks();

        $sections = array(
            'id' => 'tfaf_as',
            'title' => esc_html__('Autosubscribtion Manager', 'toolbox-for-asgaros-forum'),
            'callback' => array($this->as_callbacks, 'asSectionManager'),
            'page' => 'tfaf_as'
        );


        $fields = array(
            array(
                'id' => 'as_owner',
                'title' => esc_html__('User creates a topic', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->as_callbacks, 'checkboxField'),
                'page' => 'tfaf_as',
                'section' => 'tfaf_as',
                'args' => array(
                    'option_name' => 'tfaf_plugin_settings',
                    'label_for' => 'as_owner',
                    'placeholder' => '',
                    'url_extension' => '',
                    'class' => 'ui-toggle'
                )
            ), array(
                'id' => 'as_reply',
                'title' => esc_html__('User replies in a topic', 'toolbox-for-asgaros-forum'),
                'callback' => array($this->as_callbacks, 'checkboxField'),
                'page' => 'tfaf_as',
                'section' => 'tfaf_as',
                'args' => array(
                    'option_name' => 'tfaf_plugin_settings',
                    'label_for' => 'as_reply',
                    'placeholder' => '',
                    'url_extension' => '',
                    'class' => 'ui-toggle'
                )
            )
        );

        $this->settings->addSections($sections);

        $this->settings->addFields($fields);
    }
}

