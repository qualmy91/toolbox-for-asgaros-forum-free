<?php

namespace Tfaf\Inc;

use Tfaf\Inc\Base\UltimateMemberIntegrationController;

final class Init
{
    /**
     * Store all the used classes inside an array
     * @return array Full list of classes
     */
    public static function get_services()
    {
        require_once TFAF_PLUGIN_PATH . 'Inc/Pages/Dashboard.php';
        require_once TFAF_PLUGIN_PATH . 'Inc/Base/Enqueue.php';
        require_once TFAF_PLUGIN_PATH . 'Inc/Base/SettingsLinks.php';
        require_once TFAF_PLUGIN_PATH . 'Inc/Base/CustomMenuController.php';
        require_once TFAF_PLUGIN_PATH . 'Inc/Base/AutoSubscription.php';
        require_once TFAF_PLUGIN_PATH . 'Inc/Base/CustomUrlController.php';
        require_once TFAF_PLUGIN_PATH . 'Inc/Base/UltimateMemberIntegrationController.php';
        require_once TFAF_PLUGIN_PATH . 'Inc/Base/AuthorPageController.php';
        require_once TFAF_PLUGIN_PATH . 'Inc/Base/ImageUploadController.php';


        return [
            Pages\Dashboard::class,
            Base\Enqueue::class,
            Base\SettingsLinks::class,
            Base\CustomMenuController::class,
            Base\CustomUrlController::class,
            Base\UltimateMemberIntegrationController::class,
            Base\AuthorPageController::class,
            Base\ImageUploadController::class,
            Base\AutoSubscription::class,
        ];
    }

    /**
     * Loop through the classes, initialize them,
     * and call the register() method if exists
     */
    public static function register_services()
    {

        // Check if Plugin was updated
        $plugin_options = get_option('tfaf_plugin_settings');
        if (!isset($plugin_options['version'])) {
            // Update from 1.0

            // Save new Version to Options
            $plugin_options['version'] = TFAF_VERSION;
            $plugin_options['notification'] = 1;
            $plugin_options['notification_message'] = '1.1.0';
            update_option('tfaf_plugin_settings', $plugin_options);

        } elseif ($plugin_options['version'] != TFAF_VERSION) {

            if (version_compare($plugin_options['version'], '1.2.3', '<=')) {
                // Update manager names
                if (isset($plugin_options['cem_manager'])){
                    $plugin_options['cme_manager'] = $plugin_options['cem_manager'];
                    unset($plugin_options['cem_manager']);
                }
            }

            // Save new Version to Options
            $plugin_options['version'] = TFAF_VERSION;
            update_option('tfaf_plugin_settings', $plugin_options);

        }

        // Check notification response
        if ( isset( $_GET['tfaf_notify'] ) ) {
            $notify_response = esc_attr( $_GET['tfaf_notify'] );

            if ( $notify_response == 0) {
                // Reset notification setting
                $plugin_options['notification'] = 0;
                update_option('tfaf_plugin_settings', $plugin_options);
            } elseif ($notify_response == 2){
                // Set notification setting to never ask again
                $plugin_options['notification'] = 2;
                update_option('tfaf_plugin_settings', $plugin_options);
            }
        }



        // Check for new notifications
        if ($plugin_options['notification_message'] !== '' && $plugin_options['notification'] == 1) {
            add_action('admin_notices', array(__CLASS__, 'admin_notice'));
        }

        foreach (self::get_services() as $class) {
            $service = self::instantiate($class);
            if (method_exists($service, 'register')) {
                $service->register();
            }
        }
    }

    /**
     * Initialize the class
     *
     * @param class $class class to initialize
     *
     * @return class instance new instance of the class
     */
    private static function instantiate($class)
    {
        return new $class();
    }

    /**
     * Show a Notification at the admin panel
     */
    public static function admin_notice()
    {

        $messages = array(
            '1.1.0' => esc_html__(' With this update of Toolbox for Asgaros Forum there is a new feature. Your users get the ability to insert images directly into the post by simply clicking the button. The images can be easily adjusted and moved, as you are used to from the WordPress Editor.', 'toolbox-for-asgaros-forum') .
                ' <a href="admin.php?page=toolbox_asgaros">' . esc_html__('Activate this feature right now in the Dashboard', 'toolbox-for-asgaros-forum') . '</a>',
            'iu_50' => esc_html__(' It seems like your users like the Inline Upload Function of Toolbox for Asgaros Forum. They already uploaded 50 images until now!', 'toolbox-for-asgaros-forum') ,
            'iu_100' => esc_html__(' Your users uploaded already 100 images with the Inline Upload Function of Toolbox for Asgaros Forum. That\'s great!', 'toolbox-for-asgaros-forum') ,
            'iu_250' => esc_html__(' Already 250 images have been uploaded with the Inline Upload Function of Toolbox for Asgaros Forum.', 'toolbox-for-asgaros-forum') ,
            'iu_500' => esc_html__(' Your forum reached 500 image uploads with the Inline Upload Function of Toolbox for Asgaros Forum. This functionality helped your users a lot to easily upload images and made your forum more user friendly.', 'toolbox-for-asgaros-forum') ,
        );

        $notification_message = get_option('tfaf_plugin_settings')['notification_message'];

        if (!array_key_exists($notification_message, $messages)) return;
        $message = $messages[$notification_message];


        echo '<div class="notice notice-success">';

        echo '<p>' . $message . '</p><br>';

        echo '<b>' . esc_html__('Can you please do me a quick favor? Please give Toolbox for Asgaros Forum a 5-star rating on WordPress and keep me motivated.', 'toolbox-for-asgaros-forum') . '</b>';


        echo '<ul><li><a href="https://wordpress.org/support/plugin/toolbox-for-asgaros-forum/reviews/" target="_blank">' . esc_html__('Of course, you deserve my help', 'toolbox-for-asgaros-forum') . '</a></li>';
        echo '<li><a href="' .  get_admin_url() . '?tfaf_notify=0">' . esc_html__('Maybe later', 'toolbox-for-asgaros-forum').'</a></li>';
        echo '<li><a href="' . get_admin_url() . '?tfaf_notify=2">' . esc_html__('I already did', 'toolbox-for-asgaros-forum').'</a></li>';
        echo '</ul></div >';

    }

}
