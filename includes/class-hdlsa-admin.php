<?php

namespace htrxuan\hdlsa;

if (!defined('ABSPATH')) {
	exit;
}

class HDLSA_Admin
{

	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
		add_action('admin_init', array($this, 'handle_chat_export'));
		add_action('admin_init', array($this, 'handle_analytics_export'));
		add_action('add_meta_boxes', array($this, 'add_stream_meta_boxes'));
		add_action('save_post', array($this, 'save_stream_meta_data'));
		add_filter('plugin_action_links_' . plugin_basename(HDLSA_PLUGIN_DIR . 'hdwebmobile-live-shopping-with-agora.php'), array($this, 'add_settings_link'));
		add_action('admin_menu', array($this, 'reorder_submenu'), 999);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_media_scripts'));
		add_action('admin_head', array($this, 'reorder_submenu'));

		// Shortcode columns
		add_filter('manage_hdlsa_livestream_posts_columns', array($this, 'add_shortcode_column'));
		add_action('manage_hdlsa_livestream_posts_custom_column', array($this, 'render_shortcode_column'), 10, 2);
	}

	public function add_settings_link($links)
	{
		$settings_link = '<a href="admin.php?page=hdwebmobile-live-shopping-with-agora">' . __('Settings', 'hdwebmobile-live-shopping-with-agora') . '</a>';
		$donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" style="color:#d54e21;font-weight:bold;">' . __('Donate', 'hdwebmobile-live-shopping-with-agora') . '</a>';
		array_unshift($links, $donate_link);
		array_unshift($links, $settings_link);
		return $links;
	}

	public function enqueue_admin_media_scripts($hook)
	{
		if ('post.php' != $hook && 'post-new.php' != $hook)
			return;
		global $post_type;
		if ('hdlsa_livestream' != $post_type)
			return;

		wp_enqueue_media();
		wp_enqueue_script(
			'hdlsa-admin-js',
			HDLSA_PLUGIN_URL . 'assets/js/hdlsa-admin.js',
			array('jquery'),
			HDLSA_VERSION,
			true
		);
		wp_localize_script('hdlsa-admin-js', 'hdlsa_admin_params', array(
			'select_avatar_title' => __('Select Avatar', 'hdwebmobile-live-shopping-with-agora'),
			'use_image_text' => __('Use Image', 'hdwebmobile-live-shopping-with-agora')
		));
	}

	public function add_plugin_page()
	{
		add_submenu_page(
			'edit.php?post_type=hdlsa_livestream',
			__('Agora Live Shopping Settings', 'hdwebmobile-live-shopping-with-agora'),
			__('Settings', 'hdwebmobile-live-shopping-with-agora'),
			'manage_options',
			'hdwebmobile-live-shopping-with-agora',
			array($this, 'create_admin_page')
		);

		add_submenu_page(
			'edit.php?post_type=hdlsa_livestream',
			__('Agora Live Shopping Dashboard', 'hdwebmobile-live-shopping-with-agora'),
			__('Dashboard', 'hdwebmobile-live-shopping-with-agora'),
			'manage_options',
			'hdlsa-dashboard',
			array($this, 'create_dashboard_page')
		);
	}

	public function create_dashboard_page()
	{
		require_once HDLSA_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}

	public function reorder_submenu()
	{
		global $submenu;
		$parent = 'edit.php?post_type=hdlsa_livestream';

		if (isset($submenu[$parent])) {
			$new_order = array();
			$dashboard_key = false;

			// Find our dashboard key
			foreach ($submenu[$parent] as $key => $item) {
				if ($item[2] === 'hdlsa-dashboard') {
					$dashboard_key = $key;
					break;
				}
			}

			if ($dashboard_key !== false) {
				// Move dashboard to the first position
				$dashboard_item = $submenu[$parent][$dashboard_key];
				unset($submenu[$parent][$dashboard_key]);
				array_unshift($submenu[$parent], $dashboard_item);
			}
		}
	}

	public function create_admin_page()
	{
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Agora Live Shopping Settings', 'hdwebmobile-live-shopping-with-agora'); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('hdlsa_option_group');
				do_settings_sections('hdwebmobile-live-shopping-with-agora-admin');
				submit_button();
				?>
			</form>
			<?php $this->render_sibling_plugins_panel('hdwebmobile-live-shopping-with-agora'); ?>
		</div>
		<?php
	}

	/**
	 * A self-contained "more plugins by this author" panel, shown at the bottom of this
	 * plugin's own settings page only (never a global admin notice or dashboard widget) --
	 * stays within WordPress.org's guidelines for author self-promotion.
	 */
	private function render_sibling_plugins_panel($exclude_slug)
	{
		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = array(
			'hdwebmobile-mix-match-bundles' => array(
				'name' => 'Mix & Match Bundles',
				'file' => 'hdwebmobile-mix-match-bundles/hdwebmobile-mix-match-bundles.php',
				'description' => __('Build-a-box bundles for WooCommerce.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-back-in-stock-waitlist' => array(
				'name' => 'Back In Stock & Waitlist',
				'file' => 'hdwebmobile-back-in-stock-waitlist/hdwebmobile-back-in-stock-waitlist.php',
				'description' => __('Notify customers the moment an out-of-stock product is back.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-spin-and-win' => array(
				'name' => 'Spin & Win',
				'file' => 'hdwebmobile-spin-and-win/hdwebmobile-spin-and-win.php',
				'description' => __('A gamified discount-wheel popup with server-enforced one spin per email.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-abandoned-cart-recovery' => array(
				'name' => 'Abandoned Cart Recovery',
				'file' => 'hdwebmobile-abandoned-cart-recovery/hdwebmobile-abandoned-cart-recovery.php',
				'description' => __('Recover lost sales with automatic cart-recovery emails.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-live-shopping-with-agora' => array(
				'name' => 'Live Shopping with Agora',
				'file' => 'hdwebmobile-live-shopping-with-agora/hdwebmobile-live-shopping-with-agora.php',
				'description' => __('Sell live on livestream video with real-time WooCommerce checkout.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-media-folder-roles' => array(
				'name' => 'Media Folder Roles',
				'file' => 'hdwebmobile-media-folder-roles/hd-media-folder-roles.php',
				'description' => __('Organize the Media Library into folders with per-role permissions.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-socials-floating' => array(
				'name' => 'Socials Floating',
				'file' => 'hdwebmobile-socials-floating/hdwebmobile-socials-floating.php',
				'description' => __('A floating social-media contact bar for your site.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-wishlist' => array(
				'name' => 'Wishlist',
				'file' => 'hdwebmobile-wishlist/hdwebmobile-wishlist.php',
				'description' => __('A wishlist that works with block-based Shop pages and guest browsing.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-frequently-bought-together' => array(
				'name' => 'Frequently Bought Together',
				'file' => 'hdwebmobile-frequently-bought-together/hdwebmobile-frequently-bought-together.php',
				'description' => __('An Amazon-style widget that adds several related products in one click.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-delivery-date-time-slot' => array(
				'name' => 'Delivery Date & Time Slot',
				'file' => 'hdwebmobile-delivery-date-time-slot/hdwebmobile-delivery-date-time-slot.php',
				'description' => __('Let customers pick a delivery date and time slot at checkout.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-product-options' => array(
				'name' => 'Product Options & Add-ons',
				'file' => 'hdwebmobile-product-options/hdwebmobile-product-options.php',
				'description' => __('Add paid text, dropdown, and checkbox options to products -- flat pricing only, no formulas.', 'hdwebmobile-live-shopping-with-agora'),
			),
			'hdwebmobile-invoices-packing-slips' => array(
				'name' => 'Invoices & Packing Slips',
				'file' => 'hdwebmobile-invoices-packing-slips/hdwebmobile-invoices-packing-slips.php',
				'description' => __('On-demand invoice and packing-slip documents, printable to PDF from any browser.', 'hdwebmobile-live-shopping-with-agora'),
			),
            'hdwebmobile-gift-cards' => array(
                'name' => 'Gift Cards',
                'file' => 'hdwebmobile-gift-cards/hdwebmobile-gift-cards.php',
                'description' => __('Sell digital gift cards redeemed through WooCommerce\'s own native coupon field.', 'hdwebmobile-live-shopping-with-agora'),
            ),
            'hdwebmobile-shipment-tracking' => array(
                'name' => 'Shipment Tracking',
                'file' => 'hdwebmobile-shipment-tracking/hdwebmobile-shipment-tracking.php',
                'description' => __('Add a carrier and tracking number to any order and show it to the customer.', 'hdwebmobile-live-shopping-with-agora'),
            ),
            'hdwebmobile-order-export' => array(
                'name' => 'Order Export',
                'file' => 'hdwebmobile-order-export/hdwebmobile-order-export.php',
                'description' => __('Export orders to CSV, filtered by date range and status.', 'hdwebmobile-live-shopping-with-agora'),
            ),
            'hdwebmobile-photo-video-reviews' => array(
                'name' => 'Photo & Video Reviews',
                'file' => 'hdwebmobile-photo-video-reviews/hdwebmobile-photo-video-reviews.php',
                'description' => __('Let customers attach photos and short videos to their product reviews.', 'hdwebmobile-live-shopping-with-agora'),
            ),
		);

		unset($plugins[$exclude_slug]);
		?>
		<div class="hdwebmobile-sibling-plugins" style="margin-top:2em;padding:1.5em;background:#fff;border:1px solid #dcdcde;border-radius:4px;max-width:800px;">
			<h2 style="margin-top:0;"><?php esc_html_e('More WooCommerce Plugins by HDWebmobile', 'hdwebmobile-live-shopping-with-agora'); ?></h2>
			<table class="widefat striped" style="margin-top:1em;">
				<tbody>
					<?php foreach ($plugins as $plugin) : ?>
						<?php $active = is_plugin_active($plugin['file']); ?>
						<?php $installed = file_exists(WP_PLUGIN_DIR . '/' . $plugin['file']); ?>
						<tr>
							<td>
								<strong><?php echo esc_html($plugin['name']); ?></strong><br />
								<span style="opacity:.75;"><?php echo esc_html($plugin['description']); ?></span>
							</td>
							<td style="text-align:right;white-space:nowrap;vertical-align:middle;">
								<?php if ($active) : ?>
									<span style="color:#1a7f37;">&#10003; <?php esc_html_e('Active', 'hdwebmobile-live-shopping-with-agora'); ?></span>
								<?php elseif ($installed) : ?>
									<a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-small"><?php esc_html_e('Activate', 'hdwebmobile-live-shopping-with-agora'); ?></a>
								<?php else : ?>
									<a href="https://hdwebmobile.com/plugins/" target="_blank" rel="noopener noreferrer" class="button button-small"><?php esc_html_e('Learn More', 'hdwebmobile-live-shopping-with-agora'); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function page_init()
	{
		register_setting(
			'hdlsa_option_group',
			'hdlsa_agora_app_id',
			array($this, 'sanitize')
		);

		register_setting(
			'hdlsa_option_group',
			'hdlsa_agora_app_certificate',
			array($this, 'sanitize_secret')
		);

		register_setting(
			'hdlsa_option_group',
			'hdlsa_agora_customer_id',
			array($this, 'sanitize')
		);

		register_setting(
			'hdlsa_option_group',
			'hdlsa_agora_customer_secret',
			array($this, 'sanitize_secret')
		);

		register_setting(
			'hdlsa_option_group',
			'hdlsa_agora_project_id',
			array($this, 'sanitize')
		);

		add_settings_section(
			'setting_section_id',
			__('Agora.io Credentials', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'print_section_info'),
			'hdwebmobile-live-shopping-with-agora-admin'
		);

		add_settings_field(
			'hdlsa_agora_app_id',
			__('App ID', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'app_id_callback'),
			'hdwebmobile-live-shopping-with-agora-admin',
			'setting_section_id'
		);

		add_settings_field(
			'hdlsa_agora_app_certificate',
			__('App Certificate', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'app_certificate_callback'),
			'hdwebmobile-live-shopping-with-agora-admin',
			'setting_section_id'
		);

		add_settings_field(
			'hdlsa_agora_customer_id',
			__('Customer ID (REST API)', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'customer_id_callback'),
			'hdwebmobile-live-shopping-with-agora-admin',
			'setting_section_id'
		);

		add_settings_field(
			'hdlsa_agora_customer_secret',
			__('Customer Secret (REST API)', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'customer_secret_callback'),
			'hdwebmobile-live-shopping-with-agora-admin',
			'setting_section_id'
		);

		add_settings_field(
			'hdlsa_agora_project_id',
			__('Project ID (REST API)', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'project_id_callback'),
			'hdwebmobile-live-shopping-with-agora-admin',
			'setting_section_id'
		);

		// Chat Settings Section
		add_settings_section(
			'chat_settings_section',
			__('Chat Settings', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'chat_section_info'),
			'hdwebmobile-live-shopping-with-agora-admin'
		);

		register_setting(
			'hdlsa_option_group',
			'hdlsa_bad_words_list',
			array($this, 'sanitize_bad_words')
		);

		add_settings_field(
			'hdlsa_bad_words_list',
			__('Bad Words Filter', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'bad_words_callback'),
			'hdwebmobile-live-shopping-with-agora-admin',
			'chat_settings_section'
		);
		// Closing brace for page_init
	}

	public function handle_chat_export()
	{
		if (isset($_GET['hdlsa_export_chat']) && current_user_can('manage_options')) {
			$post_id = intval($_GET['hdlsa_export_chat']);
			$nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
			check_admin_referer('hdlsa_export_chat_' . $post_id, 'nonce');

			$history = get_post_meta($post_id, '_hdlsa_chat_history', true);

			if (!is_array($history)) {
				$history = array();
			}

			$filename = 'chat_export_' . $post_id . '_' . gmdate('Y-m-d') . '.csv';

			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $filename);

			$output = '';
			$output .= '"Time","User","Message"' . "\n";

			foreach ($history as $msg) {
				$output .= '"' . str_replace('"', '""', gmdate('Y-m-d H:i:s', $msg['time'])) . '",';
				$output .= '"' . str_replace('"', '""', $msg['user']) . '",';
				$output .= '"' . str_replace('"', '""', $msg['msg']) . '"' . "\n";
			}

			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}
	}

	public function handle_analytics_export()
	{
		if (isset($_GET['hdlsa_export_analytics']) && current_user_can('manage_options')) {
			$nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
			check_admin_referer('hdlsa_export_analytics', 'nonce');

			$args = array(
				'post_type' => 'hdlsa_livestream',
				'posts_per_page' => -1,
				'post_status' => 'publish',
			);
			$streams = get_posts($args);

			$filename = 'stream_analytics_report_' . gmdate('Y-m-d') . '.csv';

			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $filename);

			$output = '';
			$output .= '"ID","Stream Title","Date","Duration","Unique Viewers","Peak Viewers"' . "\n";

			foreach ($streams as $s) {
				$start = get_post_meta($s->ID, '_hdlsa_stream_start_time', true);
				$end = get_post_meta($s->ID, '_hdlsa_stream_end_time', true);
				$viewers = get_post_meta($s->ID, '_hdlsa_total_viewers', true);
				$peak = (int) get_post_meta($s->ID, '_hdlsa_peak_viewers', true);

				$duration_val = ($start && $end) ? ($end - $start) : 0;
				$duration_formatted = ($duration_val > 0) ? gmdate("H:i:s", $duration_val) : '00:00:00';

				$output .= '"' . $s->ID . '",';
				$output .= '"' . str_replace('"', '""', $s->post_title) . '",';
				$output .= '"' . get_the_date('Y-m-d', $s) . '",';
				$output .= '"' . $duration_formatted . '",';
				$output .= '"' . (is_array($viewers) ? count($viewers) : 0) . '",';
				$output .= '"' . $peak . '"' . "\n";
			}

			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}
	}

	public function sanitize($input)
	{
		return sanitize_text_field($input);
	}

	public function sanitize_secret($input)
	{
		// Secrets usually contain characters that sanitize_text_field() might strip.
		// We want to trim whitespace but allow characters like +, =, /, etc.
		return trim($input);
	}

	public function add_shortcode_column($columns)
	{
		$new_columns = array();
		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;
			if ($key === 'title') {
				$new_columns['hdlsa_shortcode'] = __('Shortcode', 'hdwebmobile-live-shopping-with-agora');
			}
		}
		return $new_columns;
	}

	public function render_shortcode_column($column, $post_id)
	{
		if ($column === 'hdlsa_shortcode') {
			echo '<code>[hdlsa_stream id="' . (int) $post_id . '"]</code>';
		}
	}

	public function print_section_info()
	{
		echo esc_html__('Enter your Agora App ID and Certificate below.', 'hdwebmobile-live-shopping-with-agora');

		$app_id = get_option('hdlsa_agora_app_id');
		$app_cert = get_option('hdlsa_agora_app_certificate');
		$customer_id = get_option('hdlsa_agora_customer_id');
		$customer_secret = get_option('hdlsa_agora_customer_secret');

		echo '<div class="notice notice-info" style="margin-top: 15px; border-left-color: #2271b1;">';
		echo '<p><strong>Diagnostic Checklist:</strong></p>';
		echo '<ul>';
		echo '<li>' . ($app_id ? '✅ App ID entered.' : '❌ App ID missing.') . '</li>';
		echo '<li>' . ($app_cert ? '✅ App Certificate entered (Security enabled).' : 'ℹ️ App Certificate missing (Testing mode).') . '</li>';
		echo '<li>' . ($customer_id && $customer_secret ? '✅ REST API Credentials entered (Usage tracking enabled).' : 'ℹ️ REST API Credentials missing (Usage tracking disabled).') . '</li>';
		echo '<li>' . ('🚩 <strong>Action Required:</strong> Ensure "Signaling" (RTM) is <strong>Enabled</strong> in the <a href="https://console.agora.io/" target="_blank">Agora Console</a> for this project.') . '</li>';
		echo '</ul>';
		echo '</div>';
	}

	public function app_id_callback()
	{
		printf(
			'<input type="text" id="hdlsa_agora_app_id" name="hdlsa_agora_app_id" value="%s" class="regular-text" />',
			get_option('hdlsa_agora_app_id') ? esc_attr(get_option('hdlsa_agora_app_id')) : ''
		);
	}

	public function app_certificate_callback()
	{
		printf(
			'<input type="password" id="hdlsa_agora_app_certificate" name="hdlsa_agora_app_certificate" value="%s" class="regular-text" />',
			get_option('hdlsa_agora_app_certificate') ? esc_attr(get_option('hdlsa_agora_app_certificate')) : ''
		);
	}

	public function customer_id_callback()
	{
		printf(
			'<input type="text" id="hdlsa_agora_customer_id" name="hdlsa_agora_customer_id" value="%s" class="regular-text" />',
			get_option('hdlsa_agora_customer_id') ? esc_attr(get_option('hdlsa_agora_customer_id')) : ''
		);
		echo '<p class="description">' . esc_html__('Found in Agora Console > RESTful API. Required for usage display.', 'hdwebmobile-live-shopping-with-agora') . '</p>';
	}

	public function customer_secret_callback()
	{
		printf(
			'<input type="password" id="hdlsa_agora_customer_secret" name="hdlsa_agora_customer_secret" value="%s" class="regular-text" />',
			get_option('hdlsa_agora_customer_secret') ? esc_attr(get_option('hdlsa_agora_customer_secret')) : ''
		);
		echo '<p class="description">' . esc_html__('Found in Agora Console > RESTful API. Required for usage display.', 'hdwebmobile-live-shopping-with-agora') . '</p>';
	}

	public function project_id_callback()
	{
		printf(
			'<input type="text" id="hdlsa_agora_project_id" name="hdlsa_agora_project_id" value="%s" class="regular-text" />',
			get_option('hdlsa_agora_project_id') ? esc_attr(get_option('hdlsa_agora_project_id')) : ''
		);
		echo '<p class="description">' . esc_html__('Different from App ID! Find in Agora Console > Project Management. Required for usage display.', 'hdwebmobile-live-shopping-with-agora') . '</p>';
	}

	public function chat_section_info()
	{
		echo esc_html__('Configure chat moderation settings.', 'hdwebmobile-live-shopping-with-agora');
	}

	public function bad_words_callback()
	{
		printf(
			'<textarea id="hdlsa_bad_words_list" name="hdlsa_bad_words_list" rows="5" cols="50" class="regular-text">%s</textarea>',
			esc_textarea(get_option('hdlsa_bad_words_list'))
		);
		echo '<p class="description">' . esc_html__('Enter bad words separated by commas (e.g. bad, word, example). These will be replaced with *** in the chat.', 'hdwebmobile-live-shopping-with-agora') . '</p>';
	}

	public function sanitize_bad_words($input)
	{
		return sanitize_textarea_field($input);
	}

	/**
	 * Fetch monthly usage from Agora REST API
	 */
	public function get_agora_usage()
	{
		$customer_id = get_option('hdlsa_agora_customer_id');
		$customer_secret = get_option('hdlsa_agora_customer_secret');
		$project_id = get_option('hdlsa_agora_project_id');

		if (!$customer_id || !$customer_secret || !$project_id) {
			return false;
		}

		$cache_key = 'hdlsa_agora_usage_' . md5($project_id);

		// Temporarily disabled for testing
		// $cached_usage = get_transient($cache_key);
		// if ($cached_usage !== false) {
		// 	return $cached_usage;
		// }

		// Calculate date range for last 30 days (to match Agora Console default)
		$from_date = gmdate('Y-m-d', strtotime('-30 days'));
		$to_date = gmdate('Y-m-d');

		// Add business=default parameter (required by Agora API)
		$url = "https://api.agora.io/dev/v3/usage?project_id=" . urlencode($project_id) . "&from_date=" . urlencode($from_date) . "&to_date=" . urlencode($to_date) . "&business=default";

		$credentials = base64_encode($customer_id . ':' . $customer_secret);

		$response = wp_remote_get($url, array(
			'headers' => array(
				'Authorization' => 'Basic ' . $credentials,
			),
			'timeout' => 15,
		));

		if (is_wp_error($response)) {
			return false;
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		// Check for API error
		if (isset($data['message'])) {
			return 0;
		}

		// The response has a 'usages' array containing daily records
		if (!isset($data['usages']) || !is_array($data['usages']) || empty($data['usages'])) {
			return 0;
		}

		$total_seconds = 0;

		// Iterate through each day's data in the usages array
		foreach ($data['usages'] as $day_record) {
			if (isset($day_record['usage']) && is_array($day_record['usage'])) {
				// Sum all duration fields for this day
				foreach ($day_record['usage'] as $key => $value) {
					// All duration fields start with 'duration'
					if (strpos($key, 'duration') === 0 && is_numeric($value)) {
						$total_seconds += (int) $value;
					}
				}
			}
		}

		// Convert seconds to minutes
		$total_minutes = round($total_seconds / 60);

		// Cache for 1 hour
		set_transient($cache_key, $total_minutes, HOUR_IN_SECONDS);

		return $total_minutes;
	}

	// Meta Boxes for Live Stream
	public function add_stream_meta_boxes()
	{
		add_meta_box(
			'hdlsa_stream_details',
			__('Stream Details', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'render_stream_details_meta_box'),
			'hdlsa_livestream',
			'normal',
			'high'
		);

		add_meta_box(
			'hdlsa_stream_shortcode',
			__('Embed Shortcode', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'render_shortcode_meta_box'),
			'hdlsa_livestream',
			'side',
			'high'
		);

		add_meta_box(
			'hdlsa_stream_analytics',
			__('Stream Analytics', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'render_stream_analytics_meta_box'),
			'hdlsa_livestream',
			'normal',
			'low'
		);

		add_meta_box(
			'hdlsa_product_meta',
			__('Featured Products', 'hdwebmobile-live-shopping-with-agora'),
			array($this, 'render_product_meta_box'),
			'hdlsa_livestream',
			'normal',
			'high'
		);
	}

	public function render_shortcode_meta_box($post)
	{
		?>
		<p class="description">
			<?php esc_html_e('Copy and paste this shortcode into any page or post to embed this live stream.', 'hdwebmobile-live-shopping-with-agora'); ?>
		</p>
		<input type="text" class="widefat" readonly value='[hdlsa_stream id="<?php echo (int) $post->ID; ?>"]'
			onclick="this.select();" />
		<p style="font-size: 11px; color: #777; margin-top: 10px;">
			<?php esc_html_e('Note: Your theme will handle the header and footer, fitting the stream perfectly into your layout.', 'hdwebmobile-live-shopping-with-agora'); ?>
		</p>
		<?php
	}

	public function render_stream_details_meta_box($post)
	{
		$host_name = get_post_meta($post->ID, '_hdlsa_host_name', true);
		$host_avatar_id = get_post_meta($post->ID, '_hdlsa_host_avatar_id', true);
		$avatar_url = wp_get_attachment_image_url($host_avatar_id, 'thumbnail');

		wp_nonce_field('hdlsa_save_stream_meta', 'hdlsa_stream_meta_nonce');

		echo '<table class="form-table">';

		// Host Name field
		echo '<tr>';
		echo '<th><label for="hdlsa_host_name">' . esc_html__('Host Name', 'hdwebmobile-live-shopping-with-agora') . '</label></th>';
		echo '<td>';
		echo '<input type="text" id="hdlsa_host_name" name="hdlsa_host_name" value="' . esc_attr($host_name) . '" class="regular-text" placeholder="' . esc_attr(get_the_author_meta('display_name', $post->post_author)) . '">';
		echo '<p class="description">' . esc_html__('Enter the name of the host (defaults to author name if empty).', 'hdwebmobile-live-shopping-with-agora') . '</p>';
		echo '</td>';
		echo '</tr>';

		// Host Avatar field
		echo '<tr>';
		echo '<th><label>' . esc_html__('Host Avatar', 'hdwebmobile-live-shopping-with-agora') . '</label></th>';
		echo '<td>';
		echo '<div id="hdlsa-avatar-preview" style="margin-bottom:10px;">';
		if ($avatar_url) {
			echo '<img src="' . esc_url($avatar_url) . '" style="max-width:100px; border-radius:50%; border:2px solid #FE2C55; padding:2px;">';
		}
		echo '</div>';
		echo '<input type="hidden" id="hdlsa_host_avatar_id" name="hdlsa_host_avatar_id" value="' . esc_attr($host_avatar_id) . '">';
		echo '<button type="button" class="button hdlsa-upload-avatar">' . esc_html__('Select Avatar', 'hdwebmobile-live-shopping-with-agora') . '</button> ';
		echo '<button type="button" class="button hdlsa-remove-avatar" ' . (!$avatar_url ? 'style="display:none;"' : '') . '>' . esc_html__('Remove', 'hdwebmobile-live-shopping-with-agora') . '</button>';
		echo '<p class="description">' . esc_html__('Upload or select a custom avatar for the host.', 'hdwebmobile-live-shopping-with-agora') . '</p>';

		// Inline script moved to assets/js/hdlsa-admin.js

		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	public function render_stream_analytics_meta_box($post)
	{
		$start_time = get_post_meta($post->ID, '_hdlsa_stream_start_time', true);
		$end_time = get_post_meta($post->ID, '_hdlsa_stream_end_time', true);
		$total_viewers = get_post_meta($post->ID, '_hdlsa_total_viewers', true);
		$peak_viewers = get_post_meta($post->ID, '_hdlsa_peak_viewers', true);
		$sessions = get_post_meta($post->ID, '_hdlsa_viewer_sessions', true);

		// Calculations
		$duration = ($start_time && $end_time) ? ($end_time - $start_time) : 0;
		$unique_count = is_array($total_viewers) ? count($total_viewers) : 0;
		$peak = (int) $peak_viewers;

		$total_stay = 0;
		$session_count = 0;
		if (is_array($sessions)) {
			foreach ($sessions as $s) {
				if ($s['leave'] > $s['join']) {
					$total_stay += ($s['leave'] - $s['join']);
					$session_count++;
				}
			}
		}
		$avg_stay = ($session_count > 0) ? round($total_stay / $session_count) : 0;

		// Display
		echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 10px;">';

		echo '<div style="background:#f9f9f9; padding:15px; border-radius:8px; border-left:4px solid #FE2C55;">';
		echo '<h4 style="margin-top:0;">' . esc_html__('Stream Duration', 'hdwebmobile-live-shopping-with-agora') . '</h4>';
		echo '<p style="font-size:24px; font-weight:700; margin:0;">' . esc_html($duration > 0 ? gmdate("H:i:s", $duration) : '--:--:--') . '</p>';
		echo '</div>';

		echo '<div style="background:#f9f9f9; padding:15px; border-radius:8px; border-left:4px solid #25D366;">';
		echo '<h4 style="margin-top:0;">' . esc_html__('Peak Viewers', 'hdwebmobile-live-shopping-with-agora') . '</h4>';
		echo '<p style="font-size:24px; font-weight:700; margin:0;">' . esc_html($peak) . '</p>';
		echo '</div>';

		echo '<div style="background:#f9f9f9; padding:15px; border-radius:8px; border-left:4px solid #3498db;">';
		echo '<h4 style="margin-top:0;">' . esc_html__('Total Unique Viewers', 'hdwebmobile-live-shopping-with-agora') . '</h4>';
		echo '<p style="font-size:24px; font-weight:700; margin:0;">' . esc_html($unique_count) . '</p>';
		echo '</div>';

		echo '<div style="background:#f9f9f9; padding:15px; border-radius:8px; border-left:4px solid #f1c40f;">';
		echo '<h4 style="margin-top:0;">' . esc_html__('Avg. Stay Time', 'hdwebmobile-live-shopping-with-agora') . '</h4>';
		echo '<p style="font-size:24px; font-weight:700; margin:0;">' . esc_html($avg_stay > 0 ? gmdate("i:s", $avg_stay) . ' min' : '--:--') . '</p>';
		echo '</div>';

		echo '</div>';

		echo '<div style="margin-top:20px; padding:0 10px;">';
		$export_url = add_query_arg(array('hdlsa_export_chat' => $post->ID, 'nonce' => wp_create_nonce('hdlsa_export_chat_' . $post->ID)), admin_url());
		echo '<a href="' . esc_url($export_url) . '" class="button button-secondary">' . esc_html__('Export Chat History (.CSV)', 'hdwebmobile-live-shopping-with-agora') . '</a>';
		echo '</div>';

		if (!$start_time) {
			echo '<p style="color:#d63638; padding:0 10px;">' . esc_html__('Note: Analytics will be available after the stream starts.', 'hdwebmobile-live-shopping-with-agora') . '</p>';
		}
	}

	public function render_product_meta_box($post)
	{
		// Retrieve existing values
		$selected_products = get_post_meta($post->ID, '_hdlsa_selected_products', true);
		if (!is_array($selected_products)) {
			$selected_products = array();
		}

		// Get all WooCommerce products
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		);
		$products = get_posts($args);

		echo '<div style="max-height: 200px; overflow-y: auto;">';
		foreach ($products as $product) {
			$checked = in_array($product->ID, $selected_products) ? 'checked' : '';
			echo '<label style="display:block; margin-bottom: 5px;">';
			echo '<input type="checkbox" name="hdlsa_selected_products[]" value="' . esc_attr($product->ID) . '" ' . esc_attr($checked) . ' /> ';
			echo esc_html($product->post_title);
			echo '</label>';
		}
		echo '</div>';

		// Nonce field for security
		wp_nonce_field('hdlsa_save_product_meta_box_data', 'hdlsa_product_meta_box_nonce');
	}

	public function save_stream_meta_data($post_id)
	{
		// Common Checks
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Save Product Selection
		$product_nonce = isset($_POST['hdlsa_product_meta_box_nonce']) ? sanitize_text_field(wp_unslash($_POST['hdlsa_product_meta_box_nonce'])) : '';
		if ($product_nonce && wp_verify_nonce($product_nonce, 'hdlsa_save_product_meta_box_data')) {
			if (isset($_POST['hdlsa_selected_products'])) {
				$data = array_map('sanitize_text_field', wp_unslash($_POST['hdlsa_selected_products']));
				update_post_meta($post_id, '_hdlsa_selected_products', $data);
			} else {
				delete_post_meta($post_id, '_hdlsa_selected_products');
			}
		}

		// Save Stream Details
		$stream_nonce = isset($_POST['hdlsa_stream_meta_nonce']) ? sanitize_text_field(wp_unslash($_POST['hdlsa_stream_meta_nonce'])) : '';
		if ($stream_nonce && wp_verify_nonce($stream_nonce, 'hdlsa_save_stream_meta')) {
			if (isset($_POST['hdlsa_host_name'])) {
				update_post_meta($post_id, '_hdlsa_host_name', sanitize_text_field(wp_unslash($_POST['hdlsa_host_name'])));
			} else {
				delete_post_meta($post_id, '_hdlsa_host_name');
			}

			if (isset($_POST['hdlsa_host_avatar_id'])) {
				update_post_meta($post_id, '_hdlsa_host_avatar_id', sanitize_text_field(wp_unslash($_POST['hdlsa_host_avatar_id'])));
			} else {
				delete_post_meta($post_id, '_hdlsa_host_avatar_id');
			}
		}
	}
}
