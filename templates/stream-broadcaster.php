<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="hdlsa-container broadcaster">
    <div id="hdlsa-full-stream"></div>

    <div class="hdlsa-overlay">
        <!-- Top Status Bar -->
        <div class="hdlsa-top-bar">
            <span id="hdlsa-live-indicator"
                style="background:#FE2C55; color:white; padding: 4px 12px; font-weight:700; border-radius:4px; font-size:12px; display:none; letter-spacing: 0.5px;">LIVE</span>
            <div class="viewer-count"
                style="background:rgba(0,0,0,0.5); padding: 4px 8px; border-radius:4px; color:white; font-size:12px; display:flex; align-items:center; gap:4px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <span id="hdlsa-viewer-count">0</span>
            </div>
        </div>

        <!-- Hearts Container -->
        <div id="hdlsa-hearts-container" class="hdlsa-hearts-container"></div>

        <!-- Chat Messages -->
        <div class="hdlsa-chat-container">
            <div id="hdlsa-chat-messages" class="hdlsa-chat-messages">
                <!-- Messages -->
            </div>
        </div>

        <!-- Host Controls -->
        <div class="hdlsa-ui-controls" style="flex-wrap: wrap; justify-content: center;">
            <!-- Top Row: Stream Controls -->
            <div style="width:100%; display:flex; justify-content:center; gap: 15px; margin-bottom: 10px;">
                <button id="hdlsa-btn-mic" class="hdlsa-btn" title="Toggle Mic">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z" />
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                        <line x1="12" y1="19" x2="12" y2="23" />
                        <line x1="8" y1="23" x2="16" y2="23" />
                    </svg>
                </button>
                <button id="hdlsa-btn-cam" class="hdlsa-btn" title="Toggle Cam">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M23 7l-7 5 7 5V7z" />
                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2" />
                    </svg>
                </button>
                <button id="hdlsa-btn-products" class="hdlsa-btn" title="Feature Products"
                    onclick="document.getElementById('hdlsa-host-products').classList.add('open')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                </button>
                <button id="hdlsa-btn-start" class="hdlsa-btn primary"
                    style="width:auto; padding:0 24px; border-radius:24px; font-weight:700;">GO LIVE</button>
            </div>

            <!-- Bottom Row: Chat -->
            <div class="hdlsa-chat-input-area">
                <input type="text" id="hdlsa-chat-input" placeholder="Reply to viewers..." />
            </div>
            <button id="hdlsa-chat-send" class="hdlsa-btn primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M22 2L11 13" />
                    <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                </svg>
            </button>
        </div>

        <!-- Host Product Drawer -->
        <div id="hdlsa-host-products" class="hdlsa-shopping-bag">
            <div class="hdlsa-bag-header">
                <h4>Feature a Product</h4>
                <button
                    onclick="document.getElementById('hdlsa-host-products').classList.remove('open')">&times;</button>
            </div>
            <div class="hdlsa-bag-items">
                <?php
                // Use $post_id passed from render_stream_ui()
                $hdlsa_selected_products = get_post_meta($post_id, '_hdlsa_selected_products', true);
                if ($hdlsa_selected_products && is_array($hdlsa_selected_products)):
                    foreach ($hdlsa_selected_products as $hdlsa_product_id):
                        $hdlsa_p = wc_get_product($hdlsa_product_id);
                        if ($hdlsa_p):
                            ?>
                            <div class="hdlsa-bag-item-card"
                                onclick="hdlsaBroadcaster.featureProduct(<?php echo (int) $hdlsa_p->get_id(); ?>); document.getElementById('hdlsa-host-products').classList.remove('open');">
                                <?php echo wp_kses_post($hdlsa_p->get_image('thumbnail')); ?>
                                <div class="info">
                                    <h5>
                                        <?php echo esc_html($hdlsa_p->get_name()); ?>
                                    </h5>
                                    <span class="price">
                                        <?php echo wp_kses_post($hdlsa_p->get_price_html()); ?>
                                    </span>
                                    <span class="btn-buy" style="background:#333; cursor:pointer;">
                                        <?php echo esc_html__('Push to Stream', 'hdwebmobile-live-shopping-with-agora'); ?>
                                    </span>
                                </div>
                            </div>
                            <?php
                        endif;
                    endforeach;
                else:
                    ?>
                    <div style="padding:20px; text-align:center; color:#999;">
                        <?php esc_html_e('No products selected for this stream.', 'hdwebmobile-live-shopping-with-agora'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>