<div class="tfaf_admin_wrapper">
    <h1><?php esc_html_e('Shortcodes for Ultimate Member', 'toolbox-for-asgaros-forum') ?></h1>

    <p><?php esc_html_e('To add more functionality on the profile you can use the Ultimate Member together with Asgaros Forum. Just use the following Shortcodes to add the forum specific information to the Ultimate Member profile.', 'toolbox-for-asgaros-forum') ?></p>

    <h2><?php esc_html_e('User Statistics', 'toolbox-for-asgaros-forum') ?></h2>

    <p><?php esc_html_e('This is the Shortcode to insert the user statistic in the Ultimate Member Profile:', 'toolbox-for-asgaros-forum') ?>
        <code>[tfaf_activity]</code></p>

    <p><strong>Preview:</strong></p>
    <div class="tfaf-preview">
        <?php echo do_shortcode('[tfaf_activity]'); ?>
    </div>

    <h2><?php esc_html_e('Post History', 'toolbox-for-asgaros-forum') ?></h2>

    <p><?php esc_html_e('This is the Shortcode to insert the user history at the Ultimate Member Profile:', 'toolbox-for-asgaros-forum') ?>
        <code>[tfaf_history]</code></p>
    <p><?php esc_html_e('You can also use the function "Profile Tab for Ultimate Member" to add a tab at the Profile of Ultimate Member. This Shortcode will be automatically included in this tab.', 'toolbox-for-asgaros-forum') ?> </p>
    <p><strong><?php esc_html_e('Preview:', 'toolbox-for-asgaros-forum') ?></strong></p>
    <div class="tfaf-preview">
        <?php echo do_shortcode('[tfaf_history]'); ?>
    </div>

    <h2><?php esc_html_e('Additional Tab at Ultimate Member Profile', 'toolbox-for-asgaros-forum') ?></h2>

    <p>
        <?php esc_html_e('This function creates an additional tab for the User Profile of Ultimate Member. The is automatically added to the profile page.', 'toolbox-for-asgaros-forum') ?>
    </p>


    <p><strong><?php esc_html_e('Preview of the Ultimate Member Profile Tab:', 'toolbox-for-asgaros-forum') ?></strong>
    </p>
    <img src="<?php echo TFAF_PLUGIN_URL . 'assets/images/user_history.jpg' ?>">

</div>
