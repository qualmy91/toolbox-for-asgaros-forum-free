<div class="tfaf_admin_wrapper">
    <?php
    settings_errors();
    ?>


    <form method="post" action="options.php">
        <input type="hidden" name="tfaf_plugin_settings[page]" value="tfaf_as">
        <?php
        settings_fields('tfaf_plugin_settings');
        do_settings_sections('tfaf_as');
        submit_button();
        ?>
    </form>

</div>

