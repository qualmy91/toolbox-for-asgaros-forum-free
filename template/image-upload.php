<div class="tfaf_admin_wrapper">
    <h1><?php esc_html_e('Inline Image Upload', 'toolbox-for-asgaros-forum') ?></h1>


    <form method="post" action="options.php">
        <?php
        settings_fields('tfaf_image_upload_settings');
        do_settings_sections('tfaf_iu');
        submit_button();
        ?>
    </form>

    <p><?php esc_html_e('The users get the ability to easily insert images into a post.', 'toolbox-for-asgaros-forum') ?></p>

    <h2><?php esc_html_e('Instructions', 'toolbox-for-asgaros-forum') ?></h2>

    <h3><?php esc_html_e('1. Click the insert/edit image button', 'toolbox-for-asgaros-forum') ?></h3>
    <img src="<?php echo TFAF_PLUGIN_URL . 'assets/images/insert_image.png' ?>">

    <h3><?php esc_html_e('2. Choose an image by clicking the upload button', 'toolbox-for-asgaros-forum') ?></h3>
    <img src="<?php echo TFAF_PLUGIN_URL . 'assets/images/upload_popup.png' ?>">

    <h3><?php esc_html_e('3. The image is inserted in the post', 'toolbox-for-asgaros-forum') ?></h3>
    <img src="<?php echo TFAF_PLUGIN_URL . 'assets/images/image_inserted.png' ?>">


</div>
