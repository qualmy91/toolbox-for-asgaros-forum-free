<?php

namespace Tfaf\Inc\Base;

if (!defined('ABSPATH')) {
    exit;
}

require_once TFAF_PLUGIN_PATH . 'Inc/Api/SettingsApi.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AdminCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';
include_once(ABSPATH . 'wp-admin/includes/plugin.php');


use AsgarosForum;
use AsgarosForumStatistics;
use Tfaf\Inc\Api\Callbacks\AdminCallbacks;
use Tfaf\Inc\Api\SettingsApi;

class UltimateMemberIntegrationController extends BaseController
{

    public $callbacks;

    public $settings;

    public $subpages = array();

    public $asgaros_forum;

    /**
     * Register the Ultimate Member Shortcode Controller
     */
    public function register()
    {

        // Check if the Controller is activated
        if (!$this->activated('um_shortcode_manager')) {
            return;
        }

        // Check if Ultimate Member is activated
        if (!is_plugin_active('ultimate-member/ultimate-member.php')) {
            return;
        }

        // Check if Asgaros Forum is activated
        if (!is_plugin_active('asgaros-forum/asgaros-forum.php')) {
            return;
        }

        require_once(WP_PLUGIN_DIR . '/asgaros-forum/includes/forum.php');
        require_once(WP_PLUGIN_DIR . '/asgaros-forum/includes/forum-profile.php');

        $this->settings = new SettingsApi();
        $this->callbacks = new AdminCallbacks();

        global $asgarosforum;
        $this->asgaros_forum = $asgarosforum;

        $this->setSubPages();
        $this->settings->addSubPages($this->subpages)->register();

        // register shortcodes
        add_shortcode('tfaf_activity', array($this, 'tfaf_activity_func'));
        add_shortcode('tfaf_history', array($this, 'tfaf_history_func'));

        // add filter to add custom profile tab and render the profile tab in ultimate member profile
        add_filter('um_profile_tabs', array($this, 'add_profile_tab'), 1000);
        add_action('um_profile_content_user_history_default', array($this, 'render_profile_tab'));
    }

    /**
     * Show a small statistic about the user activity
     */
    public function tfaf_activity_func()
    {

        // enqueue style
        wp_enqueue_style(
            'tfaf_activity_style',
            TFAF_PLUGIN_URL . 'assets/um_integration.css',
            array(),
            TFAF_VERSION
        );

        $profile_id = um_profile_id();
        ob_start();

        echo '<div class="tfaf_wrapper">';
        echo '<div class="profile-section-header">';
        echo '<span class="profile-section-header-icon fas fa-address-card"></span>';
        echo esc_html__('Member Activity', 'toolbox-for-asgaros-forum');
        echo '</div>';

        echo '<div class="profile-section-content">';
        // Topics started.
        $count_topics = $this->asgaros_forum->countTopicsByUser($profile_id);
        AsgarosForumStatistics::renderStatisticsElement(__('Topics Started', 'toolbox-for-asgaros-forum'), $count_topics, 'far fa-comments');

        // Replies created.
        $count_posts = $this->asgaros_forum->countPostsByUser($profile_id);
        $count_posts = $count_posts - $count_topics;
        AsgarosForumStatistics::renderStatisticsElement(__('Replies Created', 'toolbox-for-asgaros-forum'), $count_posts, 'far fa-comment');

        // Likes Received.
        if ($this->asgaros_forum->options['enable_reactions']) {
            $count_likes = $this->asgaros_forum->reactions->get_reactions_received($profile_id, 'up');
            AsgarosForumStatistics::renderStatisticsElement(__('Likes Received', 'toolbox-for-asgaros-forum'), $count_likes, 'fas fa-thumbs-up');
        }
        echo '</div></div>';

        return ob_get_clean();


    }


