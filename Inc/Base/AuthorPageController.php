<?php

namespace Tfaf\Inc\Base;

require_once TFAF_PLUGIN_PATH . 'Inc/Api/SettingsApi.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Api/Callbacks/AdminCallbacks.php';
require_once TFAF_PLUGIN_PATH . 'Inc/Base/BaseController.php';
include_once(ABSPATH . 'wp-admin/includes/plugin.php');


use AsgarosForum;
use AsgarosForumStatistics;
use Tfaf\Inc\Api\Callbacks\AdminCallbacks;
use Tfaf\Inc\Api\SettingsApi;

class AuthorPageController extends BaseController
{

    public $callbacks;

    public $settings;

    public $subpages = array();

    public $asgaros_forum;

    /**
     * Register the Author Page Shortcode Controller
     */
    public function register()
    {

        // Check if the Controller is activated
        if (!$this->activated('ap_shortcode_manager')) {
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
        add_shortcode('tfaf__ap_activity', array($this, 'tfaf_ap_activity_func'));
        add_shortcode('tfaf__ap_history', array($this, 'tfaf_ap_history_func'));

    }

    /**
     * Show a small statistic about the user activity
     */
    public function tfaf_ap_activity_func()
    {


        $curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
        $profile_id = $curauth->ID;
        ob_start();

        echo '<div class="tfaf_wrapper">';
        echo '<div class="profile-section-header">';
        echo '<span class="profile-section-header-icon fas fa-address-card"></span>';
        echo __('Member Activity', 'asgaros-forum');
        echo '</div>';

        echo '<div class="profile-section-content">';
        // Topics started.
        $count_topics = $this->asgaros_forum->countTopicsByUser($profile_id);
        AsgarosForumStatistics::renderStatisticsElement(__('Topics Started', 'asgaros-forum'), $count_topics, 'far fa-comments');

        // Replies created.
        $count_posts = $this->asgaros_forum->countPostsByUser($profile_id);
        $count_posts = $count_posts - $count_topics;
        AsgarosForumStatistics::renderStatisticsElement(__('Replies Created', 'asgaros-forum'), $count_posts, 'far fa-comment');

        // Likes Received.
        if ($this->asgaros_forum->options['enable_reactions']) {
            $count_likes = $this->asgaros_forum->reactions->get_reactions_received($profile_id, 'up');
            AsgarosForumStatistics::renderStatisticsElement(__('Likes Received', 'asgaros-forum'), $count_likes, 'fas fa-thumbs-up');
        }
        echo '</div></div>';

        return ob_get_clean();


    }


    /**
     * Generate a out to show the history of the posts of an user
     */
    public function tfaf_ap_history_func()
    {

        $curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
        $user_id = $curauth->ID;
        

        ob_start();

        echo '<div class="tfaf_wrapper">';

        $posts = $this->get_post_history_by_user($user_id, true);

        if (empty($posts)) {
            _e('No posts made by this user.', 'asgaros-forum');
        } else {

            foreach ($posts as $post) {
                echo '<div class="history-element">';
                echo '<div class="history-name">';

                // set links
                $this->asgaros_forum->rewrite->set_links();

                $link = $this->asgaros_forum->rewrite->get_post_link($post->id, $post->parent_id);
                $text = esc_html(stripslashes(strip_tags($post->text)));
                $text = $this->asgaros_forum->cut_string($text, 100);

                echo '<a class="history-title" href="' . $link . '">' . $text . '</a>';

                $topic_link = $this->asgaros_forum->rewrite->get_link('topic', $post->parent_id);
                $topic_name = esc_html(stripslashes($post->name));
                $topic_time = sprintf(__('%s ago', 'asgaros-forum'), human_time_diff(strtotime($post->date), current_time('timestamp')));

                echo '<span class="history-topic">' . __('In:', 'asgaros-forum') . ' <a href="' . $topic_link . '">' . $topic_name . '</a></span>';
                echo '</div>';

                echo '<div class="history-time">' . $topic_time . '</div>';
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
     * Register Subpage for Admin Menu
     */
    public function setSubPages()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'toolbox_asgaros',
                'page_title' => esc_html__('Shortcodes for Author Page', 'toolbox-for-asgaros-forum'),
                'menu_title' => esc_html__('Integration Author Page', 'toolbox-for-asgaros-forum'),
                'capability' => 'manage_options',
                'menu_slug' => 'tfaf_shortcodes_ap',
                'callback' => array($this->callbacks, 'shortcodesAP')
            )
        );
    }

}
