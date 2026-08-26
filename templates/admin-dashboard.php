<?php
if (!defined('ABSPATH')) {
    exit;
}

// Fetch all livestreams
$hdlsa_args = array(
    'post_type' => 'hdlsa_livestream',
    'posts_per_page' => -1,
    'post_status' => 'publish',
);
$hdlsa_streams = get_posts($hdlsa_args);

$hdlsa_lifetime_viewers = array();
$hdlsa_all_time_peak = 0;
$hdlsa_total_duration = 0;
$hdlsa_stream_count = count($hdlsa_streams);

$hdlsa_recent_streams = array();

foreach ($hdlsa_streams as $hdlsa_s) {
    $hdlsa_start = get_post_meta($hdlsa_s->ID, '_hdlsa_stream_start_time', true);
    $hdlsa_end = get_post_meta($hdlsa_s->ID, '_hdlsa_stream_end_time', true);
    $hdlsa_viewers = get_post_meta($hdlsa_s->ID, '_hdlsa_total_viewers', true);
    $hdlsa_peak = (int) get_post_meta($hdlsa_s->ID, '_hdlsa_peak_viewers', true);

    if (is_array($hdlsa_viewers)) {
        $hdlsa_lifetime_viewers = array_unique(array_merge($hdlsa_lifetime_viewers, $hdlsa_viewers));
    }

    if ($hdlsa_peak > $hdlsa_all_time_peak) {
        $hdlsa_all_time_peak = $hdlsa_peak;
    }

    if ($hdlsa_start && $hdlsa_end) {
        $hdlsa_total_duration += ($hdlsa_end - $hdlsa_start);
    }

    $hdlsa_recent_streams[] = array(
        'id' => $hdlsa_s->ID,
        'title' => $hdlsa_s->post_title,
        'date' => $hdlsa_s->post_date, // Changed from get_the_date to post_date for sorting consistency
        'duration' => ($hdlsa_start && $hdlsa_end) ? ($hdlsa_end - $hdlsa_start) : 0,
        'viewers' => is_array($hdlsa_viewers) ? count($hdlsa_viewers) : 0,
        'peak' => $hdlsa_peak
    );
}

