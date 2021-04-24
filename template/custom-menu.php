<div class="tfaf_admin_wrapper">
    <h1><?php esc_html_e('Custom Menu Manager', 'toolbox-for-asgaros-forum') ?></h1>
    <?php settings_errors(); ?>

    <ul class="nav nav-tabs">
        <li class="<?php echo !isset($_POST["edit_entry"]) ? 'active' : '' ?>">
            <a href="#tab-1"><?php esc_html_e('Your Custom Menu Entries', 'toolbox-for-asgaros-forum') ?></a></li>
        <li class="<?php echo isset($_POST["edit_entry"]) ? 'active' : '' ?>">
            <a href="#tab-2">
                <?php
                echo isset($_POST["edit_entry"]) ? esc_html__('Edit', 'toolbox-for-asgaros-forum') . ' ' : esc_html__('Add', 'toolbox-for-asgaros-forum') . ' ';
                esc_html_e('Custom Menu Entry', 'toolbox-for-asgaros-forum');
                ?>
            </a>
        </li>
        <li>
            <a href="#tab-3"><?php esc_html_e('Menu Settings', 'toolbox-for-asgaros-forum') ?></a></li>
    </ul>

    <div class="tab-content">
        <div id="tab-1" class="tab-pane <?php echo !isset($_POST["edit_entry"]) ? 'active' : '' ?>">
            <h3><?php esc_html_e('Manage your Custom Menu Entries', 'toolbox-for-asgaros-forum') ?></h3>

            <?php

            $options = get_option('tfaf_custom_menu') ?: array();
            $positions = "";
            echo '<table class="cme-table"><tr><th>' . esc_html__('Position', 'toolbox-for-asgaros-forum') .
                '</th><th>' . esc_html__('Link Text', 'toolbox-for-asgaros-forum') . '</th><th>' .
                esc_html__('URL', 'toolbox-for-asgaros-forum') . '</th><th>' .
                esc_html__('Menu Class', 'toolbox-for-asgaros-forum') . '</th><th>' .
                esc_html__('New Tab', 'toolbox-for-asgaros-forum') . '</th><th>' .
                esc_html__('Show entry to', 'toolbox-for-asgaros-forum') . '</th><th>' .
                esc_html__('Move Entry', 'toolbox-for-asgaros-forum') . '</th><th>' .
                esc_html__('Actions', 'toolbox-for-asgaros-forum') . '</th></tr>';

            foreach ($options as $option) {
                $positions .= $option['menu_position'] . '-';
                $new_tab = (isset($option['menu_new_tab'])) ? 'TRUE' : 'FALSE';
                $menu_class = (isset($option['menu_class'])) ? $option['menu_class'] : '';


                if (isset($option['menu_only_users']) && $option['menu_only_users'] == "1") {
                    $only_users = "LOGGED IN";
                } elseif (isset($option['menu_only_users']) && $option['menu_only_users'] == "2") {
                    $only_users = "LOGGED OUT";
                } else {
                    $only_users = "ALL";
                }

                echo "<tr class='menu-entry'><td>{$option['menu_position']}</td><td>{$option['menu_link_text']}</td>" .
                    "<td>{$option['menu_url']}</td><td>$menu_class</td><td>$new_tab</td><td>$only_users</td>" .
                    "<td>";

                echo '<button class ="up_button button button-small"><span class="dashicons dashicons-arrow-up-alt2"></span></button>';
                echo '<button class ="down_button button button-small"><span class="dashicons dashicons-arrow-down-alt2"></span></button> </td>';
                echo '<td><form method="post" action="" class="inline-block">';
                echo '<input type="hidden" name="edit_entry" value="' . $option['menu_position'] . '">';
                submit_button(esc_html__('Edit', 'toolbox-for-asgaros-forum'), 'primary small', 'submit', false);
                echo '</form>';


                echo ' <form method="post" action="options.php" class="inline-block">';
                settings_fields('tfaf_custom_menu_settings');
                echo '<input type="hidden" name="remove" value="' . $option['menu_position'] . '">';
                submit_button(esc_html__('Delete', 'toolbox-for-asgaros-forum'), 'delete small', 'submit', false, array(
                    'onclick' => 'return confirm("Are you sure you want to delete the CEM?")'
                ));

                echo '</form></td></tr>';

            }

            echo '</table>';

            echo '<br><form method="post" action="options.php">';
            settings_fields('tfaf_custom_menu_settings');
            echo '<input type="hidden" name="positions" id="tfaf_menu_positions" value="">';
            submit_button(esc_html__('Save Order', 'toolbox-for-asgaros-forum'), 'primary', 'submit', false, array('id' => 'tfaf-button-save-order'));
            echo '</form>';

            $cme_settings = get_option('tfaf_custom_menu_settings');
            if (isset($cme_settings['replace_menu'])) {
                echo '<h3>' . esc_html__('Add default menu entries', 'toolbox-for-asgaros-forum') . '</h3>';
                esc_html_e('Please enter the shown URLs (e.g. [home]) to use the default links from Asgaros Forum. If you want to redirect after logout you can use the folling URL "[logout=pageslug]"', 'toolbox-for-asgaros-forum');

                $default_entries = array(
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

                echo '<table class="cme-table cme-default-table"><tr><th>' . esc_html__('Link Text', 'toolbox-for-asgaros-forum') . '</th><th>' .
                    esc_html__('URL', 'toolbox-for-asgaros-forum') . '</th><th>' .
                    esc_html__('Menu Class', 'toolbox-for-asgaros-forum') . '</th><th>' .
                    esc_html__('New Tab', 'toolbox-for-asgaros-forum') . '</th><th>' .
                    esc_html__('Show entry to', 'toolbox-for-asgaros-forum') . '</th><th>' .
                    esc_html__('Add to menu', 'toolbox-for-asgaros-forum') . '</th></tr>';

                foreach ($default_entries as $key => $default_entry) {
                    if (empty($default_entry)) continue;

                    // Check for new tab
                    $new_tab = (isset($default_entry['menu_new_tab']) && $default_entry['menu_new_tab'] == true) ? 'TRUE' : 'FALSE';
                    $menu_class = (isset($default_entry['menu_class'])) ? $default_entry['menu_class'] : '';

                    // Check login status
                    if (isset($default_entry['menu_only_users']) && $default_entry['menu_only_users'] == "1") {
                        $only_users = "LOGGED IN";
                    } elseif (isset($default_entry['menu_only_users']) && $default_entry['menu_only_users'] == "2") {
                        $only_users = "LOGGED OUT";
                    } else {
                        $only_users = "ALL";
                    }

                    $entry_id = 'tfaf_default_entry_' . $key;



                    echo '<tr><td>' . $default_entry['menu_link_text'] . '</td><td>' .
                        $default_entry['menu_url'] . '</td><td>' .$menu_class .'</td><td>' . $new_tab . '</td><td>' .
                        $only_users . '</td><td>';

                    echo '<form method="post" action="options.php">';
                    settings_fields('tfaf_custom_menu_settings');
                    echo '<input type="hidden" name="add_default" id="' . $entry_id . '" value="' . $key . '">';
                    submit_button(esc_html__('Add', 'toolbox-for-asgaros-forum'), 'primary', 'submit', false, array('id' => $entry_id));
                    echo '</form>';

                    echo '</td></tr>';

                }

                echo '</table>';

                echo '<br><form method="post" action="options.php">';
                settings_fields('tfaf_custom_menu_settings');
                echo '<input type="hidden" name="add_all_default" id="tfaf_add_all_entries" value="all">';
                submit_button(esc_html__('Add all default entries', 'toolbox-for-asgaros-forum'), 'primary', 'submit', false, array('id' => 'tfaf_add_all_entries'));
                echo '</form>';

            }


            ?>


        </div>

        <div id="tab-2" class="tab-pane <?php echo isset($_POST["edit_entry"]) ? 'active' : '' ?>">
            <form method="post" action="options.php">
                <?php
                settings_fields('tfaf_custom_menu_settings');
                do_settings_sections('tfaf_cme');

                submit_button(null, 'primary', 'submit', false);
                if (isset($_POST["edit_entry"])) {
                    echo '<button onclick="location.reload()" class="button button-secondary">' .
                        esc_html__('Discard', 'toolbox-for-asgaros-forum') . '</button>';
                }
                ?>
            </form>
        </div>

        <div id="tab-3" class="tab-pane">
            <form method="post" action="options.php">
                <?php
                settings_fields('tfaf_custom_menu_general_settings');
                do_settings_sections('tfaf_cme_settings');

                submit_button(null, 'primary', 'submit', false);

                ?>
            </form>
        </div>
    </div>

</div>