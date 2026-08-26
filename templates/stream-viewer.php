<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="hdlsa-container viewer">
    <div id="hdlsa-full-stream">
        <!-- Video will play here -->
        <div id="hdlsa-waiting-message"
            style="display:flex; justify-content:center; align-items:center; height:100%; color:white; background:#222;">
            <div style="text-align:center;">
                <h3>Host is offline</h3>
                <p>Waiting for broadcast...</p>
            </div>
        </div>
    </div>

    <div class="hdlsa-overlay">
        <!-- Top Status Bar -->
        <div class="hdlsa-top-bar">
            <div class="host-info" style="display:flex; align-items:center; gap:8px;">
                <div style="position:relative;">
                    <?php
                    // Use $post_id passed from render_stream_ui
                    $hdlsa_stream_post = get_post($post_id);

                    $hdlsa_host_avatar_id = get_post_meta($post_id, '_hdlsa_host_avatar_id', true);
                    $hdlsa_avatar_url = wp_get_attachment_image_url($hdlsa_host_avatar_id, 'thumbnail');
                    if (!$hdlsa_avatar_url) {
                        $hdlsa_avatar_url = get_avatar_url($hdlsa_stream_post->post_author, array('size' => 100));
                    }
                    ?>
                    <img src="<?php echo esc_url($hdlsa_avatar_url); ?>"
                        style="width:36px; height:36px; border-radius:50%; border:1px solid rgba(255,255,255,0.2); object-fit:cover;">
                </div>
                <div style="display:flex; flex-direction:column;">
                    <?php
                    $hdlsa_host_name = get_post_meta($post_id, '_hdlsa_host_name', true);
                    if (empty($hdlsa_host_name)) {
                        $hdlsa_host_name = get_the_author_meta('display_name', $hdlsa_stream_post->post_author);
                    }
                    ?>
                    <span
                        style="font-weight:700; color:white; font-size:13px; line-height:1.2; text-shadow:0 1px 2px rgba(0,0,0,0.5);">
                        <?php echo esc_html($hdlsa_host_name); ?>
                    </span>
                    <span
                        style="font-size:10px; color:rgba(255,255,255,0.8); text-shadow:0 1px 2px rgba(0,0,0,0.5);">Live
                        Shopping</span>
                </div>
            </div>

            <div style="display:flex; gap:8px; align-items:center;">
                <div class="viewer-count"
                    style="background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); padding: 4px 8px; border-radius:12px; color:white; font-size:11px; display:flex; align-items:center; gap:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <span id="hdlsa-viewer-count">0</span>
                </div>
                <button onclick="this.closest('.hdlsa-container').requestFullscreen()"
                    style="background:rgba(0,0,0,0.4); border:none; width:28px; height:28px; border-radius:50%; color:white; display:flex; align-items:center; justify-content:center; cursor:pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path
                            d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Hearts Container -->
        <div id="hdlsa-hearts-container" class="hdlsa-hearts-container"></div>

        <!-- Featured Product Popup -->
        <div id="hdlsa-featured-product" class="hdlsa-featured-product">
            <button class="close-btn"
                onclick="document.getElementById('hdlsa-featured-product').classList.remove('active')">&times;</button>
            <img src="" id="hdlsa-fp-img" />
            <div class="hdlsa-fp-details">
                <h4 id="hdlsa-fp-name"></h4>
                <span id="hdlsa-fp-price" class="price"></span>
                <a href="#" id="hdlsa-fp-btn" class="btn-buy-mini">Buy Now</a>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="hdlsa-chat-container">
            <div id="hdlsa-chat-messages" class="hdlsa-chat-messages">
                <!-- Messages -->
            </div>
        </div>

        <!-- Shopping Bag List -->
        <div id="hdlsa-shopping-bag" class="hdlsa-shopping-bag">
            <div class="hdlsa-bag-header">
                <h4>Shop</h4>
                <button
                    onclick="document.getElementById('hdlsa-shopping-bag').classList.remove('open')">&times;</button>
            </div>
            <div id="hdlsa-bag-items" class="hdlsa-bag-items">
                <!-- Items injected via JS -->
            </div>
        </div>

        <!-- Bottom Controls -->
        <div class="hdlsa-ui-controls">
            <button id="hdlsa-btn-bag" class="hdlsa-btn" style="margin-right:8px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
            </button>
            <div class="hdlsa-chat-input-area">
                <input type="text" id="hdlsa-chat-input" placeholder="Say something..." />
            </div>
            <button id="hdlsa-btn-like" class="hdlsa-btn" style="background: rgba(254, 44, 85, 0.2);">
                <svg width="24" height="24" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M15 8C8.92487 8 4 12.9249 4 19C4 30 17 40 24 42.3262C31 40 44 30 44 19C44 12.9249 39.0751 8 33 8C29.2797 8 25.9907 9.8469 24 12.6738C22.0093 9.8469 18.7203 8 15 8Z"
                        fill="#FE2C55" stroke="#FE2C55" stroke-width="4" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </button>
            <button id="hdlsa-chat-send" class="hdlsa-btn primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="white" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </button>
        </div>
    </div>
</div>