<?php

namespace htrxuan\hdlsa;

use DateTime;
use DateTimeZone;

if (!defined('ABSPATH')) {
    exit;
}

class HDLSA_Ajax
{

    public function __construct()
    {
        add_action('wp_ajax_hdlsa_get_product_details', array($this, 'get_product_details'));
        add_action('wp_ajax_nopriv_hdlsa_get_product_details', array($this, 'get_product_details'));
        add_action('wp_ajax_hdlsa_get_stream_products', array($this, 'get_stream_products'));
        add_action('wp_ajax_nopriv_hdlsa_get_stream_products', array($this, 'get_stream_products'));
        add_action('wp_ajax_hdlsa_generate_agora_tokens', array($this, 'generate_agora_tokens'));
        add_action('wp_ajax_nopriv_hdlsa_generate_agora_tokens', array($this, 'generate_agora_tokens'));
        add_action('wp_ajax_hdlsa_save_chat_message', array($this, 'save_chat_message'));
        add_action('wp_ajax_nopriv_hdlsa_save_chat_message', array($this, 'save_chat_message'));
        add_action('wp_ajax_hdlsa_get_chat_history', array($this, 'get_chat_history'));
        add_action('wp_ajax_nopriv_hdlsa_get_chat_history', array($this, 'get_chat_history'));
        add_action('wp_ajax_hdlsa_record_stream_event', array($this, 'record_stream_event'));
        add_action('wp_ajax_nopriv_hdlsa_record_stream_event', array($this, 'record_stream_event'));
        add_action('wp_ajax_hdlsa_record_viewer_event', array($this, 'record_viewer_event'));
        add_action('wp_ajax_nopriv_hdlsa_record_viewer_event', array($this, 'record_viewer_event'));
    }

    public function get_product_details()
    {
        check_ajax_referer('hdlsa_ajax_nonce', 'nonce');

        if (!isset($_GET['product_id'])) {
            wp_send_json_error(__('Missing product ID', 'hdwebmobile-live-shopping-with-agora'));
        }

        $product_id = intval($_GET['product_id']);
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(__('Product not found', 'hdwebmobile-live-shopping-with-agora'));
        }

        $image_url = wp_get_attachment_image_url($product->get_image_id(), 'medium');
        if (!$image_url) {
            $image_url = wc_placeholder_img_src('medium');
        }

