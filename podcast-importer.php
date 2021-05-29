<?php
/**
 * Plugin Name:       Podcast Importer
 * Description:       A simple podcast import plugin with ongoing podcast feed import features.
 * Version:           1.0.0
 * Author:            VeronaLabs
 * Author URI:        https://veronalabs.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       podcast-importer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('PODCAST_IMPORTER_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-podcast-importer-activator.php
 */
function activate_podcast_importer()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-podcast-importer-activator.php';
    Podcast_Importer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-podcast-importer-deactivator.php
 */
function deactivate_podcast_importer()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-podcast-importer-deactivator.php';
    Podcast_Importer_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_podcast_importer');
register_deactivation_hook(__FILE__, 'deactivate_podcast_importer');


/**
 * Allow iframes in generated post content
 */
function podcast_importer_allow_iframe($tags, $context)
{
    if ('post' === $context) {
        $tags['iframe'] = array(
            'src'             => true,
            'height'          => true,
            'width'           => true,
            'style'           => true,
            'frameborder'     => true,
            'allowfullscreen' => true,
            'scrolling'       => true,
            'seamless'        => true,
        );
    }
    return $tags;
}

add_filter('wp_kses_allowed_html', 'podcast_importer_allow_iframe', 10, 2);

/**
 * Add oEmbed providers
 */
function podcast_importer_oembed_providers($providers)
{
    $providers['#https?://(.+).podbean.com/e/.+#i'] = array('https://api.podbean.com/v1/oembed', true);
    return $providers;
}

add_filter('oembed_providers', 'podcast_importer_oembed_providers');

/* Add 'Setting' link to plugins page */
function podcast_importer_add_settings_link($links)
{
    $settings_link = '<a href="tools.php?page=podcast-imprter">' . esc_attr__('Settings', 'podcast-importer') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'podcast_importer_add_settings_link');


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks
 */
require plugin_dir_path(__FILE__) . 'class-podcast-importer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function podcast_importer()
{
    new Podcast_Importer();
}

podcast_importer();
