<div class="tfaf_admin_wrapper">
    <h1><?php esc_html_e('Toolbox for Asgaros Forum', 'toolbox-for-asgaros-forum') ?></h1>

    <?php
        settings_errors();
        esc_html_e('Please check also the ', 'toolbox-for-asgaros-forum');
        echo ' <a href="https://www.dominikrauch.de/en/toolbox-for-asgaros-forum-docs/">' . esc_html__('Docs', 'toolbox-for-asgaros-forum') . '</a> ';
        esc_html_e('for further help.', 'toolbox-for-asgaros-forum');
    ?>


    <form method="post" action="options.php">
        <?php
        settings_fields('tfaf_plugin_settings');
        do_settings_sections('tfaf_plugin');
        submit_button();
        ?>
    </form>

</div>

