<?php


namespace Tfaf\Inc\Base;

class SettingsLinks
{
    /**
     * Register the Settings Links Class
     */
    public function register()
    {
        add_filter("plugin_action_links_" . TFAF_PLUGIN, array($this, 'settings_link'));
    }


    /**
     * Add a link to the Settings Page of the Plugin
     *
     * @param array $links actual links at the Plugin Page
     *
     * @return array with all the links for the Plugin Page
     */
    public function settings_link($links)
    {
        // add Docs link
        $docs_link = '<a href="https://www.dominikrauch.de/en/toolbox-for-asgaros-forum-docs/">' . esc_html__('Docs', 'toolbox-for-asgaros-forum') . '</a>';
        array_push($links, $docs_link);

        // add settings link
        $settings_link = '<a href="admin.php?page=toolbox_asgaros">' . esc_html__('Settings', 'toolbox-for-asgaros-forum') . '</a>';
        array_push($links, $settings_link);

        return $links;
    }

}