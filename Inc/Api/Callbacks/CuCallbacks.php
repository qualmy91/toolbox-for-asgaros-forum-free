<?php


namespace Tfaf\Inc\Api\Callbacks;

require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

class CuCallbacks
{

    /**
     * Sanitize the user input
     *
     * @param $input input for custom URLs
     *
     * @return array sanitized input
     */
    public function cuSanitize($input)
    {
        $output = [];
        foreach ($input as $key => $value) {
            // remove whitespace
            $value = str_replace(' ', '', $value);
            $output[$key] = esc_url_raw($value);
        }

        return $output;
    }

    /**
     * Callback for Custom URl Section
     */
    public function cuSectionManager()
    {
        esc_html_e('Enter a Page Slug (e.g. "/my-new-login") to change the URL to a custom page.', 'toolbox-for-asgaros-forum');
    }


    /**
     * Callback for URL Field
     *
     * @param $args arguments for Url Field
     */
    public function urlField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];

        $input = get_option($option_name);
        $value = isset($input[$name]) ? $input[$name] : '';

        echo '<input type="text" class="regular-text" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="' . $value . '" placeholder="' . $args['placeholder'] . '" pattern="^/.+" > ' .
            $args['url_extension'];
    }
}