        $data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'image' => $image_url,
            'url' => $product->get_permalink(),
            'add_to_cart_url' => $this->get_clean_add_to_cart_url($product)
        );

        wp_send_json_success($data);
    }

    public function get_stream_products()
    {
        check_ajax_referer('hdlsa_ajax_nonce', 'nonce');

        if (!isset($_GET['post_id'])) {
            wp_send_json_error(__('Missing Post ID', 'hdwebmobile-live-shopping-with-agora'));
        }

        $post_id = intval($_GET['post_id']);
        $selected_products = get_post_meta($post_id, '_hdlsa_selected_products', true);

        if (!$selected_products || !is_array($selected_products)) {
            wp_send_json_error(__('No products found', 'hdwebmobile-live-shopping-with-agora'));
        }

        $products_data = array();
        foreach ($selected_products as $pid) {
            $product = wc_get_product($pid);
            if ($product) {
                $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
                if (!$image_url) {
                    $image_url = wc_placeholder_img_src('thumbnail');
                }

                $products_data[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'image' => $image_url,
                    'url' => $product->get_permalink(),
                    'add_to_cart_url' => $this->get_clean_add_to_cart_url($product)
                );
            }
        }

        wp_send_json_success($products_data);
    }

    public function generate_agora_tokens()
    {
        check_ajax_referer('hdlsa_ajax_nonce', 'nonce');

        $appID = get_option('hdlsa_agora_app_id');
        $appCertificate = get_option('hdlsa_agora_app_certificate');
        $channelName = isset($_GET['channelName']) ? sanitize_text_field(wp_unslash($_GET['channelName'])) : '';
        $uid = isset($_GET['uid']) ? sanitize_text_field(wp_unslash($_GET['uid'])) : 0;
        $rtm_uid = isset($_GET['rtm_uid']) ? sanitize_text_field(wp_unslash($_GET['rtm_uid'])) : '';
        // UID for RTM

        if (!$appID) {
            wp_send_json_error('App ID not configured');
        }

        $results = array(
            'rtcToken' => null,
            'rtmToken' => null
        );

        // If App Certificate is present, generate tokens
        if ($appCertificate) {
            require_once HDLSA_PLUGIN_DIR . 'includes/AccessToken.php';
            require_once HDLSA_PLUGIN_DIR . 'includes/RtcTokenBuilder.php';
            require_once HDLSA_PLUGIN_DIR . 'includes/RtmTokenBuilder.php';

            $expireTimeInSeconds = 3600;
            $currentTimestamp = (new DateTime("now", new DateTimeZone('UTC')))->getTimestamp();
            $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

            // RTC Token
            if ($channelName) {
                $role = HDLSA_RtcTokenBuilder::RolePublisher;
                $results['rtcToken'] = HDLSA_RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs);
            }

            // RTM Token
            // Use provided rtm_uid or fallback to current user id if missing
            if (empty($rtm_uid)) {
                $rtm_uid = (string) get_current_user_id();
            }

            $results['rtmToken'] = HDLSA_RtmTokenBuilder::buildToken($appID, $appCertificate, $rtm_uid, HDLSA_RtmTokenBuilder::RoleRtmUser, $privilegeExpiredTs);
        }

        $results['rtc_token_generated'] = !empty($results['rtcToken']);
        $results['rtm_token_generated'] = !empty($results['rtmToken']);
        $results['has_certificate'] = !empty($appCertificate);

        wp_send_json_success($results);
    }

    public function save_chat_message()
    {
        check_ajax_referer('hdlsa_ajax_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $user = isset($_POST['user']) ? sanitize_text_field(wp_unslash($_POST['user'])) : 'Guest';
        $msg = isset($_POST['msg']) ? sanitize_textarea_field(wp_unslash($_POST['msg'])) : '';

        if (!$post_id || !$user || !$msg) {
            wp_send_json_error('Invalid data');
        }

        // Server-side bad words filter
        $bad_words_str = get_option('hdlsa_bad_words_list', '');
        if ($bad_words_str) {
            $bad_words = array_map('trim', explode(',', $bad_words_str));
            foreach ($bad_words as $word) {
                if (!empty($word)) {
                    $msg = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', '***', $msg);
                }
            }
        }

        $history = get_post_meta($post_id, '_hdlsa_chat_history', true);
        if (!is_array($history)) {
            $history = array();
        }

        $history[] = array(
            'user' => $user,
            'msg' => $msg,
            'time' => time()
        );

        // Keep last 50
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        update_post_meta($post_id, '_hdlsa_chat_history', $history);
        wp_send_json_success();
    }

    public function get_chat_history()
    {
        check_ajax_referer('hdlsa_ajax_nonce', 'nonce');

        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('Missing Post ID');
        }

        $history = get_post_meta($post_id, '_hdlsa_chat_history', true);
        if (!is_array($history)) {
            $history = array();
        }

        wp_send_json_success($history);
    }

    public function record_stream_event()
    {
        check_ajax_referer('hdlsa_ajax_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        // Security: Only the post author (broadcaster) should be able to trigger stream events
        $post = get_post($post_id);
        if (!$post || $post->post_author != get_current_user_id()) {
            wp_send_json_error('Permission denied');
        }

        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : ''; // start, end

        if (!$post_id || !$type) {
            wp_send_json_error('Invalid data');
        }

        if ($type === 'start') {
            update_post_meta($post_id, '_hdlsa_stream_start_time', time());
            // Reset sessions/stats on new start if desired, or keep cumulative. 
            // Usually, each GO LIVE might be a fresh session, but for now we'll just update start/end.
        } elseif ($type === 'end') {
            update_post_meta($post_id, '_hdlsa_stream_end_time', time());
        }

        wp_send_json_success();
    }

    public function record_viewer_event()
    {
        check_ajax_referer('hdlsa_ajax_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
        $uid = isset($_POST['uid']) ? sanitize_text_field(wp_unslash($_POST['uid'])) : '';
        $count = isset($_POST['count']) ? intval($_POST['count']) : 0;

        if (!$post_id || !$type) {
            wp_send_json_error('Invalid data');
        }

        if ($type === 'join' && $uid) {
            // Unique Viewer tracking
            $total_viewers = get_post_meta($post_id, '_hdlsa_total_viewers', true);
            if (!is_array($total_viewers))
                $total_viewers = array();
            if (!in_array($uid, $total_viewers)) {
                $total_viewers[] = $uid;
                update_post_meta($post_id, '_hdlsa_total_viewers', $total_viewers);
            }

            // Session tracking
            $sessions = get_post_meta($post_id, '_hdlsa_viewer_sessions', true);
            if (!is_array($sessions))
                $sessions = array();
            $sessions[] = array('uid' => $uid, 'join' => time(), 'leave' => 0);
            update_post_meta($post_id, '_hdlsa_viewer_sessions', $sessions);

        } elseif ($type === 'leave' && $uid) {
            $sessions = get_post_meta($post_id, '_hdlsa_viewer_sessions', true);
            if (is_array($sessions)) {
                // Find last session for this UID that hasn't left
                for ($i = count($sessions) - 1; $i >= 0; $i--) {
                    if ($sessions[$i]['uid'] === $uid && $sessions[$i]['leave'] === 0) {
                        $sessions[$i]['leave'] = time();
                        break;
                    }
                }
                update_post_meta($post_id, '_hdlsa_viewer_sessions', $sessions);
            }
        } elseif ($type === 'count') {
            $peak = (int) get_post_meta($post_id, '_hdlsa_peak_viewers', true);
            if ($count > $peak) {
                update_post_meta($post_id, '_hdlsa_peak_viewers', $count);
            }
        }

        wp_send_json_success();
    }

    private function get_clean_add_to_cart_url($product)
    {
        $url = $product->add_to_cart_url();
        // If it starts with ?, it's relative query string (Simple product often)
        if (strpos($url, '?') === 0) {
            return site_url($url);
        }
        // If it points to admin-ajax.php, fix it (Variable products sometimes default if not handled)
        // But usually variable redirects to permalink.
        // Let's force simple products to home_url if needed?
        // Actually, explicit check:
        if ($product->is_type('simple')) {
            return home_url('/?add-to-cart=' . $product->get_id());
        }
        return $url;
    }
}
