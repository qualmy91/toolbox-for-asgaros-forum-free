<?php


namespace Tfaf\Inc\Api\Callbacks;

if (!defined('ABSPATH')) {
    exit;
}

require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

use Tfaf\Inc\Base\BaseController;

class AsCallbacks extends BaseController
{

    /**
     * Callback for admin section
     */
    public function asSectionManager()
    {
        esc_html_e('Subscribe Users automatically for a topic.', 'toolbox-for-asgaros-forum');
    }

    /**
     * Callback to render a checkbox
     *
     * @param $args arguments from checkbox field
     */
    public function checkboxField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];
        $checkbox = get_option($option_name);
        $checked = isset($checkbox[$name]) ? ($checkbox[$name] ? true : false) : false;

        echo '<div class="' . esc_attr($classes) . '"><input type="checkbox" id="' . esc_attr($name) . '" name="' . esc_attr($option_name) . '[' .
            esc_attr($name) . ']" value="1" ' . ($checked ? 'checked' : '') . '><label for="' . esc_attr($name) .
            '"><div></div></label></div>';
    }

}