<?php

namespace WP_PODCAST_IMPORTER\admin;

use WP_PODCAST_IMPORTER;

class Admin
{
    public static $admin_page_slug = 'podcast-importer';

    public function __construct()
    {
        # Add Settings Page Link
        add_filter("plugin_action_links_" . WP_PODCAST_IMPORTER::$plugin_basename, array($this, 'podcastImporterAddSettingsLink'));

        # Add Admin Menu
        add_action('admin_menu', array($this, 'admin_menu'));

        # Load Admin Asset
        add_action('admin_enqueue_scripts', array($this, 'adminAssets'));

        # Allow Iframe For Import
        add_filter('wp_kses_allowed_html', array($this, 'podcastImporterAllowIframe'), 10, 2);

        # oEmbed discovery is enabled for all users and allows embedding of sanitized iframes
        add_filter('oembed_providers', array($this, 'podcastImporterOembedProviders'));
    }

    /**
     * Admin Link
     *
     * @param $page
     * @param array $args
     * @return string
     */
    public static function admin_link($page, $args = array())
    {
        return add_query_arg($args, admin_url('admin.php?page=' . $page));
    }

    /**
     * If in Page in Admin
     *
     * @param $page_slug
     * @return bool
     */
    public static function in_page($page_slug)
    {
        global $pagenow;
        if ($pagenow == "admin.php" and isset($_GET['page']) and $_GET['page'] == $page_slug) {
            return true;
        }

        return false;
    }

    /**
     * Load assets file in admin
     */
    public function adminAssets()
    {
        global $pagenow;

        //List Allow This Script
        if ($pagenow == "tools.php") {

            // Get Plugin Version
            $plugin_version = WP_PODCAST_IMPORTER::$plugin_version;
            if (defined('SCRIPT_DEBUG') and SCRIPT_DEBUG === true) {
                $plugin_version = time();
            }

            wp_register_style(self::$admin_page_slug, WP_PODCAST_IMPORTER::$plugin_url . '/asset/admin/css/admin.css', false, $plugin_version);
            wp_enqueue_style(self::$admin_page_slug);
            wp_register_script(self::$admin_page_slug, WP_PODCAST_IMPORTER::$plugin_url . '/asset/admin/js/admin.js', false, $plugin_version, true);
            wp_enqueue_script(self::$admin_page_slug);
        }
    }

    /**
     * Set Admin Menu
     * @see https://developer.wordpress.org/reference/functions/add_management_page/
     */
    public function admin_menu()
    {
        add_management_page(esc_attr__('Podcast Importer', 'podcast-importer'), esc_attr__('Podcast Importer', 'podcast-importer'), 'manage_options', 'podcast-importer', array($this, 'adminPage'));
    }

    /**
     * Show Admin Page
     */
    public function adminPage()
    {
        require_once WP_PODCAST_IMPORTER::$plugin_path . '/inc/admin/views/tools.php';
    }

    /**
     * Show Setting Form
     *
     * @param false $post_id_to_update
     */
    public static function settingsForm($post_id_to_update = false)
    {
        if ($post_id_to_update) {
            $post_meta = get_post_meta($post_id_to_update);
        }
        require_once WP_PODCAST_IMPORTER::$plugin_path . '/inc/admin/views/settings.php';
    }

