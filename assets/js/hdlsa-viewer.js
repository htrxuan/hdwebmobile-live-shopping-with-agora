jQuery(document).ready(function ($) {
    const appID = hdlsa_params.appID;
    const channelName = hdlsa_params.channelName;
    const client = AgoraRTC.createClient({ mode: "live", codec: "vp8" });

    // RTM
    let rtmClient;
    let rtmChannel;
    let uid = sessionStorage.getItem('hdlsa_uid');
    if (!uid) {
        uid = String(Math.floor(Math.random() * 10000));
        sessionStorage.setItem('hdlsa_uid', uid);
    }

    const fetchTokens = async (uid, rtmUid) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: hdlsa_params.ajax_url,
                data: {
                    action: 'hdlsa_generate_agora_tokens',
                    channelName: channelName,
                    uid: uid || 0,
                    rtm_uid: rtmUid,
                    nonce: hdlsa_params.nonce
                },
                success: function (res) {
                    if (res.success) {
                        resolve(res.data);
                    } else {
                        reject(res.data);
                    }
                },
                error: (err) => reject(err)
            });
        });
    };

    const initAgora = async () => {
        try {
            if (!appID) {
                console.error("Agora App ID missing");
                return;
            }

            client.setClientRole("audience");

            // Listen for user publishing
            client.on("user-published", async (user, mediaType) => {
                try {
                    await client.subscribe(user, mediaType);

                    if (mediaType === "video") {
                        // Clear waiting message
                        $("#hdlsa-waiting-message").hide();
                        user.videoTrack.play("hdlsa-full-stream");
                    }
                    if (mediaType === "audio") {
                        user.audioTrack.play();
                    }
                } catch (e) {
                    console.error("Error subscribing to user:", e);
                }
            });

            const viewerRtmUid = "viewer_" + uid;

            // Fetch tokens, but don't block if it fails
            let tokens = { rtcToken: null, rtmToken: null };
            try {
                const res = await fetchTokens(0, viewerRtmUid);
                tokens = res;
            } catch (e) {
                console.warn("Could not fetch tokens for viewer:", e);
            }

            // Join RTC (Pass token if available, otherwise null)
            try {
                await client.join(appID, channelName, tokens.rtcToken || null, null);
            } catch (rtcError) {
                console.error("Failed to join RTC channel:", rtcError);
                $("#hdlsa-full-stream").html('<div style="color:white; text-align:center; padding-top:40%; font-family:sans-serif;">Stream connection failed.<br>Please check App ID and token settings.</div>');
            }

            // Initialize RTM
            try {
                rtmClient = AgoraRTM.createInstance(appID);
                await rtmClient.login({ uid: viewerRtmUid, token: tokens.rtmToken || undefined });

                rtmChannel = rtmClient.createChannel(channelName);
                await rtmChannel.join();

                // Initial viewer count
                rtmChannel.getMembers().then(members => {
                    $("#hdlsa-viewer-count").text(members.length);
                });

                rtmChannel.on('MemberJoined', function (memberId) {
                    rtmChannel.getMembers().then(members => {
                        $("#hdlsa-viewer-count").text(members.length);
                    });
                });

                rtmChannel.on('MemberLeft', function (memberId) {
                    rtmChannel.getMembers().then(members => {
                        $("#hdlsa-viewer-count").text(members.length);
                    });
                });

                // Listen for Channel Messages
                rtmChannel.on('ChannelMessage', function (message, memberId) {
                    handleMessage(message);
                });

                // Load Chat History
                $.get(hdlsa_params.ajax_url, {
                    action: 'hdlsa_get_chat_history',
                    post_id: hdlsa_params.post_id,
                    nonce: hdlsa_params.nonce
                }, (res) => {
                    if (res.success && res.data) {
                        res.data.forEach(item => {
                            appendChatMessage(item.user, item.msg);
                        });
                    }
                });


                // Record Join
                $.post(hdlsa_params.ajax_url, {
                    action: 'hdlsa_record_viewer_event',
                    post_id: hdlsa_params.post_id,
                    type: 'join',
                    uid: uid,
                    nonce: hdlsa_params.nonce
                });

            } catch (rtmError) {
                console.error("Full Viewer RTM Login Error Object:", rtmError);
                console.warn("Viewer RTM initialization failed:", rtmError);
                rtmChannel = null;
                $("#hdlsa-chat-messages").append('<div style="color:#ff5252; font-size:12px; margin-top:10px;">Chat unavailable.</div>');
                $("#hdlsa-chat-input").prop('disabled', true).attr('placeholder', 'Chat unavailable');
            }
        } catch (error) {
            console.error("Critical failure in Agora Viewer init:", error);
        }
    };

    const handleMessage = (message) => {
        try {
            const data = JSON.parse(message.text);

            if (data.type === 'feature_product') {
                showFeaturedProduct(data.product_id);
            } else if (data.type === 'chat') {
                appendChatMessage(data.user, data.msg);
            } else if (data.type === 'like') {
                showFloatingHeart();
            }
        } catch (e) {
            // Might be plain text chat
            // appendChatMessage(memberId, message.text);
        }
    };

    const showFeaturedProduct = (productId) => {
        $.ajax({
            url: hdlsa_params.ajax_url,
            data: {
                action: 'hdlsa_get_product_details',
                product_id: productId,
                nonce: hdlsa_params.nonce
            },
            success: function (res) {
                if (res.success) {
                    const p = res.data;
                    $("#hdlsa-fp-img").attr("src", p.image);
                    $("#hdlsa-fp-name").text(p.name);
                    $("#hdlsa-fp-price").html(p.price);

                    // Update Button for AJAX
                    const btn = $("#hdlsa-fp-btn");
                    btn.attr("href", p.add_to_cart_url);
                    btn.attr("data-product_id", p.id);
                    btn.attr("data-quantity", "1");
                    // Add standard Woo classes: button product_type_simple add_to_cart_button ajax_add_to_cart
                    btn.addClass("button product_type_simple add_to_cart_button ajax_add_to_cart");

                    $("#hdlsa-featured-product").addClass("active"); // Use class for visibility

                    // Auto hide after 15 seconds?
                    setTimeout(() => {
                        $("#hdlsa-featured-product").removeClass("active");
                    }, 15000);
                }
            }
        });
    };

    // Chat Logic
    const appendChatMessage = (user, text) => {
        const div = $("<div>").addClass("hdlsa-chat-msg");
        div.html(`<span class="user">${user}:</span> ${text}`);
        $("#hdlsa-chat-messages").append(div);
        // Scroll to bottom
        const container = document.getElementById("hdlsa-chat-messages");
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    };

    $("#hdlsa-chat-send").click(async function () {
        if (!rtmChannel) {
            alert("Chat is currently unavailable (RTM not connected).");
            return;
        }


        const text = $("#hdlsa-chat-input").val();
        if (!text) return;

        const filterBadWords = (str) => {
            const badWords = hdlsa_params.bad_words || [];
            let newStr = str;
            badWords.forEach(word => {
                if (word && word.trim().length > 0) {
                    const regex = new RegExp(`\\b${word}\\b`, 'gi');
                    newStr = newStr.replace(regex, '***');
                }
            });
            return newStr;
        };

        const filteredText = filterBadWords(text);

        try {
            const msgData = {
                type: 'chat',
                user: 'Guest ' + uid.substring(0, 4),
                msg: filteredText
            };

            const msgString = JSON.stringify(msgData);
            await rtmChannel.sendMessage({ text: msgString });

            // Save to DB for history
            $.post(hdlsa_params.ajax_url, {
                action: 'hdlsa_save_chat_message',
                post_id: hdlsa_params.post_id,
                user: msgData.user,
                msg: filteredText,
                nonce: hdlsa_params.nonce
            });

            // Show my own message
            appendChatMessage('Me', filteredText);
            $("#hdlsa-chat-input").val('');
        } catch (error) {
            console.error("Failed to send chat message:", error);
            alert("Failed to send message.");
        }
    });

    // Like Button Logic
    $("#hdlsa-btn-like").click(async function () {
        if (!rtmChannel) {
            alert("Likes are currently unavailable (RTM not connected).");
            return;
        }

        try {
            const msgData = { type: 'like' };
            await rtmChannel.sendMessage({ text: JSON.stringify(msgData) });
            showFloatingHeart();
        } catch (error) {
            console.error("Failed to send like:", error);
        }
    });

    // Listen for Woo 'added_to_cart' event
    $(document.body).on('added_to_cart', function (e, fragments, cart_hash, $button) {
        // Show a simple toast or feedback
        const originalText = $button.text();
        $button.text("Added!").addClass("added");

        // Reset after 3 seconds
        setTimeout(function () {
            $button.text(originalText).removeClass("added");
        }, 3000);

        // Optional: Show global toast
        if ($("#hdlsa-toast").length === 0) {
            $(".hdlsa-container").append('<div id="hdlsa-toast" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:rgba(0,0,0,0.8); color:white; padding:10px 20px; border-radius:20px; display:none; z-index:999;">Added to Cart!</div>');
        }
        $("#hdlsa-toast").fadeIn().delay(1500).fadeOut();
    });

    // Shopping Bag Logic
    $("#hdlsa-btn-bag").click(function () {
        $("#hdlsa-shopping-bag").addClass("open");

        // Load if empty or force reload
        if ($("#hdlsa-bag-items").children().length === 0) {
            loadStreamProducts();
        }
    });

    const loadStreamProducts = () => {
        const container = $("#hdlsa-bag-items");
        container.html('<div style="padding:20px; text-align:center; color:#999;">Loading products...</div>');


        $.ajax({
            url: hdlsa_params.ajax_url,
            data: {
                action: 'hdlsa_get_stream_products',
                post_id: hdlsa_params.post_id,
                nonce: hdlsa_params.nonce
            },
            success: function (res) {
                container.empty();
                if (res.success && res.data.length > 0) {
                    const products = res.data;
                    products.forEach(p => {
                        const html = `
                            <div class="hdlsa-bag-item-card">
                                <img src="${p.image}" />
                                <div class="info">
                                    <h5>${p.name}</h5>
                                    <span class="price">${p.price}</span>
                                    <a href="${p.add_to_cart_url}" 
                                       class="btn-buy button product_type_simple add_to_cart_button ajax_add_to_cart"
                                       data-product_id="${p.id}"
                                       data-quantity="1"
                                       aria-label="Add to cart">
                                       Add to Cart
                                    </a>
                                </div>
                            </div>
                        `;
                        container.append(html);
                    });
                } else {
                    container.html('<div style="padding:20px; text-align:center; color:#999;">No products in this stream.</div>');
                }
            },
            error: function (err) {
                console.error("Error fetching products:", err);
                container.html('<div style="padding:20px; text-align:center; color:#999;">Error loading products.</div>');
            }
        });
    };

    const showFloatingHeart = () => {
        const heart = document.createElement('div');
        heart.classList.add('hdlsa-floating-heart');
        const randomX = Math.floor(Math.random() * 50);
        heart.style.right = randomX + 'px';
        document.getElementById('hdlsa-hearts-container').appendChild(heart);
        setTimeout(() => { heart.remove(); }, 3000);
    };

    initAgora();

    window.addEventListener('beforeunload', () => {
        if (rtmChannel) {
            rtmChannel.leave();
            // Record Leave
            $.post({
                url: hdlsa_params.ajax_url,
                data: {
                    action: 'hdlsa_record_viewer_event',
                    post_id: hdlsa_params.post_id,
                    type: 'leave',
                    uid: uid,
                    nonce: hdlsa_params.nonce
                },
                async: false // Important for beforeunload
            });
        }
        if (rtmClient) rtmClient.logout();
    });
});