    /**
     * Generate a out to show the history of the posts of an user
     */
    public function tfaf_history_func()
    {

        // enqueue style
        wp_enqueue_style(
            'tfaf_activity_style',
            TFAF_PLUGIN_URL . 'assets/um_integration.css',
            array(),
            TFAF_VERSION
        );

        $user_id = um_profile_id();

        ob_start();

        echo '<div class="tfaf_wrapper">';

        $posts = $this->get_post_history_by_user($user_id, true);

        if (empty($posts)) {
            esc_html_e('No posts made by this user.', 'toolbox-for-asgaros-forum');
        } else {

            foreach ($posts as $post) {
                echo '<div class="history-element">';
                echo '<div class="history-name">';

                // set links
                $this->asgaros_forum->rewrite->set_links();

                $link = $this->asgaros_forum->rewrite->get_post_link($post->id, $post->parent_id);
                $text = esc_html(stripslashes(wp_strip_all_tags($post->text)));
                $text = $this->asgaros_forum->cut_string($text, 100);

                echo '<a class="history-title" href="' . esc_url($link) . '">' . esc_html($text) . '</a>';

                $topic_link = $this->asgaros_forum->rewrite->get_link('topic', $post->parent_id);
                $topic_name = esc_html(stripslashes($post->name));
                /* translators: %s: Human-readable time difference. */
                $topic_time = sprintf(__('%s ago', 'toolbox-for-asgaros-forum'), human_time_diff(strtotime($post->date), current_time('timestamp')));

                echo '<span class="history-topic">' . esc_html__('In:', 'toolbox-for-asgaros-forum') . ' <a href="' . esc_url($topic_link) . '">' . esc_html($topic_name) . '</a></span>';
                echo '</div>';

                echo '<div class="history-time">' . esc_html($topic_time) . '</div>';
                echo '</div>';
            }

        }
        echo '</div>';
        return ob_get_clean();

    }


    /**
     * Get the history of posts for an user
     *
     * @param number $user_id id of the user to search
     * @param number $limit limit of the posts to get
     *
     * @return array|bool List of posts by an user
     */
    public function get_post_history_by_user($user_id, $limit = false)
    {
        // Get accessible categories for the current user first.
        $accessible_categories = $this->asgaros_forum->content->get_categories_ids();

        if (empty($accessible_categories)) {
            // Cancel if the user cant access any categories.
            return false;
        } else {
            // Now load history-data based for an user based on the categories which are accessible for the current user.
            $accessible_categories = implode(',', $accessible_categories);

            $query_limit = "";

            if ($limit) {
                $elements_maximum = 50;
                $elements_start = $this->asgaros_forum->current_page * $elements_maximum;

                $query_limit = "LIMIT {$elements_start}, {$elements_maximum}";
            }

            $query = "SELECT p.id, p.text, p.date, p.parent_id, t.name FROM {$this->asgaros_forum->tables->posts} AS p, {$this->asgaros_forum->tables->topics} AS t WHERE p.author_id = %d AND p.parent_id = t.id AND EXISTS (SELECT f.id FROM {$this->asgaros_forum->tables->forums} AS f WHERE f.id = t.parent_id AND f.parent_id IN ({$accessible_categories})) AND t.approved = 1 ORDER BY p.id DESC {$query_limit};";

            return $this->asgaros_forum->db->get_results($this->asgaros_forum->db->prepare($query, $user_id));
        }
    }

    /**
     * Add a new tab to the ultimate member profile page
     *
     * @param array $tabs actual tabs at ultimate member profile
     *
     * @return array all tabs at utlimate member profile
     */
    function add_profile_tab($tabs)
    {

        $tabs['user_history'] = array(
            'name' => esc_html__('Post History', 'toolbox-for-asgaros-forum'),
            'icon' => 'um-faicon-list',
            'default_privacy' => 2,
        );

        return $tabs;
    }

    /**
     * Render the Profile Tab for Ultimate Member
     *
     * @param $args
     */
    function render_profile_tab($args)
    {

        echo '<div class="um">';
        echo do_shortcode('[tfaf_history]');
        echo '</div>';

    }

    /**
     * Register Subpage for Admin Menu
     */
    public function setSubPages()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'toolbox_asgaros',
                'page_title' => esc_html__('Integration for Ultimate Member', 'toolbox-for-asgaros-forum'),
                'menu_title' => esc_html__('Integration UM', 'toolbox-for-asgaros-forum'),
                'capability' => 'manage_options',
                'menu_slug' => 'tfaf_shortcodes_um',
                'callback' => array($this->callbacks, 'shortcodesUm')
            )
        );
    }

}