    /**
     * Update Setting Process
     *
     * @param $post_id_to_update
     * @return false
     */
    public static function updateProcess($post_id_to_update)
    {
        if (!isset($_POST['podcast_feed']) || $_POST['podcast_feed'] == '') {
            echo "Must define podcast feed.";
            return false;
        }

        if (isset($_POST['podcast_feed'])) {
            $podcast_importer_rss_feed_url = sanitize_text_field($_POST['podcast_feed']);

            update_post_meta($post_id_to_update, 'podcast_importer_rss_feed', $podcast_importer_rss_feed_url);
        }

        if (isset($_POST['post_type_select'])) {
            $podcast_importer_post_type = sanitize_text_field($_POST['post_type_select']);

            update_post_meta($post_id_to_update, 'podcast_importer_post_type', $podcast_importer_post_type);
        }

        if (isset($_POST['publish_option_select'])) {
            $podcast_importer_publish = sanitize_text_field($_POST['publish_option_select']);

            update_post_meta($post_id_to_update, 'podcast_importer_publish', $podcast_importer_publish);
        }
        if (isset($_POST['post_category_select'])) {
            $podcast_importer_category = array_map('sanitize_text_field', $_POST['post_category_select']);

            update_post_meta($post_id_to_update, 'podcast_importer_category', $podcast_importer_category);
        }

        if (isset($_POST['podcast_importer_author'])) {
            $podcast_importer_author = sanitize_text_field($_POST['podcast_importer_author']);

            update_post_meta($post_id_to_update, 'podcast_importer_author', $podcast_importer_author);
        }

        if (isset($_POST['podcast_importer_images'])) {
            $podcast_importer_images = sanitize_text_field($_POST['podcast_importer_images']);

            update_post_meta($post_id_to_update, 'podcast_importer_images', $podcast_importer_images);
        }

        if (isset($_POST['podcast_importer_episode_number'])) {
            $podcast_importer_episode_number = sanitize_text_field($_POST['podcast_importer_episode_number']);

            update_post_meta($post_id_to_update, 'podcast_importer_episode_number', $podcast_importer_episode_number);
        }

        if (isset($_POST['podcast_importer_embed_player'])) {
            $podcast_importer_embed_player = sanitize_text_field($_POST['podcast_importer_embed_player']);

            update_post_meta($post_id_to_update, 'podcast_importer_embed_player', $podcast_importer_embed_player);
        }

        if (isset($_POST['podcast_importer_content_tag'])) {
            $podcast_importer_content_tag = sanitize_text_field($_POST['podcast_importer_content_tag']);

            update_post_meta($post_id_to_update, 'podcast_importer_content_tag', $podcast_importer_content_tag);
        }

        if (isset($_POST['podcast_importer_truncate_post'])) {
            $podcast_importer_truncate_post = sanitize_text_field($_POST['podcast_importer_truncate_post']);

            update_post_meta($post_id_to_update, 'podcast_importer_truncate_post', $podcast_importer_truncate_post);
        }

        if (isset($_POST['podcast_importer_prepend_title'])) {
            $podcast_importer_prepend_title = sanitize_text_field($_POST['podcast_importer_prepend_title']);

            update_post_meta($post_id_to_update, 'podcast_importer_prepend_title', $podcast_importer_prepend_title);
        }
    }

    /**
     * Add Link in Plugins WordPress Page
     *
     * @param $links
     * @return mixed
     */
    public function podcastImporterAddSettingsLink($links)
    {
        $settings_link = '<a href="tools.php?page=' . self::$admin_page_slug . '">' . esc_attr__('Settings', 'podcast-importer') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Add Iframe Tags in Kse Update WordPress Content
     *
     * @see https://developer.wordpress.org/reference/functions/wp_kses_allowed_html/
     * @param $tags
     * @param $context
     * @return mixed
     */
    public function podcastImporterAllowIframe($tags, $context)
    {
        if ('post' === $context) {
            $tags['iframe'] = array(
                'src' => true,
                'height' => true,
                'width' => true,
                'style' => true,
                'frameborder' => true,
                'allowfullscreen' => true,
                'scrolling' => true,
                'seamless' => true,
            );
        }
        return $tags;
    }

    /**
     * oEmbed discovery is enabled for all users and allows embedding of sanitized iframes
     * @see https://developer.wordpress.org/reference/hooks/oembed_providers/
     * @param $providers
     * @return mixed
     */
    public function podcastImporterOembedProviders($providers)
    {
        $providers['#https?://(.+).podbean.com/e/.+#i'] = array('https://api.podbean.com/v1/oembed', true);
        return $providers;
    }
}

new Admin();