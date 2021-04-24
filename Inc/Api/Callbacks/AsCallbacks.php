<?php


namespace Tfaf\Inc\Api\Callbacks;

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

        echo '<div class="' . $classes . '"><input type="checkbox" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="1" ' . ($checked ? 'checked' : '') . '><label for="' . $name .
            '"><div></div></label></div>';
    }

}