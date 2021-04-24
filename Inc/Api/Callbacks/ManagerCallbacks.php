<?php


namespace Tfaf\Inc\Api\Callbacks;

require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

use Tfaf\Inc\Base\BaseController;

class ManagerCallbacks extends BaseController
{

    /**
     * Sanitize input of checkbox
     *
     * @param $input
     *
     * @return array sanitized input
     */
    public function checkboxSanitize($input)
    {
        $output = get_option('tfaf_plugin_settings');

        if (isset($input['page'])){
            $output['as_owner'] =  $input['as_owner'];
            $output['as_reply'] =  $input['as_reply'];
        } else {
            foreach ($this->managers as $key => $value) {
                $output[$key] = isset($input[$key]);
            }
        }


        return $output;
    }

    /**
     * Callback for admin section
     */
    public function adminSectionManager()
    {
        esc_html_e('Manage the tools of this Plugin by activating the checkboxes in the following list.', 'toolbox-for-asgaros-forum');
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

        $disabled = '';
        $warning = '';
        if (($name == 'um_shortcode_manager' || $name == 'um_tab_manager') && !is_plugin_active('ultimate-member/ultimate-member.php')) {
            $disabled = 'disabled';
            $warning = '<span class="tfaf-warning">' . esc_html__('Ultimate Member must be installed to use this shortcodes', 'toolbox-for-asgaros-forum') . '</span>';
        }

        if (!is_plugin_active('asgaros-forum/asgaros-forum.php')) {
            $disabled = 'disabled';
            $warning = ($warning != '') ? $warning . '<br>' : '';
            $warning .= '<span class="tfaf-warning">' . esc_html__('Asgaros Forum must be installed to use this tool', 'toolbox-for-asgaros-forum') . '</span>';
        }

        echo '<div class="' . $classes . '"><input type="checkbox" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="1" ' . ($checked ? 'checked' : '') . ' ' . $disabled . '><label for="' . $name .
            '"><div></div></label>' . $warning . '</div>';
    }

}