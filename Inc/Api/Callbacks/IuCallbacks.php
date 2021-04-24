<?php


namespace Tfaf\Inc\Api\Callbacks;

require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

class IuCallbacks
{

    /**
     * Sanitize the user input
     *
     * @param $input input for custom URLs
     *
     * @return array sanitized input
     */
    public function iuSanitize($input)
    {

        $output = get_option('tfaf_image_upload');

        // Check if max upload is a positive integer
        if (is_int((int)$input['max_upload']) && (int)$input['max_upload'] > 0){
            $output['max_upload'] = (int)$input['max_upload'];
        }

        if (isset($input['debug'])){
            $output['debug'] = true;
        } else {
            $output['debug'] = false;
        }

        return $output;
    }

    /**
     * Callback for Custom URl Section
     */
    public function iuSectionManager()
    {
        esc_html_e('Set a maximum size of the images that the user can upload.', 'toolbox-for-asgaros-forum');
    }

    /**
     * Callback for Custom URl Section
     */
    public function iuDebuggingSection()
    {
        esc_html_e('The Debug mode is only visible for Administratror.', 'toolbox-for-asgaros-forum');
    }


    /**
     * Callback for number Field
     *
     * @param $args arguments for number Field
     */
    public function numberField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];

        $input = get_option($option_name);
        $value = isset($input[$name]) ? $input[$name] : '';

        echo '<input type="text" class="regular-text" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="' . $value . '" placeholder="' . $args['placeholder'] . '"> ' .
            $args['field_extension'];
    }


    /**
     * Callback for a checkbox field
     *
     * @param $args arguments for a checkbox field
     */
    public function checkboxField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];

        $checkbox = get_option($option_name);
        $checked = isset($checkbox[$name]) ? $checkbox[$name] : false;


        echo '<div class="' . $classes . '"><input type="checkbox" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="1" class="" ' . ($checked ? 'checked' : '') . '><label for="' . $name .
            '"><div></div></label></div>';
    }
}