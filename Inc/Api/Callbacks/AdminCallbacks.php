<?php


namespace Tfaf\Inc\Api\Callbacks;

/**
 * Manage callbacks for subpages
 */
class AdminCallbacks
{
    public function adminDashboard()
    {
        return require_once TFAF_PLUGIN_PATH . 'template/admin.php';
    }

    public function customMenu()
    {
        return require_once TFAF_PLUGIN_PATH . 'template/custom-menu.php';
    }

    public function customUrl()
    {
        return require_once TFAF_PLUGIN_PATH . 'template/custom-url.php';
    }

    public function shortcodesUm()
    {
        return require_once TFAF_PLUGIN_PATH . 'template/ultimate-member-integration.php';
    }
    
        public function shortcodesAP()
    {
        return require_once TFAF_PLUGIN_PATH . 'template/author-page-shortcode.php';
    }

    public function profileTabUm()
    {
        return require_once TFAF_PLUGIN_PATH . 'template/profile-tab.php';
    }
     public function imageUpload()
    {
        return require_once TFAF_PLUGIN_PATH . 'template/image-upload.php';
    }

    public function autosubscribe()
        {
            return require_once TFAF_PLUGIN_PATH . 'template/autosubscribe.php';
        }

}
