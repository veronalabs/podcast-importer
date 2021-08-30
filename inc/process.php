<?php

namespace WP_PODCAST_IMPORTER;

class Process
{
    public static function run()
    {
        global $wpdb;

        // Init Number Added Post
        $posts_added_count = 0;

        // Require relevant WordPress core files for processing images
        Helper::LoadMediaCoreWordPressHelper();

        // Parse the RSS/XML feed
        $podcast_importer_rss_feed = '';
        $podcast_importer_post_type = 'post';
        $podcast_importer_publish = 'publish';
        $podcast_importer_author = 'admin';
        $podcast_importer_category = '';
        $podcast_importer_images = 'off';
        $podcast_importer_episode_number = 'off';
        $podcast_importer_content_tag = 'content:encoded';
        $podcast_importer_truncate_post = false;
        $podcast_importer_prepend_title = '';
        $podcast_importer_embed_player = 'off';

        // Get Rss Feed
        if (isset($_POST['podcast_feed']) && $_POST['podcast_feed'] != '') {
            $podcast_importer_rss_feed_url = esc_url($_POST['podcast_feed'], array('http', 'https'));
            $podcast_importer_rss_feed = Helper::getRssFeedPodCast($podcast_importer_rss_feed_url);
        }

        // Get List Of Settings From Form
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

                $item = $podcast_importer_rss_feed->channel->item[$i];
                $itunes = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
                $guid = Helper::sanitizeData($item->guid);
                $episode_number = Helper::sanitizeData($itunes->episode);
                $season_number = Helper::sanitizeData($itunes->season);

                // Get episode duration (in seconds or text) and file size (in bytes)
                $filesize = 0;
                $filesize = $item->enclosure['length'];
                $filesize = '' . number_format($filesize / 1048576, 2) . 'M';
                $duration = Helper::sanitizeData($itunes->duration);
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

                if (($podcast_importer_episode_number == 'on') && (Helper::sanitizeData($itunes->episode) != '')) {
                    $post_title = Helper::sanitizeData($itunes->episode) . ': ' . Helper::sanitizeData($item->title);
                } else {
                    $post_title = Helper::sanitizeData($item->title);
                }

                if (isset($podcast_importer_prepend_title)) {
                    $podcast_importer_prepend_title = str_replace('[podcast_title]', Helper::sanitizeData($podcast_importer_rss_feed->channel->title), $podcast_importer_prepend_title);
                    $post_title = $podcast_importer_prepend_title . ' ' . $post_title;
                }

                // Set up audio as a shortcode and remove query variables
                $audio_url = Helper::sanitizeAudioUrl((string)$item->enclosure['url']);
                $feed_link_url = (string)$item->link;
                $host_checker = false;
                $match_image_filenames = Helper::matchImageNames($podcast_importer_rss_feed_url);
                if (!empty($feed_link_url)) {
                    $parsed_feed_url = parse_url($feed_link_url);
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
                    $audio_shortcode = Helper::embedValidator($parsed_feed_host, $feed_link_url, $audio_url, $podcast_importer_rss_feed_url, $guid);
                    $host_checker = Helper::embedHostChecker($parsed_feed_host, $podcast_importer_rss_feed_url);
                } else {
                    $audio_shortcode = '[audio src="' . esc_url($audio_url) . '"][/audio]';
                }

                // Grab the content
                $parsed_content = Helper::parseContent($item, $podcast_importer_content_tag, $itunes);
                if ($podcast_importer_truncate_post) {
                    $parsed_content = substr($parsed_content, 0, intval($podcast_importer_truncate_post));
                }
                $post_content = $parsed_content;

                // Check if post already exists, if so - skip. First we'll look for the GUID, then at the title.
                if (!empty($guid) && $guid != '') {
                    $query = "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE (meta_key = 'podcast_importer_imported_guid' AND meta_value LIKE '%$guid%')";
                    $guid_count = intval($wpdb->get_var($query));
                } else {
                    $guid_count = 0;
                }
                if ($guid_count == 0) {
                    if (0 === post_exists($post_title, "", "", $podcast_importer_post_type)) {

                        // Create Post
                        $post_id = wp_insert_post(array(
                            'post_author' => $podcast_importer_author,
                            'post_content' => $post_content,
                            'post_date' => $post_date,
                            'post_excerpt' => Helper::sanitizeData($itunes->subtitle),
                            'post_status' => $podcast_importer_publish,
                            'post_type' => $podcast_importer_post_type,
                            'post_title' => $post_title,
                        ));

                        // Continue if the import generate errors
                        if (is_wp_error($post_id)) {
                            continue;
                        }

                        // Add GUID for each post
                        update_post_meta($post_id, 'podcast_importer_imported_guid', $guid, true);
                        update_post_meta($post_id, 'podcast_importer_episode_number', $episode_number, true);
                        update_post_meta($post_id, 'podcast_importer_season_number', $season_number, true);
                        update_post_meta($post_id, 'podcast_importer_external_embed', $audio_shortcode, true);
                        update_post_meta($post_id, 'podcast_audio_file', $audio_url, true);
                        update_post_meta($post_id, 'podcast_audio_url', $audio_url, true);
                        update_post_meta($post_id, 'podcast_duration', $duration, true);
                        update_post_meta($post_id, 'podcast_filesize', $filesize, true);
                        update_post_meta($post_id, 'podcast_enclosure', $audio_url, true);
                        update_post_meta($post_id, 'podcast_author', Helper::sanitizeData($itunes->author), true);
                        update_post_meta($post_id, 'podcast_publish_date', $post_date, true);
                        update_post_meta($post_id, '_castpress_audio_url', $audio_url, true);

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
                                    $query = "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_value LIKE '%$filename%'";
                                    $filename_count = intval($wpdb->get_var($query));

                                    if ($filename_count == 0 || $match_image_filenames == false) {
                                        $img_to_import = (string)$itunes->image->attributes()->href;
                                        if (!post_exists($post_title, '', '', 'attachment') || $match_image_filenames == false) {
                                            $attachment = get_page_by_title($post_title, OBJECT, 'attachment');
                                            if (empty($attachment) || $match_image_filenames == false) {
                                                add_action('add_attachment', array(__CLASS__, 'importItunesImage'));
                                                media_sideload_image($img_to_import, $post_id, $item->title);
                                                remove_action('add_attachment', array(__CLASS__, 'importItunesImage'));
                                            }
                                        }
                                    } else {

                                        // Set Post Thumbnail
                                        Helper::setPostThumbnail($filename, $post_id);
                                    }
                                }
                            }
                        }

