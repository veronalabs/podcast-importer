<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Main importer class
class Podcast_Importer
{
    /**
     * The single instance of the class.
     *
     * @var Podcast_Importer
     */
    protected static $_instance = null;

    /**
     * Podcast_Importer constructor.
     */
    public function __construct()
    {
        // Hook for importer cron job
        add_action('podcast_importer_cron', array($this, 'scheduled_podcast_import'));
        if (!wp_next_scheduled('podcast_importer_cron')) {
            wp_schedule_event(current_time('timestamp'), 'hourly', 'podcast_importer_cron');
        }

        // Hook for adding admin menus
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_page'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('admin_init', array($this, 'init'));
        }
    }

    /**
     * Instance of the class.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // Load text domain & register custom post type
    public function init()
    {
        load_plugin_textdomain('podcast-importer', false, dirname(plugin_basename(__FILE__)) . '/languages');

        register_post_type(
            'podcast_importer',
            array(
                'labels'              => array(
                    'name'          => esc_html__('Podcast Imports', 'podcast-importer'),
                    'singular_name' => esc_html__('Podcast Import', 'podcast-importer')
                ),
                'public'              => true,
                'has_archive'         => false,
                'supports'            => array('title'),
                'can_export'          => false,
                'exclude_from_search' => true,
                'show_in_admin_bar'   => false,
                'show_in_nav_menus'   => false,
                'publicly_queryable'  => false,
            )
        );
    }

    // Add a new menu link under Tools
    public function add_page()
    {
        add_management_page(esc_attr__('Podcast Importer', 'podcast-importer'), esc_attr__('Podcast Importer', 'podcast-importer'), 'manage_options', 'podcast-imprter', array($this, 'front_init'));
    }

    // Load admin scripts
    public function admin_scripts()
    {
        wp_register_style('podcast_importer_admin_styles', esc_url(plugins_url('/assets/css/admin.css', __FILE__)), false, '1.0.0');
        wp_enqueue_style('podcast_importer_admin_styles');

        wp_register_script('podcast_importer_admin_scripts', esc_url(plugins_url('/assets/js/admin.js', __FILE__)), false, '1.0.0', true);
        wp_enqueue_script('podcast_importer_admin_scripts');
    }

    public function settings_form($post_id_to_update = false)
    {
        if ($post_id_to_update) :
            $post_meta = get_post_meta($post_id_to_update);
        endif; ?>

        <form method="POST" action="" class="podcast_importer_form">
            <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_feed" class="slt-form-label"><?php echo esc_html__('Podcast Feed URL', 'podcast-importer'); ?></label>
            <input required="required" class="podcast_feed" id="<?php echo esc_attr($post_id_to_update); ?>_podcast_feed" type="url" name="podcast_feed" value="<?php echo esc_url(get_post_meta($post_id_to_update, 'podcast_importer_rss_feed', true)); ?>" placeholder="https://www.spreaker.com/show/3069898/episodes/feed"></input>

            <label for="<?php echo esc_attr($post_id_to_update); ?>_post_type_select" class="slt-form-label"><?php echo esc_html__('Post Type', 'podcast-importer'); ?></label>
            <select id="<?php echo esc_attr($post_id_to_update); ?>_post_type_select" name="post_type_select" class="post_type_select">
                <?php $this->post_type_control(get_post_meta($post_id_to_update, 'podcast_importer_post_type', true)); ?>
            </select>

            <div class="clearfaix-slt"></div>

            <label for="<?php echo esc_attr($post_id_to_update); ?>_publish_option_select" class="slt-form-label"><?php echo esc_html__('Post Status', 'podcast-importer'); ?></label>
            <select id="<?php echo esc_attr($post_id_to_update); ?>_publish_option_select" name="publish_option_select">
                <?php $podcast_importer_publish = get_post_meta($post_id_to_update, 'podcast_importer_publish', true); ?>
                <option <?php echo $podcast_importer_publish === 'publish' ? ' selected' : ''; ?> value="publish"><?php echo esc_html__('Publish', 'podcast-importer') ?></option>
                <option <?php echo $podcast_importer_publish === 'draft' ? ' selected' : ''; ?> value="draft"><?php echo esc_html__('Save as Draft', 'podcast-importer') ?></option>
            </select>

            <div class="clearfix-slt"></div>

            <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_author" class="slt-form-label"><?php echo esc_html__('Post Author', 'podcast-importer'); ?></label>

            <?php wp_dropdown_users(array(
                'name'     => 'podcast_importer_author',
                'selected' => get_post_meta($post_id_to_update, 'podcast_importer_author', true)
            )); ?>

            <div class="clearfix-slt"></div>

            <label for="<?php echo esc_attr($post_id_to_update); ?>_post_category_select" class="slt-form-label"><?php echo esc_html__('Post Category (or Categories)', 'podcast-importer'); ?></label>
            <select id="<?php echo esc_attr($post_id_to_update); ?>_post_category_select" name="post_category_select[]" multiple="multiple">
                <?php $this->list_categories(get_post_meta($post_id_to_update, 'podcast_importer_category', true)); ?>
            </select>

            <div class="clearfix-slt"></div>

            <?php if (!$post_id_to_update) : ?>
                <div class="slt-checkbox-container">
                    <input type="checkbox" id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_continuous_import" name="podcast_importer_continuous_import"/>
                    <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_continuous_import"><?php echo esc_html__('Ongoing Import (Enable to continuously import future episodes)', 'podcast-importer') ?></label>
                </div>
            <?php endif; ?>

            <div class="slt-checkbox-container">
                <?php $podcast_importer_images = get_post_meta($post_id_to_update, 'podcast_importer_images', true); ?>

                <input <?php echo $podcast_importer_images === 'on' ? ' checked' : ''; ?> type="checkbox" id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_images" name="podcast_importer_images"/>
                <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_images"><?php echo esc_html__('Import Episode Featured Images', 'podcast-importer') ?></label>
            </div>


            <div class="slt-checkbox-container">
                <?php $podcast_importer_embed_player = get_post_meta($post_id_to_update, 'podcast_importer_embed_player', true); ?>

                <input <?php echo $podcast_importer_embed_player === 'on' ? ' checked' : ''; ?> type="checkbox" id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_embed_player" name="podcast_importer_embed_player"/>
                <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_embed_player"><?php echo esc_html__('Use an embed audio player instead of the default WordPress player (depending on your podcast host)', 'podcast-importer') ?></label>
            </div>

            <div class="settings-wrapper">
                <h3 class="slt-importer-header advanced-options"><?php echo esc_html__('Advanced Options', 'podcast-importer'); ?></h3>
                <div class="advanced-settings-wrapper">
                    <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_content_tag" class="slt-form-label"><?php echo esc_html__('Imported Content Tag', 'podcast-importer'); ?></label>
                    <select id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_content_tag" name="podcast_importer_content_tag">
                        <?php $podcast_importer_content_tag = get_post_meta($post_id_to_update, 'podcast_importer_content_tag', true); ?>
                        <option value="select"><?php echo esc_html__('Select Option', 'podcast-importer') ?></option>
                        <option <?php echo $podcast_importer_content_tag === 'content:encoded' ? ' selected' : ''; ?> value="content:encoded"><?php echo esc_html__('content:encoded', 'podcast-importer') ?></option>
                        <option <?php echo $podcast_importer_content_tag === 'description' ? ' selected' : ''; ?> value="description"><?php echo esc_html__('description', 'podcast-importer') ?></option>
                        <option <?php echo $podcast_importer_content_tag === 'itunes:summary' ? ' selected' : ''; ?> value="itunes:summary"><?php echo esc_html__('itunes:summary', 'podcast-importer') ?></option>
                    </select>

                    <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_truncate_post" class="slt-form-label">
                        <?php echo esc_html__('Truncate Post Content', 'podcast-importer'); ?><br>
                        <em>Optional: Will trim the post content when imported to the character amount below.</em>
                    </label>
                    <input id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_truncate_post" type="number" name="podcast_importer_truncate_post" value="<?php echo esc_attr(get_post_meta($post_id_to_update, 'podcast_importer_truncate_post', true)); ?>"></input>

                    <div class="slt-checkbox-container">
                        <?php $podcast_importer_episode_number = get_post_meta($post_id_to_update, 'podcast_importer_episode_number', true); ?>
                        <input <?php echo $podcast_importer_episode_number === 'on' ? ' checked' : ''; ?> type="checkbox" id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_episode_number" name="podcast_importer_episode_number"/>
                        <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_episode_number"><?php echo esc_html__('Append Episode Number to Post Title', 'podcast-importer') ?></label>
                    </div>

                    <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_prepend_title" class="slt-form-label">
                        <?php echo esc_html__('Prepend Title', 'podcast-importer'); ?><br>
                        <em>Optional: Add <code>[podcast_title]</code> to display the show name.</em>
                    </label>
                    <input type="text" id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_prepend_title" name="podcast_importer_prepend_title" placeholder="Ex: My Podcast" value="<?php echo esc_attr(get_post_meta($post_id_to_update, 'podcast_importer_prepend_title', true)); ?>"></input>
                </div>
            </div>

            <div class="clearfix-slt"></div>

            <input type="hidden" name="action" value="podcast_importer_initialize"/>

            <?php if ($post_id_to_update) : ?>
                <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id_to_update); ?>"/>

                <input class="button button-primary" type="submit" name="update" value="Update"/>
            <?php else : ?>
                <input class="button button-primary" type="submit" name="submit" value="Import Podcast"/>
            <?php endif; ?>

            <?php wp_nonce_field('podcast_importer_form', 'podcast_form_nonce'); ?>
        </form>
    <?php }

    // Main plugin page within the WordPress admin panel
    public function front_init()
    {
        ?>
        <div class="slt-plugin-container">
            <div id="slt-form-container">
                <h2 class="slt-importer-header">
                    <?php echo esc_html__('Import a Podcast', 'podcast-importer'); ?>
                </h2>

                <?php
                if (isset($_POST['update'])) :
                    $post_id = sanitize_text_field($_POST['post_id']);
                    $this->update($post_id);
                endif;

                if (!isset($_POST['submit'])) :
                    $this->settings_form();
                else :
                    if (isset($this)) {
                        $this->podcast_import();
                    }
                endif;
                ?>
            </div>

        </div>


        <?php

        // Create section for existing import processes
        global $blogloop;
        global $post;
        $args = array(
            'post_type'      => 'podcast_importer',
            'posts_per_page' => 99,
        );

        $blogloop = new \WP_Query($args);
        if ($blogloop->have_posts()) :

            ?>

            <div class="slt-plugin-container existing-import-container">
                <h2 class="slt-importer-header"><?php echo esc_html__('Scheduled Imports', 'podcast-importer'); ?></h2>
                <span class="slt-importer-notice"><?php echo esc_html__('Scheduled imports automatically sync once every hour.', 'podcast-importer'); ?></span>
                <ul>
                    <?php while ($blogloop->have_posts()): $blogloop->the_post(); ?>
                        <li>
							<span class="import-process-item">
								<strong><?php the_title(); ?></strong> - <?php echo get_post_meta($post->ID, 'podcast_importer_rss_feed', true); ?>
							</span>

                            <span class="delete-button">
								<a href="<?php echo get_delete_post_link($post->ID, '', true); ?>" class="button button-link-delete">
									<?php echo esc_html__('Delete Import', 'podcast-importer'); ?>
								</a>
							</span>

                            <button data-import-id="<?php echo esc_attr($post->ID); ?>" class="button button-link-edit">
                                Edit Import
                            </button>

                            <div id="edit-import-form--<?php echo esc_attr($post->ID); ?>" class="edit-import-form">
                                <?php $this->settings_form($post->ID); ?>
                            </div>

                            <div class="clearfix-slt"></div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php endif; ?><?php
    }

    public function update($post_id_to_update)
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

        if (isset($_POST['podcast_importer_continuous_import'])) {
            $podcast_importer_continuous = sanitize_text_field($_POST['podcast_importer_continuous_import']);

            update_post_meta($post_id_to_update, 'podcast_importer_continuous', $podcast_importer_continuous);
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

    // Main import function
    public function podcast_import()
    {

        // Check nonce and user capabilities
        if ((check_admin_referer('podcast_importer_form', 'podcast_form_nonce') == true) && (current_user_can('editor') || current_user_can('administrator'))) {

            $posts_added_count = 0;

            // Increase the time limit
            set_time_limit(360);

            // Require relevant WordPress core files for processing images
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            // Parse the RSS/XML feed
            $podcast_importer_rss_feed       = '';
            $podcast_importer_post_type      = 'post';        // default
            $podcast_importer_publish        = 'publish';    // default
            $podcast_importer_author         = 'admin';        // default
            $podcast_importer_category       = '';        // default
            $podcast_importer_continuous     = 'off';        // default
            $podcast_importer_images         = 'off';            // default
            $podcast_importer_episode_number = 'off';  // default
            $podcast_importer_content_tag    = 'content:encoded'; // default
            $podcast_importer_truncate_post  = false; // default
            $podcast_importer_prepend_title  = ''; // default
            $podcast_importer_embed_player   = 'off';    // default

            if (isset($_POST['podcast_feed']) && $_POST['podcast_feed'] != '') {
                $podcast_importer_rss_feed_url = esc_url($_POST['podcast_feed'], array('http', 'https'));
                $podcast_importer_rss_feed     = @simplexml_load_file($podcast_importer_rss_feed_url);
                if (empty($podcast_importer_rss_feed) && !empty($podcast_importer_rss_feed_url)) {
                    $response = wp_remote_get($podcast_importer_rss_feed_url, [
                        'user-agent' => 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0',
                    ]);
                    $result   = wp_remote_retrieve_body($response);
                    if (substr($result, 0, 5) == "<?xml") {
                        $podcast_importer_rss_feed = simplexml_load_string($result);
                    } else {
                        // Feed is not valid, continue and display error below.
                    }
                }
            }
            if (isset($_POST['post_type_select'])) {
                $podcast_importer_post_type = sanitize_text_field($_POST['post_type_select']);
            }
            if (isset($_POST['publish_option_select'])) {
                $podcast_importer_publish = sanitize_text_field($_POST['publish_option_select']);
            }
            if (isset($_POST['post_category_select'])) {
                $podcast_importer_category = array_map('sanitize_text_field', $_POST['post_category_select']);
            }
            if (isset($_POST['podcast_importer_author'])) {
                $podcast_importer_author = sanitize_text_field($_POST['podcast_importer_author']);
            }
            if (isset($_POST['podcast_importer_continuous_import'])) {
                $podcast_importer_continuous = sanitize_text_field($_POST['podcast_importer_continuous_import']);
            }
            if (isset($_POST['podcast_importer_images'])) {
                $podcast_importer_images = sanitize_text_field($_POST['podcast_importer_images']);
            }
            if (isset($_POST['podcast_importer_episode_number'])) {
                $podcast_importer_episode_number = sanitize_text_field($_POST['podcast_importer_episode_number']);
            }
            if (isset($_POST['podcast_importer_embed_player'])) {
                $podcast_importer_embed_player = sanitize_text_field($_POST['podcast_importer_embed_player']);
            }
            if (isset($_POST['podcast_importer_content_tag'])) {
                $podcast_importer_content_tag = sanitize_text_field($_POST['podcast_importer_content_tag']);
            }
            if (isset($_POST['podcast_importer_truncate_post'])) {
                $podcast_importer_truncate_post = sanitize_text_field($_POST['podcast_importer_truncate_post']);
            }
            if (isset($_POST['podcast_importer_prepend_title'])) {
                $podcast_importer_prepend_title = sanitize_text_field($_POST['podcast_importer_prepend_title']);
            }

            // Set up a new post per item that appears in the feed
            if (!empty($podcast_importer_rss_feed)) {

                $episode_count = count($podcast_importer_rss_feed->channel->item);

                for ($i = 0; $i < $episode_count; $i++) {

                    $item           = $podcast_importer_rss_feed->channel->item[$i];
                    $itunes         = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
                    $guid           = $this->sanitize_data($item->guid);
                    $episode_number = $this->sanitize_data($itunes->episode);
                    $season_number  = $this->sanitize_data($itunes->season);

                    // Get episode duration (in seconds or text) and file size (in bytes)
                    $filesize = 0; // default value
                    $filesize = $item->enclosure['length'];
                    $filesize = '' . number_format($filesize / 1048576, 2) . 'M';
                    $duration = $this->sanitize_data($itunes->duration);
                    if ((!empty($duration)) && (strpos($duration, ':') !== false))
                        $duration = $duration;
                    elseif (!empty($duration)) {
                        $duration = gmdate("H:i:s", $duration);
                    } else {
                        $duration = '';
                    }

                    // Ensure posts are published right away (for server/feed timezone conflicts)
                    if (strtotime((string)$item->pubDate) < current_time('timestamp')) {
                        $timestamp_post_date = strtotime((string)$item->pubDate);
                    } else {
                        $timestamp_post_date = current_time('timestamp');
                    }

                    $post_date = date('Y-m-d H:i:s', $timestamp_post_date);

                    if (($podcast_importer_episode_number == 'on') && ($this->sanitize_data($itunes->episode) != '')) {
                        $post_title = $this->sanitize_data($itunes->episode) . ': ' . $this->sanitize_data($item->title);
                    } else {
                        $post_title = $this->sanitize_data($item->title);
                    }

                    if (isset($podcast_importer_prepend_title)) {
                        $podcast_importer_prepend_title = str_replace('[podcast_title]', $this->sanitize_data($podcast_importer_rss_feed->channel->title), $podcast_importer_prepend_title);

                        $post_title = $podcast_importer_prepend_title . ' ' . $post_title;
                    }

                    // Set up audio as a shortcode and remove query variables
                    $audio_url             = (string)$item->enclosure['url'];
                    $audio_url             = preg_replace('/(?s:.*)(https?:\/\/(?:[\w\-\.]+[^#?\s]+)(?:\.mp3))(?s:.*)/', '$1', $audio_url);
                    $audio_url             = preg_replace('/(?s:.*)(https?:\/\/(?:[\w\-\.]+[^#?\s]+)(?:\.m4a))(?s:.*)/', '$1', $audio_url);
                    $feed_link_url         = (string)$item->link;
                    $host_checker          = false;
                    $match_image_filenames = $this->match_image_names($podcast_importer_rss_feed_url);
                    if (!empty($feed_link_url)) {
                        $parsed_feed_url  = parse_url($feed_link_url);
                        $parsed_feed_host = $parsed_feed_url['host'];
                    } else {
                        $parsed_feed_host = '';
                    }

                    if (($podcast_importer_embed_player == 'on') && (isset($parsed_feed_host) || isset($podcast_importer_rss_feed_url))) {
                        if (preg_match('/fireside.fm/i', $podcast_importer_rss_feed_url)) {
                            $feed_link_url = (string)$item->children('fireside', true)->playerEmbedCode;
                        }
                        if (preg_match('/omny.fm/i', $podcast_importer_rss_feed_url) || preg_match('/omnycontent.com/i', $podcast_importer_rss_feed_url)) {
                            $feed_link_url = (string)$item->children('media', true)->content->children('media', true)->player->attributes()->url;
                        }
                        if (preg_match('/libsyn.com/i', $podcast_importer_rss_feed_url) || preg_match('/omnycontent.com/i', $podcast_importer_rss_feed_url)) {
                            $feed_link_url = (string)$item->children('libsyn', true)->itemId;
                        }
                        $audio_shortcode = $this->embed_validator($parsed_feed_host, $feed_link_url, $audio_url, $podcast_importer_rss_feed_url, $guid);
                        $host_checker    = $this->embed_host_checker($parsed_feed_host, $podcast_importer_rss_feed_url);
                    } else {
                        $audio_shortcode = '[audio src="' . esc_url($audio_url) . '"][/audio]';
                    }

                    // Grab the content
                    if (!empty($item->children('itunes', true)->summary) && $podcast_importer_content_tag === 'itunes:summary') {
                        $parsed_content = $this->sanitize_data($item->children('itunes', true)->summary);
                    } elseif (!empty($item->children('itunes', true)->encoded) && $podcast_importer_content_tag === 'content:encoded') {
                        $parsed_content = $this->sanitize_data($item->children('content', true)->encoded);
                    } elseif (!empty($item->description) && $podcast_importer_content_tag === 'description') {
                        $parsed_content = $this->sanitize_data($item->description);
                        // If no preference cuts the mustard, try all the defaults
                    } elseif (!empty($item->children('content', true)->encoded)) {
                        $parsed_content = $this->sanitize_data($item->children('content', true)->encoded);
                    } elseif (!empty($item->description)) {
                        $parsed_content = $this->sanitize_data($item->description);
                    } else {
                        $parsed_content = $this->sanitize_data($itunes->summary);
                    }

                    if ($podcast_importer_truncate_post) {
                        $parsed_content = substr($parsed_content, 0, intval($podcast_importer_truncate_post));
                    }

                    $post_content = $parsed_content;

                    // Create post data
                    $post = array(
                        'post_author'  => $podcast_importer_author,
                        'post_content' => $post_content,
                        'post_date'    => $post_date,
                        'post_excerpt' => $this->sanitize_data($itunes->subtitle),
                        'post_status'  => $podcast_importer_publish,
                        'post_type'    => $podcast_importer_post_type,
                        'post_title'   => $post_title,
                    );


                    // Create the post
                    global $wpdb;
                    $post_id;
                    // Check if post already exists, if so - skip. First we'll look for the GUID, then at the title.
                    if (!empty($guid) && $guid != '') {
                        $query      = "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE (meta_key = 'podcast_importer_imported_guid' AND meta_value LIKE '%$guid%')";
                        $guid_count = intval($wpdb->get_var($query));
                    } else {
                        $guid_count = 0;
                    }
                    if ($guid_count == 0) {

                        if (0 === post_exists($post_title, "", "", $podcast_importer_post_type)) {

                            $post_id = wp_insert_post($post);

                            // Continue if the import generate errors
                            if (is_wp_error($post_id)) {
                                continue;
                            }

                            // Add GUID for each post
                            add_post_meta($post_id, 'podcast_importer_imported_guid', $guid, true);
                            add_post_meta($post_id, 'podcast_importer_episode_number', $episode_number, true);
                            add_post_meta($post_id, 'podcast_importer_season_number', $season_number, true);
                            add_post_meta($post_id, 'podcast_importer_external_embed', $audio_shortcode, true);
                            add_post_meta($post_id, 'podcast_audio_file', $audio_url, true);
                            add_post_meta($post_id, 'podcast_audio_url', $audio_url, true);
                            add_post_meta($post_id, 'podcast_duration', $duration, true);
                            add_post_meta($post_id, 'podcast_filesize', $filesize, true);
                            add_post_meta($post_id, 'podcast_enclosure', $audio_url, true);
                            add_post_meta($post_id, 'podcast_author', $this->sanitize_data($itunes->author), true);
                            add_post_meta($post_id, 'podcast_publish_date', $post_date, true);
                            add_post_meta($post_id, '_castpress_audio_url', $audio_url, true);

                            // Add episode categories
                            if (!empty($podcast_importer_category)) {
                                if ($podcast_importer_post_type == 'podcast') {
                                    wp_set_post_terms($post_id, $podcast_importer_category, 'series', false);
                                } else {
                                    wp_set_post_terms($post_id, $podcast_importer_category, 'category', false);
                                }
                            }

                            // Add episode image
                            if (isset($itunes) && isset($item)) { // Check again that feed is not empty
                                if ($podcast_importer_images == 'on') {

                                    // Grab image URL and file name
                                    if ($itunes && $itunes->image && $itunes->image->attributes() && $itunes->image->attributes()->href) {

                                        $filename = basename(parse_url($itunes->image->attributes()->href)['path']);
                                        $filename = (string)$filename;

                                        // Check if image does not exist in the database and upload it. Otherwise attach the existing image to the post
                                        $query          = "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_value LIKE '%$filename%'";
                                        $filename_count = intval($wpdb->get_var($query));

                                        if ($filename_count == 0 || $match_image_filenames == false) {
                                            $img_to_import = (string)$itunes->image->attributes()->href;
                                            if (!post_exists($post_title, '', '', 'attachment') || $match_image_filenames == false) {
                                                $attachment = get_page_by_title($post_title, OBJECT, 'attachment');
                                                if (empty($attachment) || $match_image_filenames == false) {
                                                    add_action('add_attachment', array($this, 'import_itunes_image'));
                                                    media_sideload_image($img_to_import, $post_id, $item->title);
                                                    remove_action('add_attachment', array($this, 'import_itunes_image'));
                                                }
                                            }
                                        } else {
                                            $get_upload_dir     = wp_upload_dir()['baseurl'] . '/';
                                            $filename           = pathinfo($filename, PATHINFO_FILENAME); // returns $filename with no extension
                                            $query_path_to_file = "SELECT meta_value FROM {$wpdb->postmeta} WHERE (meta_key = '_wp_attached_file' AND meta_value LIKE '%$filename%')";
                                            $filename_path      = $wpdb->get_var($query_path_to_file);
                                            $filename_path      = str_replace('-scaled', '', $filename_path);
                                            $image_src          = $get_upload_dir . $filename_path;
                                            $image_id           = $this->get_image_id($image_src);
                                            set_post_thumbnail($post_id, $image_id);
                                        }
                                    }
                                }
                            }

                            do_action('podcast_importer_after_post_import', $post_id, $podcast_importer_rss_feed, $item);

                            $posts_added_count++; // Count successfully imported episodes

                        }
                    }
                }

                // Return success/error messages
                if ($posts_added_count == 0 && $episode_count != 0) { // No episodes imported due to duplicated titles.
                    echo esc_html__('No new episodes imported, all episodes already existing in WordPress!', 'podcast-importer');
                    echo '<br><br><span class="slt-existing-post-notice">' . esc_html__('If you have existing draft, private or trashed posts with the same title as your episodes, delete those and run the importer again', 'podcast-importer') . '</span>';
                } elseif ($episode_count == 0) { // No episodes existing within feed.
                    echo esc_html__('Error! Your feed does not contain any episodes.', 'podcast-importer');
                } else {
                    echo '<strong>' . esc_html__('Success! Imported ', 'podcast-importer') . $posts_added_count . esc_html__(' out of ', 'podcast-importer') . $episode_count . esc_html__(' episodes', 'podcast-importer') . '</strong>';
                }

                // Check if scheduled import checked and already exists
                if ($podcast_importer_continuous == 'on') {
                    if (0 === post_exists($this->sanitize_data($podcast_importer_rss_feed->channel->title), "", "", 'podcast_importer')) {
                        // Create new entry for the scheduled/ongoing import.
                        $import_post    = array(
                            'post_title'  => $this->sanitize_data($podcast_importer_rss_feed->channel->title),
                            'post_type'   => 'podcast_importer',
                            'post_status' => 'publish',
                        );
                        $post_import_id = wp_insert_post($import_post);
                        add_post_meta($post_import_id, 'podcast_importer_rss_feed', $podcast_importer_rss_feed_url, true);
                        add_post_meta($post_import_id, 'podcast_importer_post_type', $podcast_importer_post_type, true);
                        add_post_meta($post_import_id, 'podcast_importer_publish', $podcast_importer_publish, true);
                        add_post_meta($post_import_id, 'podcast_importer_category', $podcast_importer_category, true);
                        add_post_meta($post_import_id, 'podcast_importer_images', $podcast_importer_images, true);
                        add_post_meta($post_import_id, 'podcast_importer_episode_number', $podcast_importer_episode_number, true);
                        add_post_meta($post_import_id, 'podcast_importer_author', $podcast_importer_author, true);
                        add_post_meta($post_import_id, 'podcast_importer_embed_player', $podcast_importer_embed_player, true);
                        add_post_meta($post_import_id, 'podcast_importer_content_tag', $podcast_importer_content_tag, true);
                        add_post_meta($post_import_id, 'podcast_importer_truncate_post', $podcast_importer_truncate_post, true);
                        add_post_meta($post_import_id, 'podcast_importer_prepend_title', $podcast_importer_prepend_title, true);

                    } else {
                        echo '<br><br>' . esc_html__('This podcast is already being scheduled for import. Delete the previous schedule to create a new one.', 'podcast-importer') . '<br><br>';
                    }

                    // Set up cron job for imports
                    add_action('podcast_importer_cron', array($this, 'scheduled_podcast_import'));

                    if (!wp_next_scheduled('podcast_importer_cron')) {
                        wp_schedule_event(current_time('timestamp'), 'hourly', 'podcast_importer_cron');
                    }
                }
            } else {
                echo '<strong>' . esc_html__('Podcast Feed Error! Please use a valid RSS feed URL.', 'podcast-importer') . '</strong>';
            }
        }
    }


    function scheduled_podcast_import()
    {

        // Load post.php class for post manipulations during cron
        if ((!is_admin()) || (!function_exists('post_exists'))) {
            require_once(ABSPATH . 'wp-admin/includes/post.php');
        }

        // Increase the time limit
        set_time_limit(360);

        // Require relevant WordPress core files for processing images
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Query all existing shceduled imports
        global $wpdb;
        global $blogloop;
        global $post;
        $args = array(
            'post_type'      => 'podcast_importer',
            'post_status'    => 'publish',
            'posts_per_page' => 999,
        );

        $blogloop = new \WP_Query($args);

        if ($blogloop->have_posts()) {

            while ($blogloop->have_posts()) {

                $blogloop->the_post();

                // Grab pre-saved vars per post
                $podcast_importer_rss_feed_url   = get_post_meta($post->ID, 'podcast_importer_rss_feed', true);
                $podcast_importer_post_type      = get_post_meta($post->ID, 'podcast_importer_post_type', true);
                $podcast_importer_publish        = get_post_meta($post->ID, 'podcast_importer_publish', true);
                $podcast_importer_category       = get_post_meta($post->ID, 'podcast_importer_category', true);
                $podcast_importer_images         = get_post_meta($post->ID, 'podcast_importer_images', true);
                $podcast_importer_episode_number = get_post_meta($post->ID, 'podcast_importer_episode_number', true);
                $podcast_importer_author         = get_post_meta($post->ID, 'podcast_importer_author', true);
                $podcast_importer_embed_player   = get_post_meta($post->ID, 'podcast_importer_embed_player', true);
                $podcast_importer_content_tag    = get_post_meta($post->ID, 'podcast_importer_content_tag', true);
                $podcast_importer_truncate_post  = get_post_meta($post->ID, 'podcast_importer_truncate_post', true);
                $podcast_importer_prepend_title  = get_post_meta($post->ID, 'podcast_importer_prepend_title', true);

                $podcast_importer_rss_feed = @simplexml_load_file($podcast_importer_rss_feed_url);
                if (empty($podcast_importer_rss_feed) && !empty($podcast_importer_rss_feed_url)) {
                    $response = wp_remote_get($podcast_importer_rss_feed_url, [
                        'user-agent' => 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0',
                    ]);
                    $result   = wp_remote_retrieve_body($response);
                    if (substr($result, 0, 5) == "<?xml") {
                        $podcast_importer_rss_feed = simplexml_load_string($result);
                    } else {
                        // Feed is not valid, continue and display error below.
                    }
                }

                // Parse the RSS/XML feed
                if (!empty($podcast_importer_rss_feed)) {

                    $episode_count = count($podcast_importer_rss_feed->channel->item);

                    for ($i = 0; $i < $episode_count; $i++) {

                        $item           = $podcast_importer_rss_feed->channel->item[$i];
                        $itunes         = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
                        $post_author    = $podcast_importer_author;
                        $guid           = $this->sanitize_data($item->guid);
                        $episode_number = $this->sanitize_data($itunes->episode);
                        $season_number  = $this->sanitize_data($itunes->season);

                        // Get episode duration (in seconds or text) and file size (in bytes)
                        $filesize = 0; // default value
                        $filesize = $item->enclosure['length'];
                        $filesize = '' . number_format($filesize / 1048576, 2) . 'M';
                        $duration = $this->sanitize_data($itunes->duration);
                        if ((!empty($duration)) && (strpos($duration, ':') !== false))
                            $duration = $duration;
                        elseif (!empty($duration)) {
                            $duration = gmdate("H:i:s", $duration);
                        } else {
                            $duration = '';
                        }

                        // Ensure posts are published right away (for server/feed timezone conflicts)
                        if (strtotime((string)$item->pubDate) < current_time('timestamp')) {
                            $timestamp_post_date = strtotime((string)$item->pubDate);
                        } else {
                            $timestamp_post_date = current_time('timestamp');
                        }

                        $post_date = date('Y-m-d H:i:s', $timestamp_post_date);


                        if (($podcast_importer_episode_number == 'on') && ($this->sanitize_data($itunes->episode) != '')) {
                            $post_title = $this->sanitize_data($itunes->episode) . ': ' . $this->sanitize_data($item->title);
                        } else {
                            $post_title = $this->sanitize_data($item->title);
                        }

                        if (isset($podcast_importer_prepend_title)) {
                            $podcast_importer_prepend_title = str_replace('[podcast_title]', $this->sanitize_data($podcast_importer_rss_feed->channel->title), $podcast_importer_prepend_title);

                            $post_title = $podcast_importer_prepend_title . ' ' . $post_title;
                        }

                        // Set up audio as a shortcode and remove query variables
                        $audio_url             = (string)$item->enclosure['url'];
                        $audio_url             = preg_replace('/(?s:.*)(https?:\/\/(?:[\w\-\.]+[^#?\s]+)(?:\.mp3))(?s:.*)/', '$1', $audio_url);
                        $audio_url             = preg_replace('/(?s:.*)(https?:\/\/(?:[\w\-\.]+[^#?\s]+)(?:\.m4a))(?s:.*)/', '$1', $audio_url);
                        $feed_link_url         = (string)$item->link;
                        $host_checker          = false;
                        $match_image_filenames = $this->match_image_names($podcast_importer_rss_feed_url);
                        if (!empty($feed_link_url)) {
                            $parsed_feed_url  = parse_url($feed_link_url);
                            $parsed_feed_host = $parsed_feed_url['host'];
                        } else {
                            $parsed_feed_host = '';
                        }

                        if (($podcast_importer_embed_player == 'on') && (isset($parsed_feed_host) || isset($podcast_importer_rss_feed_url))) {
                            if (preg_match('/fireside.fm/i', $podcast_importer_rss_feed_url)) {
                                $feed_link_url = (string)$item->children('fireside', true)->playerEmbedCode;
                            }
                            if (preg_match('/omny.fm/i', $podcast_importer_rss_feed_url) || preg_match('/omnycontent.com/i', $podcast_importer_rss_feed_url)) {
                                $feed_link_url = (string)$item->children('media', true)->content->children('media', true)->player->attributes()->url;
                            }
                            $audio_shortcode = $this->embed_validator($parsed_feed_host, $feed_link_url, $audio_url, $podcast_importer_rss_feed_url, $guid);
                            $host_checker    = $this->embed_host_checker($parsed_feed_host, $podcast_importer_rss_feed_url);
                        } else {
                            $audio_shortcode = '[audio src="' . esc_url($audio_url) . '"][/audio]';
                        }

                        // Set up the post content
                        if (!empty($item->children('itunes', true)->summary) && $podcast_importer_content_tag === 'itunes:summary') {
                            $parsed_content = $this->sanitize_data($item->children('itunes', true)->summary);
                        } elseif (!empty($item->children('itunes', true)->encoded) && $podcast_importer_content_tag === 'content:encoded') {
                            $parsed_content = $this->sanitize_data($item->children('content', true)->encoded);
                        } elseif (!empty($item->description) && $podcast_importer_content_tag === 'description') {
                            $parsed_content = $this->sanitize_data($item->description);
                            // If no preference cuts the mustard, try all the defaults
                        } elseif (!empty($item->children('content', true)->encoded)) {
                            $parsed_content = $this->sanitize_data($item->children('content', true)->encoded);
                        } elseif (!empty($item->description)) {
                            $parsed_content = $this->sanitize_data($item->description);
                        } else {
                            $parsed_content = $this->sanitize_data($itunes->summary);
                        }

                        if ($podcast_importer_truncate_post) {
                            $parsed_content = substr($parsed_content, 0, intval($podcast_importer_truncate_post));
                        }

                        $post_content = $parsed_content;

                        // Create the post content
                        $post = array(
                            'post_author'  => $post_author,
                            'post_content' => $post_content,
                            'post_date'    => $post_date,
                            'post_excerpt' => $this->sanitize_data($itunes->subtitle),
                            'post_status'  => $podcast_importer_publish,
                            'post_title'   => $post_title,
                            'post_type'    => $podcast_importer_post_type,
                        );

                        // Create the post
                        global $wpdb;
                        $post_id;

                        // Check if post already exists, if so - skip. First we'll look for the GUID, then at the title.
                        if (!empty($guid) && $guid != '') {
                            $query      = "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE (meta_key = 'podcast_importer_imported_guid' AND meta_value LIKE '%$guid%')";
                            $guid_count = intval($wpdb->get_var($query));
                        } else {
                            $guid_count = 0;
                        }
                        if ($guid_count == 0) {

                            if (0 === post_exists($post_title, "", "", $podcast_importer_post_type)) {

                                $post_id = wp_insert_post($post);

                                // Continue if the import process errors
                                if (is_wp_error($post_id)) {
                                    //continue;
                                }

                                // Add GUID for each post
                                add_post_meta($post_id, 'podcast_importer_imported_guid', $guid, true);
                                add_post_meta($post_id, 'podcast_importer_episode_number', $episode_number, true);
                                add_post_meta($post_id, 'podcast_importer_season_number', $season_number, true);
                                add_post_meta($post_id, 'podcast_importer_external_embed', $audio_shortcode, true);
                                add_post_meta($post_id, 'podcast_audio_file', $audio_url, true);
                                add_post_meta($post_id, 'podcast_audio_url', $audio_url, true);
                                add_post_meta($post_id, 'podcast_duration', $duration, true);
                                add_post_meta($post_id, 'podcast_filesize', $filesize, true);
                                add_post_meta($post_id, 'podcast_enclosure', $audio_url, true);
                                add_post_meta($post_id, 'podcast_author', $this->sanitize_data($itunes->author), true);
                                add_post_meta($post_id, 'podcast_publish_date', $post_date, true);
                                add_post_meta($post_id, '_castpress_audio_url', $audio_url, true);

                                // Add episode categories
                                if (!empty($podcast_importer_category)) {
                                    if ($podcast_importer_post_type == 'podcast') {
                                        wp_set_post_terms($post_id, $podcast_importer_category, 'series', false);
                                    } else {
                                        wp_set_post_terms($post_id, $podcast_importer_category, 'category', false);
                                    }
                                }

                                // Add episode image
                                if (isset($itunes) && isset($item)) { // Workaround for "Node no longer exists" error
                                    if ($podcast_importer_images == 'on') {

                                        if ($itunes && $itunes->image && $itunes->image->attributes() && $itunes->image->attributes()->href) {

                                            $filename = basename(parse_url($itunes->image->attributes()->href)['path']);
                                            $filename = (string)$filename;

                                            // Check if image does not exist in the database and upload it. Otherwise attach the existing image to the post
                                            $query          = "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_value LIKE '%$filename%'";
                                            $filename_count = intval($wpdb->get_var($query));
                                            if ($filename_count == 0 || $match_image_filenames == false) {
                                                $img_to_import = (string)$itunes->image->attributes()->href;
                                                if (!post_exists($post_title, '', '', 'attachment') || $match_image_filenames == false) {
                                                    $attachment = get_page_by_title($post_title, OBJECT, 'attachment');
                                                    if (empty($attachment) || $match_image_filenames == false) {
                                                        add_action('add_attachment', array($this, 'import_itunes_image'));
                                                        media_sideload_image($img_to_import, $post_id, $item->title);
                                                        remove_action('add_attachment', array($this, 'import_itunes_image'));
                                                    }
                                                }
                                            } else {
                                                $get_upload_dir     = wp_upload_dir()['baseurl'] . '/';
                                                $filename           = pathinfo($filename, PATHINFO_FILENAME); // returns $filename with no extension
                                                $query_path_to_file = "SELECT meta_value FROM {$wpdb->postmeta} WHERE (meta_key = '_wp_attached_file' AND meta_value LIKE '%$filename%')";
                                                $filename_path      = $wpdb->get_var($query_path_to_file);
                                                $filename_path      = str_replace('-scaled', '', $filename_path);
                                                $image_src          = $get_upload_dir . $filename_path;
                                                $image_id           = $this->get_image_id($image_src);
                                                set_post_thumbnail($post_id, $image_id);
                                            }
                                        }
                                    }
                                }

                                do_action('podcast_importer_after_post_scheduled_import', $post_id, $podcast_importer_rss_feed, $item);
                            }
                        }
                    }
                }
            }
        }
    }


    // Grab the images and save the image id with the post
    function import_itunes_image($att_id)
    {
        $post_img = get_post($att_id);
        update_post_meta($post_img->post_parent, '_thumbnail_id', $att_id);
    }

    // Get image ID by URL
    function get_image_id($image_url)
    {
        global $wpdb;
        $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url));
        if (!empty($attachment)) {
            return $attachment[0];
        }
    }

    // Query post types and list as options
    function post_type_control($value)
    {
        $cpts         = get_post_types(array('public' => true, 'show_in_nav_menus' => true));
        $exclude_cpts = array('elementor_library', 'podcast_importer', 'attachment', 'product', 'page');

        foreach ($exclude_cpts as $exclude_cpt) {
            unset($cpts[$exclude_cpt]);
        }

        foreach ($cpts as $cpt) {
            $selected = $value === $cpt ? ' selected' : '';
            echo '<option' . esc_attr($selected) . ' value="' . esc_attr($cpt) . '">' . esc_html($cpt) . '</option>';
        }
    }

    // Query categories and list as options
    function list_categories($values)
    {
        if (function_exists('ssp_episodes')) {
            $args = array('taxonomy' => 'series', 'hide_empty' => false,);
        } else {
            $args = array('taxonomy' => 'category', 'hide_empty' => false,);
        }

        $cats = get_categories($args);
        foreach ($cats as $cat) {
            if (is_array($values)) {
                $selected = in_array($cat->term_id, $values) ? ' selected' : '';
            } else {
                $selected = '';
            }
            echo '<option' . esc_attr($selected) . ' value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
        }
    }

    // Return an embed audio player depending on the podcast hosting provider
    function embed_validator($parsed_feed_host, $embed_url, $audio_url, $rss_feed_url, $guid)
    {
        if (strpos($parsed_feed_host, 'transistor.fm') !== false) {

            $fixed_share_url = str_replace('/s/', '/e/', $embed_url);
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" width="100%" height="180" frameborder="0" scrolling="no" seamless="true" style="width:100%; height:180px;"></iframe>';

        } elseif (strpos($parsed_feed_host, 'anchor.fm') !== false) {

            $fixed_share_url = str_replace('/episodes/', '/embed/episodes/', $embed_url);
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" height="180px" width="100%" frameborder="0" scrolling="no" style="width:100%; height:180px;"></iframe>';

        } elseif (strpos($parsed_feed_host, 'simplecast.com') !== false) {

            $simplecast_response = wp_remote_get('https://api.simplecast.com/oembed?url=' . rawurlencode($embed_url));
            $simplecast_json     = json_decode($simplecast_response['body'], true);
            $simplecast_html     = $simplecast_json['html'];
            preg_match('/src="([^"]+)"/', $simplecast_html, $match);
            $fixed_share_url = $match[1];
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" height="200px" width="100%" frameborder="no" scrolling="no" style="width:100%; height:200px;"></iframe>';

        } elseif (strpos($parsed_feed_host, 'whooshkaa.com') !== false) {

            $whooshkaa_audio_id = substr($embed_url, strpos($embed_url, "?id=") + 4);
            $fixed_share_url    = 'https://webplayer.whooshkaa.com/player/episode/id/' . $whooshkaa_audio_id . '?theme=light';
            $audio_shortcode    = '<iframe src="' . esc_url($fixed_share_url) . '" width="100%" height="200" frameborder="0" scrolling="no" style="width: 100%; height: 200px"></iframe>';

        } elseif ((strpos($rss_feed_url, 'omny.fm') !== false) || (strpos($rss_feed_url, 'omnycontent.com') !== false)) {

            $audio_shortcode = '<iframe src="' . esc_url($embed_url) . '" width="100%" height="180px" scrolling="no"  frameborder="0" style="width:100%; height:180px;"></iframe>';

        } elseif (strpos($parsed_feed_host, 'podbean.com') !== false) {

            $audio_shortcode = wp_oembed_get(esc_url($embed_url)); // oEmbed

        } elseif (strpos($rss_feed_url, 'megaphone.fm') !== false) {
            $megaphone_audio_link = explode('megaphone.fm/', $audio_url);
            $megaphone_audio_id   = explode('.', $megaphone_audio_link[1]);
            $fixed_share_url      = 'https://playlist.megaphone.fm/?e=' . $megaphone_audio_id[0];
            $audio_shortcode      = '<iframe src="' . esc_url($fixed_share_url) . '" width="100%" height="210" scrolling="no"  frameborder="0" style="width: 100%; height: 210px"></iframe>';

        } elseif (strpos($rss_feed_url, 'captivate.fm') !== false) {
            $captivate_audio_link = explode('media/', $audio_url);
            $captivate_audio_id   = explode('/', $captivate_audio_link[1]);
            $fixed_share_url      = 'https://player.captivate.fm/episode/' . $captivate_audio_id[0];
            $audio_shortcode      = '<iframe src="' . esc_url($fixed_share_url) . '" width="100%" height="170" scrolling="no"  frameborder="0" style="width: 100%; height: 170px"></iframe>';

        } elseif (strpos($audio_url, 'buzzsprout.com') !== false) {
            $buzzsprout_audio_url = explode('.mp3', $audio_url);
            $fixed_share_url      = $buzzsprout_audio_url[0] . '?iframe=true';
            $audio_shortcode      = '<iframe src="' . esc_url($fixed_share_url) . '" scrolling="no" width="100%" scrolling="no"  height="200" frameborder="0" style="width: 100%; height: 200px"></iframe>';

        } elseif (strpos($audio_url, 'pinecast.com') !== false) {
            $pinecast_audio_url   = explode('.mp3', $audio_url);
            $pinecast_episode_url = str_replace('/listen/', '/player/', $pinecast_audio_url[0]);
            $fixed_share_url      = $pinecast_episode_url . '?theme=flat';
            $audio_shortcode      = '<iframe src="' . esc_url($fixed_share_url) . '" scrolling="no" width="100%" scrolling="no"  height="200" frameborder="0" style="width: 100%; height: 200px"></iframe>';

        } elseif (strpos($rss_feed_url, 'feed.ausha.co') !== false) {
            $ausha_audio_link = explode('audio.ausha.co/', $audio_url);
            $ausha_audio_id   = explode('.mp3', $ausha_audio_link[1]);
            $podcastId        = $ausha_audio_id[0];
            $fixed_share_url  = 'https://widget.ausha.co/index.html?podcastId=' . $podcastId . '&display=horizontal&v=2';
            $audio_shortcode  = '<iframe frameborder="0" height="200px" scrolling="no"  width="100%" src="' . esc_url($fixed_share_url) . '"></iframe>';

        } elseif (strpos($rss_feed_url, 'spreaker.com') !== false) {
            $fixed_share_url = explode('/episode/', $guid);
            if (isset($fixed_share_url[1])) {
                $fixed_share_url = 'https://widget.spreaker.com/player?episode_id=' . $fixed_share_url[1];
                $audio_shortcode = '<iframe frameborder="0" height="200" scrolling="no" width="100%" src="' . esc_url($fixed_share_url) . '"></iframe>';
            } else {
                $audio_shortcode = '[audio src="' . esc_url($audio_url) . '"][/audio]';
            }

        } elseif (strpos($rss_feed_url, 'fireside.fm') !== false) {
            $audio_shortcode = $embed_url . '</iframe>';

        } elseif (strpos($rss_feed_url, 'libsyn.com') !== false) {
            $fixed_share_url = 'https://html5-player.libsyn.com/embed/episode/id/' . $embed_url;
            $audio_shortcode = '<iframe frameborder="0" height="90" scrolling="no" width="100%" src="' . esc_url($fixed_share_url) . '" ></iframe>';

        } elseif (strpos($rss_feed_url, 'audioboom.com') !== false) {
            $fixed_share_url = str_replace('/posts/', '/boos/', $embed_url);
            $audio_shortcode = '<iframe frameborder="0" height="220" scrolling="no" width="100%" src="' . esc_url($fixed_share_url) . '/embed/v4"></iframe>';

        } else {

            $audio_shortcode = '[audio src="' . esc_url($audio_url) . '"][/audio]';

        }

        return $audio_shortcode;
    }

    function embed_host_checker($parsed_feed_host, $rss_feed_url)
    {
        if ((preg_match('/transistor.fm|anchor.fm|fireside.fm|simplecast.com|spreaker.com|whooshkaa.com|omny.fm|omnycontent.com|megaphone.fm|podbean.com/i', $parsed_feed_host)) || (preg_match('/megaphone.fm|captivate.fm|ausha.co|omny.fm|omnycontent.com|pinecast.com|audioboom.com|buzzsprout.com/i', $rss_feed_url))) {
            $host_checker = true;
        } else {
            $host_checker = false;
        }
        return $host_checker;
    }

    function match_image_names($rss_feed_url)
    {
        if (preg_match('/buzzsprout.com|omny.fm|omnycontent.com/i', $rss_feed_url)) {
            $match_image_filenames = false;
        } else {
            $match_image_filenames = true;
        }
        return $match_image_filenames;
    }

    // Parse and sanitize data from RSS/XML
    function sanitize_data($data)
    {
        $content = array();

        trim((string)$data);
        $data = str_replace("&nbsp;", "", $data);
        if (preg_match('/^<!\[CDATA\[(.*)\]\]>$/is', $data, $content)) {
            $data = $content[1];
        } else {
            $data = html_entity_decode($data);
        }

        return $data;
    }

}