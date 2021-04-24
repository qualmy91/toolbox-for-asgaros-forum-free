<?php

namespace Tfaf\Inc\Base;

require_once TFAF_PLUGIN_PATH . 'Inc/Api/SettingsApi.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AdminCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AsCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

use Tfaf\Inc\Api\Callbacks\AsCallbacks;
use Tfaf\Inc\Api\SettingsApi;
use Tfaf\Inc\Api\Callbacks\AdminCallbacks;

class AutoSubscription extends BaseController
{

    public $callbacks;
    public $as_callbacks;

    public $settings;
    public $as_options;

    public $subpages = array();

    /**
     * Register the Custom URL Controller
     */
    public function register()
    {

        if (!$this->activated('ai_manager')) {
            return;
        }

        // Check if Asgaros Forum is activated
        if (!is_plugin_active('asgaros-forum/asgaros-forum.php')) {
            return;
        }

        $this->settings = new SettingsApi();

        $this->callbacks = new AdminCallbacks();
        $this->as_callbacks = new AsCallbacks();

        $this->as_options = get_option('tfaf_plugin_settings') ?: array();

        $this->setSubPages();

        // register the subpages and create menu entries
        $this->settings->addSubPages($this->subpages)->register();

        if(isset($this->as_options['as_owner'])){
            add_action('asgarosforum_after_add_topic_submit', array($this, 'auto_subscribe_owner'), 10, 2);
        }
        if(isset($this->as_options['as_reply'])){
            add_action('asgarosforum_after_add_post_submit', array($this, 'auto_subscribe_post'), 10, 2);
        }

    }


    /**
     * Autosubscribe topic owner
     */
    public function auto_subscribe_owner($postId, $topicId)
    {
        global $asgarosforum;
        $asgarosforum->notifications->subscribe_topic($topicId);

    }

    /**
     * Autosubscribe post owner to topic
     */
    public function auto_subscribe_post($postId, $topicId){
        global $asgarosforum;
        $asgarosforum->notifications->subscribe_topic($topicId);

    }

    /**
     * Set subpages for URL Custom Controller to admin menu
     */
    public function setSubPages()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'toolbox_asgaros',
                'page_title' => esc_html__('Autosubscribtion', 'toolbox-for-asgaros-forum'),
                'menu_title' => esc_html__('Autosubscribtion', 'toolbox-for-asgaros-forum'),
                'capability' => 'manage_options',
                'menu_slug' => 'tfaf_as',
                'callback' => array($this->callbacks, 'autosubscribe')
            )
        );
    }

}