                        do_action('action_after_podcast_import', $post_id, $podcast_importer_rss_feed, $item);
                        $posts_added_count++;
                    }
                }
            }

            // Return success/error messages
            if ($posts_added_count == 0 && $episode_count != 0) { // No episodes imported due to duplicated titles.
                echo esc_html__('No new episodes imported, all episodes already existing in WordPress!', 'podcast-importer');
                echo '<br><br><span class="ver-existing-post-notice">' . esc_html__('If you have existing draft, private or trashed posts with the same title as your episodes, delete those and run the importer again', 'podcast-importer') . '</span>';
            } elseif ($episode_count == 0) { // No episodes existing within feed.
                echo esc_html__('Error! Your feed does not contain any episodes.', 'podcast-importer');
            } else {
                echo '<strong>' . esc_html__('Success! Imported ', 'podcast-importer') . $posts_added_count . esc_html__(' out of ', 'podcast-importer') . $episode_count . esc_html__(' episodes', 'podcast-importer') . '</strong>';
            }

        } else {
            echo '<strong>' . esc_html__('Podcast Feed Error! Please use a valid RSS feed URL.', 'podcast-importer') . '</strong>';
        }
    }

    public static function importItunesImage($att_id)
    {
        $post_img = get_post($att_id);
        update_post_meta($post_img->post_parent, '_thumbnail_id', $att_id);
    }
}

new Process();