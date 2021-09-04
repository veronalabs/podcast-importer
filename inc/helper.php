<?php

namespace WP_PODCAST_IMPORTER;

class Helper
{
    public static function WP_Query($arg = array())
    {
        // Create Empty List
        $list = array();

        // Prepare Params
        $default = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => '-1',
            'order' => 'ASC',
            'fields' => 'ids',
            'cache_results' => false,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters' => true
        );
        $args = wp_parse_args($arg, $default);

        // Get Data
        $query = new \WP_Query($args);

        // Added To List
        foreach ($query->posts as $ID) {
            $list[] = $ID;
        }

        return $list;
    }

    public static function LoadMediaCoreWordPressHelper()
    {
        require_once(ABSPATH . 'wp-admin/includes/post.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    public static function getUploadDir()
    {
        return wp_upload_dir();
    }

    public static function getRssFeedPodCast($podcast_importer_rss_feed_url)
    {
        $podcast_importer_rss_feed = @simplexml_load_file($podcast_importer_rss_feed_url);
        if (empty($podcast_importer_rss_feed) && !empty($podcast_importer_rss_feed_url)) {
            $response = wp_remote_get($podcast_importer_rss_feed_url, [
                'user-agent' => 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0',
            ]);
            $result = wp_remote_retrieve_body($response);
            if (substr($result, 0, 5) == "<?xml") {
                $podcast_importer_rss_feed = simplexml_load_string($result);
            } else {
                // Feed is not valid, continue and display error below.
            }
        }

        return $podcast_importer_rss_feed;
    }

    public static function setPostThumbnail($filename, $post_id)
    {
        global $wpdb;

        $get_upload_dir = Helper::getUploadDir();
        $get_upload_dir = $get_upload_dir['baseurl'] . '/';
        $filename = pathinfo($filename, PATHINFO_FILENAME); // returns $filename with no extension
        $query_path_to_file = "SELECT meta_value FROM {$wpdb->postmeta} WHERE (meta_key = '_wp_attached_file' AND meta_value LIKE '%$filename%')";
        $filename_path = $wpdb->get_var($query_path_to_file);
        $filename_path = str_replace('-scaled', '', $filename_path);
        $image_src = $get_upload_dir . $filename_path;
        $image_id = Helper::getImageIDByUrl($image_src);
        if (!is_null($image_id)) {
            set_post_thumbnail($post_id, $image_id);
        }
    }

    public static function parseContent($item, $podcast_importer_content_tag, $itunes)
    {
        if (!empty($item->children('itunes', true)->summary) && $podcast_importer_content_tag === 'itunes:summary') {
            $parsed_content = Helper::sanitizeData($item->children('itunes', true)->summary);
        } elseif (!empty($item->children('itunes', true)->encoded) && $podcast_importer_content_tag === 'content:encoded') {
            $parsed_content = Helper::sanitizeData($item->children('content', true)->encoded);
        } elseif (!empty($item->description) && $podcast_importer_content_tag === 'description') {
            $parsed_content = Helper::sanitizeData($item->description);
            // If no preference cuts the mustard, try all the defaults
        } elseif (!empty($item->children('content', true)->encoded)) {
            $parsed_content = Helper::sanitizeData($item->children('content', true)->encoded);
        } elseif (!empty($item->description)) {
            $parsed_content = Helper::sanitizeData($item->description);
        } else {
            $parsed_content = Helper::sanitizeData($itunes->summary);
        }

        return $parsed_content;
    }

    public static function sanitizeAudioUrl($audio_url)
    {
        $audio_url = preg_replace('/(?s:.*)(https?:\/\/(?:[\w\-\.]+[^#?\s]+)(?:\.mp3))(?s:.*)/', '$1', $audio_url);
        $audio_url = preg_replace('/(?s:.*)(https?:\/\/(?:[\w\-\.]+[^#?\s]+)(?:\.m4a))(?s:.*)/', '$1', $audio_url);
        return $audio_url;
    }

    public static function getImageIDByUrl($image_url)
    {
        global $wpdb;
        $attachment = $wpdb->get_col(
            $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url)
        );
        if (!empty($attachment)) {
            return $attachment[0];
        }

        return null;
    }

    public static function postTypeControl($value)
    {
        $cpts = get_post_types(
            array(
                'public' => true,
                'show_in_nav_menus' => true
            )
        );
        $exclude_cpts = array('elementor_library', 'podcast_importer', 'attachment', 'product', 'page');
        foreach ($exclude_cpts as $exclude_cpt) {
            unset($cpts[$exclude_cpt]);
        }
        foreach ($cpts as $cpt) {
            $selected = $value === $cpt ? ' selected' : '';
            echo '<option' . esc_attr($selected) . ' value="' . esc_attr($cpt) . '">' . esc_html($cpt) . '</option>';
        }
    }

    public static function listTerms($values)
    {
        $args = array('taxonomy' => 'category', 'hide_empty' => false,);
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

    public static function embedValidator($parsed_feed_host, $embed_url, $audio_url, $rss_feed_url, $guid)
    {
        if (strpos($parsed_feed_host, 'transistor.fm') !== false) {

            $fixed_share_url = str_replace('/s/', '/e/', $embed_url);
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" width="100%" height="180" frameborder="0" scrolling="no" seamless="true" style="width:100%; height:180px;"></iframe>';

        } elseif (strpos($parsed_feed_host, 'anchor.fm') !== false) {

            $fixed_share_url = str_replace('/episodes/', '/embed/episodes/', $embed_url);
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" height="180px" width="100%" frameborder="0" scrolling="no" style="width:100%; height:180px;"></iframe>';

        } elseif (strpos($parsed_feed_host, 'simplecast.com') !== false) {

            $simplecast_response = wp_remote_get('https://api.simplecast.com/oembed?url=' . rawurlencode($embed_url));
            $simplecast_json = json_decode($simplecast_response['body'], true);
            $simplecast_html = $simplecast_json['html'];
            preg_match('/src="([^"]+)"/', $simplecast_html, $match);
            $fixed_share_url = $match[1];
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" height="200px" width="100%" frameborder="no" scrolling="no" style="width:100%; height:200px;"></iframe>';

        } elseif (strpos($parsed_feed_host, 'whooshkaa.com') !== false) {

            $whooshkaa_audio_id = substr($embed_url, strpos($embed_url, "?id=") + 4);
            $fixed_share_url = 'https://webplayer.whooshkaa.com/player/episode/id/' . $whooshkaa_audio_id . '?theme=light';
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" width="100%" height="200" frameborder="0" scrolling="no" style="width: 100%; height: 200px"></iframe>';

        } elseif ((strpos($rss_feed_url, 'omny.fm') !== false) || (strpos($rss_feed_url, 'omnycontent.com') !== false)) {

            $audio_shortcode = '<iframe src="' . esc_url($embed_url) . '" width="100%" height="180px" scrolling="no"  frameborder="0" style="width:100%; height:180px;"></iframe>';

        } elseif (strpos($parsed_feed_host, 'podbean.com') !== false) {

            $audio_shortcode = wp_oembed_get(esc_url($embed_url)); // oEmbed

        } elseif (strpos($rss_feed_url, 'megaphone.fm') !== false) {
            $megaphone_audio_link = explode('megaphone.fm/', $audio_url);
            $megaphone_audio_id = explode('.', $megaphone_audio_link[1]);
            $fixed_share_url = 'https://playlist.megaphone.fm/?e=' . $megaphone_audio_id[0];
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" width="100%" height="210" scrolling="no"  frameborder="0" style="width: 100%; height: 210px"></iframe>';

        } elseif (strpos($rss_feed_url, 'captivate.fm') !== false) {
            $captivate_audio_link = explode('media/', $audio_url);
            $captivate_audio_id = explode('/', $captivate_audio_link[1]);
            $fixed_share_url = 'https://player.captivate.fm/episode/' . $captivate_audio_id[0];
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" width="100%" height="170" scrolling="no"  frameborder="0" style="width: 100%; height: 170px"></iframe>';

        } elseif (strpos($audio_url, 'buzzsprout.com') !== false) {
            $buzzsprout_audio_url = explode('.mp3', $audio_url);
            $fixed_share_url = $buzzsprout_audio_url[0] . '?iframe=true';
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" scrolling="no" width="100%" scrolling="no"  height="200" frameborder="0" style="width: 100%; height: 200px"></iframe>';

        } elseif (strpos($audio_url, 'pinecast.com') !== false) {
            $pinecast_audio_url = explode('.mp3', $audio_url);
            $pinecast_episode_url = str_replace('/listen/', '/player/', $pinecast_audio_url[0]);
            $fixed_share_url = $pinecast_episode_url . '?theme=flat';
            $audio_shortcode = '<iframe src="' . esc_url($fixed_share_url) . '" scrolling="no" width="100%" scrolling="no"  height="200" frameborder="0" style="width: 100%; height: 200px"></iframe>';

        } elseif (strpos($rss_feed_url, 'feed.ausha.co') !== false) {
            $ausha_audio_link = explode('audio.ausha.co/', $audio_url);
            $ausha_audio_id = explode('.mp3', $ausha_audio_link[1]);
            $podcastId = $ausha_audio_id[0];
            $fixed_share_url = 'https://widget.ausha.co/index.html?podcastId=' . $podcastId . '&display=horizontal&v=2';
            $audio_shortcode = '<iframe frameborder="0" height="200px" scrolling="no"  width="100%" src="' . esc_url($fixed_share_url) . '"></iframe>';

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

    public static function embedHostChecker($parsed_feed_host, $rss_feed_url)
    {
        if ((preg_match('/transistor.fm|anchor.fm|fireside.fm|simplecast.com|spreaker.com|whooshkaa.com|omny.fm|omnycontent.com|megaphone.fm|podbean.com/i', $parsed_feed_host)) || (preg_match('/megaphone.fm|captivate.fm|ausha.co|omny.fm|omnycontent.com|pinecast.com|audioboom.com|buzzsprout.com/i', $rss_feed_url))) {
            $host_checker = true;
        } else {
            $host_checker = false;
        }
        return $host_checker;
    }

    public static function matchImageNames($rss_feed_url)
    {
        if (preg_match('/buzzsprout.com|omny.fm|omnycontent.com/i', $rss_feed_url)) {
            $match_image_filenames = false;
        } else {
            $match_image_filenames = true;
        }
        return $match_image_filenames;
    }

    public static function sanitizeData($data)
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

new Helper();
