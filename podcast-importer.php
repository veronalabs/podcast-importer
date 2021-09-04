<?php
/**
 * Plugin Name:       Podcast Importer
 * Description:       A simple podcast import plugin with feed.
 * Version:           2.0
 * Author:            VeronaLabs
 * Author URI:        https://veronalabs.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       podcast-importer
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_PODCAST_IMPORTER
{
    /**
     * Minimum PHP version required
     *
     * @var string
     */
    private $min_php = '5.4.0';

    /**
     * Use plugin's translated strings
     *
     * @var string
     * @default true
     */
    public static $use_i18n = true;

    /**
     * URL to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_url;

    /**
     * Path to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_path;

    /**
     * Path to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_version;

    /**
     * get Plugin Basename
     *
     * @var string
     */
    public static $plugin_basename;

    /**
     * Plugin instance.
     *
     * @see get_instance()
     * @status Core
     */
    protected static $_instance = null;

    /**
     * Access this plugin’s working instance
     *
     * @wp-hook plugins_loaded
     * @return  object of this class
     * @since   2012.09.13
     */
    public static function instance()
    {
        null === self::$_instance and self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * WP_PODCAST_IMPORTER constructor.
     */
    public function __construct()
    {

        /*
         * Check Require Php Version
         */
        if (version_compare(PHP_VERSION, $this->min_php, '<=')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }

        /*
         * Define Variable
         */
        $this->define_constants();

        /*
         * include files
         */
        $this->includes();

        /*
         * init Wordpress hook
         */
        $this->init_hooks();

        /*
         * Plugin Loaded Action
         */
        do_action('wp_podcast_importer_loaded');
    }

    /**
     * Define Constant
     */
    public function define_constants()
    {

        /*
         * Get Plugin Data
         */
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data(__FILE__);

        /*
         * Set Plugin Version
         */
        self::$plugin_version = $plugin_data['Version'];

        /*
         * Set Plugin Url
         */
        self::$plugin_url = plugins_url('', __FILE__);

        /*
         * Set Plugin Path
         */
        self::$plugin_path = plugin_dir_path(__FILE__);

        /*
         * Set Plugin BaseName
         */
        self::$plugin_basename = plugin_basename(__FILE__);
    }

    /**
     * include Plugin Require File
     */
    public function includes()
    {
        /*
         * autoload plugin files
         */
        include_once dirname(__FILE__) . '/inc/config/i18n.php';
        include_once dirname(__FILE__) . '/inc/config/install.php';
        include_once dirname(__FILE__) . '/inc/config/uninstall.php';
        include_once dirname(__FILE__) . '/inc/helper.php';
        include_once dirname(__FILE__) . '/inc/admin/admin.php';
        include_once dirname(__FILE__) . '/inc/process.php';
    }

    /**
     * Used for regular plugin work.
     *
     * @wp-hook init Hook
     * @return  void
     */
    public function init_hooks()
    {

        /*
         * Activation Plugin Hook
         */
        register_activation_hook(__FILE__, array('\WP_PODCAST_IMPORTER\config\install', 'run_install'));

        /*
         * Uninstall Plugin Hook
         */
        register_deactivation_hook(__FILE__, array('\WP_PODCAST_IMPORTER\config\uninstall', 'run_uninstall'));

        /*
         * Load i18n
         */
        if (self::$use_i18n === true) {
            new \WP_PODCAST_IMPORTER\config\i18n('podcast-importer');
        }
    }

    /**
     * Show notice about PHP version
     *
     * @return void
     */
    function php_version_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $error = __('Your installed PHP Version is: ', 'podcast-importer') . PHP_VERSION . '. ';
        $error .= __('The <strong>WP PodCast Importer</strong> plugin requires PHP version <strong>', 'podcast-importer') . $this->min_php . __('</strong> or greater.', 'podcast-importer');
        ?>
        <div class="error">
            <p><?php printf($error); ?></p>
        </div>
        <?php
    }

    /**
     * Write WordPress Log
     *
     * @param $log
     */
    public static function log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

/**
 * Main instance of WP_Plugin.
 *
 * @since  1.1.0
 */
function wp_podcast_importer()
{
    return WP_PODCAST_IMPORTER::instance();
}

// Global for backwards compatibility.
$GLOBALS['podcast-importer'] = wp_podcast_importer();