// Sort recent streams by date (newest first)
usort($hdlsa_recent_streams, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$hdlsa_display_limit = 5;
$hdlsa_recent_streams = array_slice($hdlsa_recent_streams, 0, $hdlsa_display_limit);

?>

<div class="wrap">
    <h1>
        <?php esc_html_e('HDWebmobile Live Shopping with Agora Dashboard', 'hdwebmobile-live-shopping-with-agora'); ?>
    </h1>

    <div
        style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
        <p style="font-size: 15px; margin-top: 0;">
            <?php esc_html_e('Welcome to HDWebmobile Live Shopping with Agora! Create engaging live shopping experiences directly on your WordPress site using the power of Agora.io.', 'hdwebmobile-live-shopping-with-agora'); ?>
        </p>
        <p>
            <?php esc_html_e('If you find this plugin helpful, please consider supporting its development.', 'hdwebmobile-live-shopping-with-agora'); ?>
        </p>
        <a href="https://paypal.me/htrxuan/20" target="_blank" class="button button-primary"
            style="background-color: #0070ba; border-color: #0070ba; font-weight: bold;">
            <?php esc_html_e('Donate via PayPal', 'hdwebmobile-live-shopping-with-agora'); ?>
        </a>
    </div>

    <div
        style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <!-- Card 1: Unique Viewers -->
        <div
            style="background:white; padding:25px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); border-bottom: 4px solid #FE2C55;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; color:#666; font-size:14px; text-transform:uppercase; letter-spacing:0.5px;">
                    <?php esc_html_e('Lifetime Unique Viewers', 'hdwebmobile-live-shopping-with-agora'); ?>
                </h3>
                <span
                    style="background:rgba(254, 44, 85, 0.1); color:#FE2C55; padding:4px 8px; border-radius:4px; font-size:12px;">All-time</span>
            </div>
            <p style="font-size:32px; font-weight:800; margin:15px 0 0 0; color:#222;">
                <?php echo number_format(count($hdlsa_lifetime_viewers)); ?>
            </p>
        </div>

        <!-- Card 2: Peak Viewers -->
        <div
            style="background:white; padding:25px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); border-bottom: 4px solid #25D366;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; color:#666; font-size:14px; text-transform:uppercase; letter-spacing:0.5px;">
                    <?php esc_html_e('Global Peak Viewers', 'hdwebmobile-live-shopping-with-agora'); ?>
                </h3>
                <span
                    style="background:rgba(37, 211, 102, 0.1); color:#25D366; padding:4px 8px; border-radius:4px; font-size:12px;">Record</span>
            </div>
            <p style="font-size:32px; font-weight:800; margin:15px 0 0 0; color:#222;">
                <?php echo number_format($hdlsa_all_time_peak); ?>
            </p>
        </div>

        <!-- Card 3: Total Stream Time -->
        <div
            style="background:white; padding:25px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); border-bottom: 4px solid #3498db;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; color:#666; font-size:14px; text-transform:uppercase; letter-spacing:0.5px;">
                    <?php esc_html_e('Total Airtime', 'hdwebmobile-live-shopping-with-agora'); ?>
                </h3>
                <span
                    style="background:rgba(52, 152, 219, 0.1); color:#3498db; padding:4px 8px; border-radius:4px; font-size:12px;">
                    <?php echo esc_html($hdlsa_stream_count); ?> Sessions
                </span>
            </div>
            <p style="font-size:32px; font-weight:800; margin:15px 0 0 0; color:#222;">
                <?php echo esc_html(($hdlsa_total_duration > 0) ? floor($hdlsa_total_duration / 3600) . 'h ' . floor(($hdlsa_total_duration % 3600) / 60) . 'm' : '0m'); ?>
            </p>
        </div>

        <!-- Card 4: Agora Usage (Quota) -->
        <?php $hdlsa_agora_minutes = $this->get_agora_usage(); ?>
        <div
            style="background:white; padding:25px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); border-bottom: 4px solid #9b59b6;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; color:#666; font-size:14px; text-transform:uppercase; letter-spacing:0.5px;">
                    <?php esc_html_e('Agora Service Usage', 'hdwebmobile-live-shopping-with-agora'); ?>
                </h3>
                <span
                    style="background:rgba(155, 89, 182, 0.1); color:#9b59b6; padding:4px 8px; border-radius:4px; font-size:12px;">Last
                    30 Days</span>
            </div>
            <p style="font-size:32px; font-weight:800; margin:15px 0 0 0; color:#222;">
                <?php
                if ($hdlsa_agora_minutes === false) {
                    echo '<span style="font-size:14px; color:#999;">' . esc_html__('Setup REST API in Settings', 'hdwebmobile-live-shopping-with-agora') . '</span>';
                } else {
                    echo number_format($hdlsa_agora_minutes) . ' <span style="font-size:16px; font-weight:400; color:#666;">mins</span>';
                }
                ?>
            </p>
        </div>
    </div>

    <div
        style="margin-top:40px; background:white; padding:30px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0; font-size:20px; font-weight:700;">
                <?php esc_html_e('Recent Streams', 'hdwebmobile-live-shopping-with-agora'); ?>
            </h2>
            <div style="display:flex; gap:10px;">
                <a href="<?php echo esc_url(add_query_arg(array('hdlsa_export_analytics' => '1', 'nonce' => wp_create_nonce('hdlsa_export_analytics')), admin_url())); ?>"
                    class="button button-primary"><?php esc_html_e('Download Analytics Report (.CSV)', 'hdwebmobile-live-shopping-with-agora'); ?></a>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=hdlsa_livestream')); ?>"
                    class="button button-secondary">
                    <?php esc_html_e('View All Streams', 'hdwebmobile-live-shopping-with-agora'); ?>
                </a>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped" style="border:none; box-shadow:none;">
            <thead>
                <tr>
                    <th style="padding:15px;">
                        <?php esc_html_e('Stream Name', 'hdwebmobile-live-shopping-with-agora'); ?>
                    </th>
                    <th style="padding:15px;">
                        <?php esc_html_e('Date', 'hdwebmobile-live-shopping-with-agora'); ?>
                    </th>
                    <th style="padding:15px;">
                        <?php esc_html_e('Duration', 'hdwebmobile-live-shopping-with-agora'); ?>
                    </th>
                    <th style="padding:15px;">
                        <?php esc_html_e('Unique Viewers', 'hdwebmobile-live-shopping-with-agora'); ?>
                    </th>
                    <th style="padding:15px;">
                        <?php esc_html_e('Peak', 'hdwebmobile-live-shopping-with-agora'); ?>
                    </th>
                    <th style="padding:15px;">
                        <?php esc_html_e('Actions', 'hdwebmobile-live-shopping-with-agora'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if ($hdlsa_recent_streams): ?>
                    <?php foreach ($hdlsa_recent_streams as $hdlsa_rs): ?>
                        <tr>
                            <td style="padding:15px; font-weight:600;"><a
                                    href="<?php echo esc_url(get_edit_post_link($hdlsa_rs['id'])); ?>">
                                    <?php echo esc_html($hdlsa_rs['title']); ?>
                                </a></td>
                            <td style="padding:15px; color:#666;">
                                <?php echo esc_html($hdlsa_rs['date']); ?>
                            </td>
                            <td style="padding:15px; color:#666;">
                                <?php echo esc_html(($hdlsa_rs['duration'] > 0) ? gmdate("H:i:s", $hdlsa_rs['duration']) : '--:--:--'); ?>
                            </td>
                            <td style="padding:15px; color:#666;">
                                <?php echo esc_html(number_format($hdlsa_rs['viewers'])); ?>
                            </td>
                            <td style="padding:15px; color:#666;">
                                <?php echo esc_html(number_format($hdlsa_rs['peak'])); ?>
                            </td>
                            <td style="padding:15px;">
                                <a href="<?php echo esc_url(get_permalink($hdlsa_rs['id'])); ?>" class="button button-small"
                                    target="_blank">
                                    <?php esc_html_e('Launch', 'hdwebmobile-live-shopping-with-agora'); ?>
                                </a>
                                <a href="<?php echo esc_url(add_query_arg(array('hdlsa_export_chat' => $hdlsa_rs['id'], 'nonce' => wp_create_nonce('hdlsa_export_chat_' . $hdlsa_rs['id'])), admin_url())); ?>"
                                    class="button button-small button-secondary"><?php esc_html_e('Export Chat', 'hdwebmobile-live-shopping-with-agora'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding:30px; text-align:center; color:#999;">
                            <?php esc_html_e('No live streams found yet.', 'hdwebmobile-live-shopping-with-agora'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>