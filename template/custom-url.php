<div class="tfaf_admin_wrapper">
    <?php
    echo '<h1>';
    esc_html_e('Custom URL Manager', 'toolbox-for-asgaros-forum');
    echo '</h1>';
    settings_errors();
    ?>


    <form method="post" action="options.php">
        <?php
        settings_fields('tfaf_custom_url_settings');
        do_settings_sections('tfaf_cu');
        submit_button();
        ?>
    </form>

</div>

