<?php


namespace Tfaf\Inc\Api\Callbacks;

require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';

class CmeCallbacks
{

    /**
     * Callback for Custom Menu Entry Section
     */
    public function cmeSection()
    {
        echo 'Manage your custom menu type.';
    }

    /**
     * Callback for Custom Menu Entry Section for editing a single Entry
     */
    public function cmeEditSection()
    {
        echo 'Create a new custom menu entry. You can change the position of the new entry at the overview after adding it.';
    }

    /**
     * Callback for Custom Menu Entry Replace
     */
    public function cmeSettingsSection()
    {
        echo 'If you activate this function the header menu of Asgaros Forum will be replaced by the selection above.';
    }

    /**
     * Sanitize the user input from the settings tab
     *
     * @param $input
     *
     * @return array
     */
    public function cmeSettingsSanitize($input)
    {
        $output = get_option('tfaf_custom_menu_settings');

        // Sanitize Replace Menu
        if (isset($input['replace_menu'])) {
            $output['replace_menu'] = 1;
        } else {
            unset($output['replace_menu']);
        }

        // sanitize hide header
        if (isset($input['hide_header'])) {
            $output['hide_header'] = 1;
        } else {
            unset($output['hide_header']);
        }

        // sanitize hide menu
        if (isset($input['hide_menu'])) {
            $output['hide_menu'] = 1;
        } else {
            unset($output['hide_menu']);
        }


        return $output;
    }


    /**
     * Sanitize the user input and add the input to the options array
     *
     * @param $input single menu entry
     *
     * @return array with all menu entries
     */
    public function cmeSanitize($input)
    {
        // get saved menu entries
        $output = get_option('tfaf_custom_menu');

        // delete menu entry
        if (isset($_POST["remove"])) {
            unset($output[$_POST["remove"]]);

            // Only order array if not empty
            if (!empty($output)) {
                $ordered_output[1] = "";
                // reorder positions
                $keys = array_keys($output);
                for ($position = 1; $position <= count($output); $position++) {
                    $ordered_output[$position] = $output[$keys[$position - 1]];
                    $ordered_output[$position]['menu_position'] = $position;
                }

                return $ordered_output;
            }

            return $output;

        }

        // Add default menu entry
        if (isset($_POST["add_default"])) {
            $default_entries = $this->get_default_entries();
            if (array_key_exists($_POST["add_default"], $default_entries)) {
                $entry_position = count($output) + 1;
                $output[$entry_position] = $default_entries[$_POST['add_default']];
                $output[$entry_position]['menu_position'] = $entry_position;

            }

            return $output;
        }

        // Add all default menu entries
        if (isset($_POST["add_all_default"])) {
            $default_entries = $this->get_default_entries();
            foreach ($default_entries as $default_entry) {
                $entry_position = count($output) + 1;
                $output[$entry_position] = $default_entry;
                $output[$entry_position]['menu_position'] = $entry_position;

            }
            return $output;
        }

        // change menu positions
        if (isset($_POST["positions"])) {

            // check if something was changed
            if ($_POST["positions"] == "") {
                return $output;
            }

            $positions = sanitize_text_field($_POST["positions"]);

            $positions = explode("-", $positions);
            unset($positions[count($positions) - 1]);

            // validate keys
            $positions_validate = array_diff_key($output, array_flip($positions));
            if (!empty($positions_validate)) {
                add_settings_error('tfaf_custom_menu', esc_attr('tfaf_custom_menu'),
                    esc_html__('Invalid Position.', 'toolbox-for-asgaros-forum'), 'error');

                return $output;
            }


            $ordered_output[1] = "";
            for ($i = 0; $i < count($positions); $i++) {
                $ordered_output[$i + 1] = $output[$positions[$i]];
                $ordered_output[$i + 1]["menu_position"] = ($i + 1) . "";
            }
            return $ordered_output;
        }

        // sanitize and validate input
        $input['menu_position'] = (int)$input['menu_position'];

        $max_position = count($output) + 1;

        if ($input['menu_position'] < 1 || $input['menu_position'] > $max_position) {
            add_settings_error('tfaf_custom_menu', esc_attr('tfaf_custom_menu'),
                esc_html__('Invalid Position.', 'toolbox-for-asgaros-forum'), 'error');

            return $output;
        }


        $input['menu_link_text'] = sanitize_text_field($input['menu_link_text']);
        if ( substr( $input['menu_url'], 0, 1 ) === "[") {
            $input['menu_url'] = sanitize_text_field($input['menu_url']);
        } elseif ($input['menu_url'] !== esc_url_raw($input['menu_url'])){
            add_settings_error('tfaf_custom_menu', esc_attr('tfaf_custom_menu'),
                esc_html__('Invalid URL. URL must start with https:// or http://', 'toolbox-for-asgaros-forum'), 'error');
            return $output;
        } else {
            $input['menu_url'] = esc_url_raw($input['menu_url']);
        }

        if (isset($input['menu_class'])) {
            $input['menu_class'] = sanitize_text_field($input['menu_class']);
            unset($input['menu_class']);
        }

        if (isset($input['menu_new_tab'])) {
            $input['menu_new_tab'] = sanitize_text_field($input['menu_new_tab']);
        }

        if (isset($input['menu_only_users'])) {
            $input['menu_only_users'] = sanitize_text_field($input['menu_only_users']);
        }

        if (count($output) == 0) {
            $output[$input['menu_position']] = $input;

            return $output;
        }

        foreach ($output as $key => $value) {
            if ($input['menu_position'] === $key) {
                $output[$key] = $input;
            } else {
                $output[$input['menu_position']] = $input;
            }
        }

        return $output;
    }

