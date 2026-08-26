<?php
/**
 * Plugin Name: HDWebmobile Live Shopping with Agora
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-live-shopping-with-agora/
 * Description: Enable Live Shopping functionality with Agora.io and WooCommerce.
 * Version: 1.0.4
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-live-shopping-with-agora
 * Domain Path: /languages
 */

namespace htrxuan\hdlsa;

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('HDLSA_VERSION', '1.0.4');
define('HDLSA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDLSA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once HDLSA_PLUGIN_DIR . 'includes/class-hdlsa-admin.php';
require_once HDLSA_PLUGIN_DIR . 'includes/class-hdlsa-activator.php';
require_once HDLSA_PLUGIN_DIR . 'includes/class-hdlsa-ajax.php';


/**
 * Main Plugin Class
 */
class HDLSA_Init
{
    /**
     * @var HDLSA_Admin
     */
    public $admin;

    /**
     * @var HDLSA_Ajax
     */
    public $ajax;

    public function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies()
    {
        $this->admin = new HDLSA_Admin();
        $this->ajax = new HDLSA_Ajax();
    }

    private function init_hooks()
    {
        add_action('init', array($this, 'register_post_type'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('hdlsa_stream', array($this, 'render_shortcode'));
    }

    public function register_post_type()
    {
        $labels = array(
            'name' => _x('Live Streams', 'Post Type General Name', 'hdwebmobile-live-shopping-with-agora'),
            'singular_name' => _x('Live Stream', 'Post Type Singular Name', 'hdwebmobile-live-shopping-with-agora'),
            'menu_name' => __('Live Shopping', 'hdwebmobile-live-shopping-with-agora'),
            'all_items' => __('All Streams', 'hdwebmobile-live-shopping-with-agora'),
            'add_new_item' => __('Add New Stream', 'hdwebmobile-live-shopping-with-agora'),
            'add_new' => __('Add New', 'hdwebmobile-live-shopping-with-agora'),
            'new_item' => __('New Stream', 'hdwebmobile-live-shopping-with-agora'),
            'edit_item' => __('Edit Stream', 'hdwebmobile-live-shopping-with-agora'),
            'view_item' => __('View Stream', 'hdwebmobile-live-shopping-with-agora'),
            'search_items' => __('Search Streams', 'hdwebmobile-live-shopping-with-agora'),
        );
        $args = array(
            'label' => __('Live Stream', 'hdwebmobile-live-shopping-with-agora'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'thumbnail'),
            'public' => false, // Hidden from frontend
            'show_ui' => true, // Still visible in admin
            'show_in_menu' => true,
            'query_var' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'menu_icon' => 'dashicons-video-alt3',
            'menu_position' => 5,
            'has_archive' => false,
            'rewrite' => false,
        );
        register_post_type('hdlsa_livestream', $args);
    }

    public function enqueue_scripts()
    {
        global $post;
        $has_shortcode = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'hdlsa_stream');

        if (is_singular('hdlsa_livestream') || $has_shortcode) {
            // Agora SDK (Bundled locally to comply with WordPress.org standards)
            wp_enqueue_script('agora-rtc-sdk', HDLSA_PLUGIN_URL . 'assets/js/vendor/agora-rtc-sdk.js', array(), '4.20.0', true);
            wp_enqueue_script('agora-rtm-sdk', HDLSA_PLUGIN_URL . 'assets/js/vendor/agora-rtm-sdk.js', array(), '1.5.1', true);

            wp_enqueue_style('hdlsa-style', HDLSA_PLUGIN_URL . 'assets/css/hdlsa-style.css', array(), HDLSA_VERSION);

            // Determine $stream_id
            $stream_id = 0;
            if (is_singular('hdlsa_livestream')) {
                $stream_id = $post->ID;
            } elseif ($has_shortcode) {
                // Try to find the ID from shortcode attributes in content (rough check)
                if (preg_match('/\[hdlsa_stream\s+id="(\d+)"\]/', $post->post_content, $matches)) {
                    $stream_id = intval($matches[1]);
                }
            }

            if (!$stream_id)
                return;

            $stream_post = get_post($stream_id);
            if (!$stream_post || $stream_post->post_type !== 'hdlsa_livestream')
                return;

            // Check if current user is the author (Broadcaster)
            $current_user_id = get_current_user_id();
            $is_broadcaster = ($stream_post->post_author == $current_user_id);

            // Get Host Name
            $host_name = get_post_meta($stream_id, '_hdlsa_host_name', true);
            if (empty($host_name)) {
                $host_name = get_the_author_meta('display_name', $stream_post->post_author);
            }

            // Get Host Avatar URL
            $host_avatar_id = get_post_meta($stream_id, '_hdlsa_host_avatar_id', true);
            $host_avatar_url = wp_get_attachment_image_url($host_avatar_id, 'thumbnail');
            if (!$host_avatar_url) {
                $host_avatar_url = get_avatar_url($stream_post->post_author, array('size' => 100));
            }

            $bad_words_str = get_option('hdlsa_bad_words_list', '');
            $bad_words = array();
            if ($bad_words_str) {
                $bad_words = array_map('trim', explode(',', $bad_words_str));
                $bad_words = array_filter($bad_words); // Filter out empty strings
            }

            $params = array(
                'appID' => get_option('hdlsa_agora_app_id'),
                'channelName' => 'channel_' . $stream_id,
                'ajax_url' => admin_url('admin-ajax.php'),
                'post_id' => $stream_id,
                'host_name' => $host_name,
                'host_avatar' => $host_avatar_url,
                'nonce' => wp_create_nonce('hdlsa_ajax_nonce'),
                'bad_words' => $bad_words
            );

            if ($is_broadcaster) {
                wp_enqueue_script('hdlsa-broadcaster', HDLSA_PLUGIN_URL . 'assets/js/hdlsa-broadcaster.js', array(
                    'jquery',
                    'agora-rtc-sdk',
                    'agora-rtm-sdk'
                ), HDLSA_VERSION, true);
                wp_localize_script('hdlsa-broadcaster', 'hdlsa_params', $params);
            } else {
                // Ensure WooCommerce AJAX scripts are loaded
                if (function_exists('WC')) {
                    wp_enqueue_script('wc-add-to-cart');
                }

                wp_enqueue_script('hdlsa-viewer', HDLSA_PLUGIN_URL . 'assets/js/hdlsa-viewer.js', array(
                    'jquery',
                    'agora-rtc-sdk',
                    'agora-rtm-sdk'
                ), HDLSA_VERSION, true);
                wp_localize_script('hdlsa-viewer', 'hdlsa_params', $params);
            }
        }
    }

    public function render_shortcode($atts)
    {
        $a = shortcode_atts(array(
            'id' => get_the_ID(),
        ), $atts);

        $post_id = intval($a['id']);
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'hdlsa_livestream') {
            return '<p>' . esc_html__('Invalid livestream ID.', 'hdwebmobile-live-shopping-with-agora') . '</p>';
        }

        return $this->render_stream_ui($post_id);
    }

    public function render_stream_ui($post_id)
    {
        $post = get_post($post_id);
        $current_user_id = get_current_user_id();

        ob_start();

        if ($post->post_author == $current_user_id) {
            include $this->locate_template('stream-broadcaster.php');
        } else {
            include $this->locate_template('stream-viewer.php');
        }

        return ob_get_clean();
    }


    public function locate_template($template_name)
    {
        // 1. Check in theme folder: your-theme/hdwebmobile-live-shopping-with-agora/template.php
        $theme_template = locate_template('hdwebmobile-live-shopping-with-agora/' . $template_name);
        if ($theme_template) {
            return $theme_template;
        }

        // 2. Check in theme root (optional): your-theme/template.php
        $theme_root_template = locate_template($template_name);
        if ($theme_root_template) {
            return $theme_root_template;
        }

        // 3. Fallback to plugin templates
        $plugin_template = HDLSA_PLUGIN_DIR . 'templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }
}

/**
 * The code that runs during plugin activation.
 */
function activate_hdwebmobile_live_shopping_with_agora()
{
    require_once HDLSA_PLUGIN_DIR . 'includes/class-hdlsa-activator.php';
    HDLSA_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_hdwebmobile_live_shopping_with_agora()
{
    require_once HDLSA_PLUGIN_DIR . 'includes/class-hdlsa-activator.php';
    HDLSA_Activator::deactivate();
}

register_activation_hook(__FILE__, __NAMESPACE__ . '\\activate_hdwebmobile_live_shopping_with_agora');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\deactivate_hdwebmobile_live_shopping_with_agora');

// Initialize the plugin
function hdlsa_run_agora_live_shopping()
{
    $plugin = new HDLSA_Init();
}
hdlsa_run_agora_live_shopping();