    /**
     * Callback for a textfield
     *
     * @param $args arguments for Text Field
     */
    public function textField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $required = isset($args['required']) ? 'required' : '';
        $value = '';
        $disabled = '';

        // Check if a existing entry has to be edit
        if (isset($_POST["edit_entry"])) {
            $edit_cme = sanitize_text_field($_POST["edit_entry"]);
            $input = get_option($option_name);

            // Check if key is valid
            if (array_key_exists($edit_cme, $input)) {
                $value = isset($input[$edit_cme][$name]) ? $input[$edit_cme][$name] : '';
            } else {
                $value = esc_html__('Error: Entry can\'t be edited.', 'toolbox-for-asgaros-forum');
            }

            $disabled = ($name == 'menu_position') ? 'readonly' : '';
        }

        echo '<input type="text" class="regular-text" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="' . $value . '" placeholder="' . $args['placeholder'] . '" ' . $disabled . ' ' . $required . '>';
    }

    /**
     * Callback for the position field
     *
     * @param $args arguments for position Field
     */
    public function position($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $input = get_option($option_name);
        $count = count($input) + 1;

        // Check if a existing entry has to be edit
        if (isset($_POST["edit_entry"])) {
            $count = sanitize_text_field($_POST["edit_entry"]);

            // check if position is valid
            if (!array_key_exists($count, $input)) {
                $count = esc_html__('Error: Position is not valid.', 'toolbox-for-asgaros-forum');
            }
        }

        echo '<input type="text" class="regular-text" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="' . $count . '" readonly required>';

    }

    /**
     * Callback for Url Field
     *
     * @param $args argument for url Field
     */
    public function urlField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $value = '';

        // Check if a existing entry has to be edit
        if (isset($_POST["edit_entry"])) {
            $edit_cme = sanitize_text_field($_POST["edit_entry"]);
            $input = get_option($option_name);

            // Check if key is valid
            if (array_key_exists($edit_cme, $input)) {
                $value = $input[$edit_cme][$name];
            } else {
                $value = esc_html__('Error: Entry can\'t be edited.', 'toolbox-for-asgaros-forum');
            }
        }

        echo '<input type="text" class="regular-text" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="' . $value . '" placeholder="' . $args['placeholder'] . '" pattern="^(https?:\/\/).+|^(\[).+|^(\/).+" required>';

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
        $checked = false;

        // Check if a existing entry has to be edit
        if (isset($_POST["edit_entry"])) {
            $checkbox = get_option($option_name);
            $checked = isset($checkbox[$_POST["edit_entry"]][$name]) ?: false;
        }


        echo '<div class="' . $classes . '"><input type="checkbox" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="1" class="" ' . ($checked ? 'checked' : '') . '><label for="' . $name .
            '"><div></div></label></div>';
    }

    /**
     * Callback for a checkbox field
     *
     * @param $args arguments for a checkbox field
     */
    public function radioField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $checked = "0";

        // Check if a existing entry has to be edit
        if (isset($_POST["edit_entry"])) {
            $option = get_option($option_name);
            $checked = isset($option[$_POST["edit_entry"]][$name]) ? $option[$_POST["edit_entry"]][$name] : "0";
        }


        echo '<div class="radio-group">';
        echo '<input type="radio" id="tfaf_login_0" name="' . $option_name . '[' . $name . ']" value="0" ' .
            checked($checked, 0, false) . '/><label for="tfaf_login_0">All users</label><br>';

        echo '<input type="radio" id="tfaf_login_1" name="' . $option_name . '[' . $name . ']" value="1" ' .
            checked($checked, 1, false) . '/><label for="tfaf_login_1">Only logged in</label><br>';

        echo '<input type="radio" id="tfaf_login_2" name="' . $option_name . '[' . $name . ']" value="2" ' .
            checked($checked, 2, false) . '/><label for="tfaf_login_2">Only logged out</label><br>';


        echo '</div>';
    }

    /**
     * Callback for a settings checkbox field
     *
     * @param $args arguments for a checkbox field
     */
    public function settingsCheckboxField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];
        $checkbox = get_option($option_name);
        $checked = isset($checkbox[$name]) ? ($checkbox[$name] ? true : false) : false;


        echo '<div class="' . $classes . '"><input type="checkbox" id="' . $name . '" name="' . $option_name . '[' .
            $name . ']" value="1" class="" ' . ($checked ? 'checked' : '') . '><label for="' . $name .
            '"><div></div></label></div>';
    }

    public function get_default_entries()
    {

        return array(
            'home' => array(
                'menu_link_text' => esc_html__('Forum', 'asgaros-forum'),
                'menu_class' => 'home-link',
                'menu_url' => '[home]',
                'menu_only_users' => 0,
            ),
            'profile' => array(
                'menu_link_text' => esc_html__('Profile', 'asgaros-forum'),
                'menu_class' => 'profile-link',
                'menu_url' => '[profile]',
                'menu_only_users' => 1,
            ),
            'memberslist' => array(
                'menu_link_text' => esc_html__('Members', 'asgaros-forum'),
                'menu_class' => 'members-link',
                'menu_url' => '[memberslist]',
                'menu_only_users' => 0,
            ),
            'subscription' => array(
                'menu_link_text' => esc_html__('Subscriptions', 'asgaros-forum'),
                'menu_class' => 'subscriptions-link',
                'menu_url' => '[subscription]',
                'menu_only_users' => 1,
            ),
            'activity' => array(
                'menu_link_text' => esc_html__('Activity', 'asgaros-forum'),
                'menu_class' => 'activity-link',
                'menu_url' => '[activity]',
                'menu_only_users' => 0,
            ),
            'login' => array(
                'menu_link_text' => esc_html__('Login', 'asgaros-forum'),
                'menu_class' => 'login-link',
                'menu_url' => '[login]',
                'menu_only_users' => 2,
            ),
            'register' => array(
                'menu_link_text' => esc_html__('Register', 'asgaros-forum'),
                'menu_class' => 'register-link',
                'menu_url' => '[register]',
                'menu_only_users' => 2,
            ),
            'logout' => array(
                'menu_link_text' => esc_html__('Logout', 'asgaros-forum'),
                'menu_class' => 'logout-link',
                'menu_url' => '[logout]',
                'menu_only_users' => 1,
            ),

        );
    }
}