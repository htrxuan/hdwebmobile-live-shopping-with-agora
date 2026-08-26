jQuery(document).ready(function ($) {
    const appID = hdlsa_params.appID;
    const channelName = hdlsa_params.channelName;
    const client = AgoraRTC.createClient({ mode: "live", codec: "vp8" });
    let localTracks = {
        videoTrack: null,
        audioTrack: null
    };

    // RTM
    let rtmClient;
    let rtmChannel;

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
        // Start local preview immediately (don't wait for RTM/RTC)
        await setupLocalTracks();

        try {
            if (!appID) {
                console.error("Agora App ID is missing in settings.");
                alert("Agora App ID is missing. Please check plugin settings.");
                return;
            }

            client.setClientRole("host");

            let rtmUid = sessionStorage.getItem('hdlsa_host_rtm_uid');
            if (!rtmUid) {
                rtmUid = "host_" + Math.floor(Math.random() * 10000);
                sessionStorage.setItem('hdlsa_host_rtm_uid', rtmUid);
            }

            // Try to fetch tokens, but don't block if it fails
            let tokens = { rtcToken: null, rtmToken: null };
            try {
                const res = await fetchTokens(0, rtmUid);
                tokens = res;
            } catch (e) {
                console.warn("Could not fetch tokens:", e);
            }

            // Initialize RTM
            try {
                rtmClient = AgoraRTM.createInstance(appID);
                await rtmClient.login({ uid: rtmUid, token: tokens.rtmToken || undefined });

                rtmChannel = rtmClient.createChannel(channelName);
                await rtmChannel.join();

                // Initial viewer count
                rtmChannel.getMembers().then(members => {
                    $("#hdlsa-viewer-count").text(members.length);
                });

                rtmChannel.on('MemberJoined', function (memberId) {
                    rtmChannel.getMembers().then(members => {
                        $("#hdlsa-viewer-count").text(members.length);
                        // Record peak
                        $.post(hdlsa_params.ajax_url, {
                            action: 'hdlsa_record_viewer_event',
                            post_id: hdlsa_params.post_id,
                            type: 'count',
                            count: members.length,
                            nonce: hdlsa_params.nonce
                        });
                    });
                });

                rtmChannel.on('MemberLeft', function (memberId) {
                    rtmChannel.getMembers().then(members => {
                        $("#hdlsa-viewer-count").text(members.length);
                    });
                });

                rtmChannel.on('ChannelMessage', function (message, memberId) {
                    handleMessage(message);
                });


            } catch (rtmError) {
                console.error("Full RTM Login Error Object:", rtmError);
                console.warn("RTM Initialization failed (Chat/Features will be disabled):", rtmError);
                rtmChannel = null;
                $("#hdlsa-chat-messages").append('<div style="color:#ff5252; font-size:12px; margin-bottom:10px;">Chat unavailable (Check Agora Console).</div>');
                // Disable chat input
                $("#hdlsa-chat-input").prop('disabled', true).attr('placeholder', 'Chat unavailable');
            }
        } catch (error) {
            console.error("Critical failure in initAgora:", error);
        }
    };

    const setupLocalTracks = async () => {
        try {
            if (localTracks.videoTrack || localTracks.audioTrack) return;


            // Create tracks
            const [audioTrack, videoTrack] = await Promise.all([
                AgoraRTC.createMicrophoneAudioTrack().catch(e => {
                    console.warn("Microphone access denied:", e);
                    return null;
                }),
                AgoraRTC.createCameraVideoTrack().catch(e => {
                    console.warn("Camera access denied:", e);
                    return null;
                })
            ]);

            localTracks.audioTrack = audioTrack;
            localTracks.videoTrack = videoTrack;

            if (localTracks.videoTrack) {
                localTracks.videoTrack.play("hdlsa-full-stream");
            } else {
                $("#hdlsa-full-stream").html('<div style="color:white; text-align:center; padding-top:40%; font-family:sans-serif;">Camera access denied or not found.<br>Please allow camera permissions and refresh.</div>');
            }
        } catch (error) {
            console.error("Error setting up local tracks:", error);
        }
    };

    const startStream = async () => {
        try {
            if (!appID) return;

            $("#hdlsa-btn-start").prop('disabled', true).text("Starting...");

            // Fetch RTC token (using 0 to let Agora assign UID)
            const tokens = await fetchTokens(0, null);

            // Join RTC Channel
            const uid = await client.join(appID, channelName, tokens.rtcToken || null, null);

            const tracksToPublish = [];
            if (localTracks.audioTrack) tracksToPublish.push(localTracks.audioTrack);
            if (localTracks.videoTrack) tracksToPublish.push(localTracks.videoTrack);

            if (tracksToPublish.length > 0) {
                await client.publish(tracksToPublish);
            } else {
                throw new Error("No audio or video tracks available to publish.");
            }

            $("#hdlsa-live-indicator").show();
            $("#hdlsa-btn-start").prop('disabled', false).text("End Stream").off('click').click(leaveStream);

            // Record Start
            $.post(hdlsa_params.ajax_url, {
                action: 'hdlsa_record_stream_event',
                post_id: hdlsa_params.post_id,
                type: 'start',
                nonce: hdlsa_params.nonce
            });

        } catch (error) {
            console.error("Failed to start stream:", error);
            alert("Could not start stream: " + error.message);
            $("#hdlsa-btn-start").prop('disabled', false).text("GO LIVE");
        }
    };

    const leaveStream = async () => {
        try {
            for (trackName in localTracks) {
                var track = localTracks[trackName];
                if (track) {
                    track.stop();
                    track.close();
                    localTracks[trackName] = null;
                }
            }
            await client.leave();

            $("#hdlsa-live-indicator").hide();
            $("#hdlsa-btn-start").text("GO LIVE").off('click').click(startStream);

            // Record End
            $.post(hdlsa_params.ajax_url, {
                action: 'hdlsa_record_stream_event',
                post_id: hdlsa_params.post_id,
                type: 'end',
                nonce: hdlsa_params.nonce
            });
        } catch (error) {
            console.error("Error leaving stream:", error);
        }
    };

    // UI Handlers
    $("#hdlsa-btn-start").click(startStream);

    $("#hdlsa-btn-mic").click(function () {
        if (localTracks.audioTrack) {
            if (localTracks.audioTrack.muted) {
                localTracks.audioTrack.setMuted(false);
                $(this).removeClass("off");
            } else {
                localTracks.audioTrack.setMuted(true);
                $(this).addClass("off");
            }
        }
    });

    $("#hdlsa-btn-cam").click(function () {
        if (localTracks.videoTrack) {
            if (localTracks.videoTrack.muted) {
                localTracks.videoTrack.setMuted(false);
                $(this).removeClass("off");
            } else {
                localTracks.videoTrack.setMuted(true);
                $(this).addClass("off");
            }
        }
    });

    // Feature Product Logic (Global function to be called from onclick)
    window.hdlsaBroadcaster = {
        featureProduct: async function (productId) {
            if (!rtmChannel) {
                alert("Chat/Product featuring is currently unavailable (RTM not connected).");
                return;
            }

            try {
                const msg = JSON.stringify({
                    type: 'feature_product',
                    product_id: productId
                });

                await rtmChannel.sendMessage({ text: msg });
                // alert("Featured product " + productId + " to viewers.");
            } catch (error) {
                console.error("Failed to send product feature:", error);
                alert("Failed to feature product. Please check connection.");
            }
        }
    };

    const handleMessage = (message) => {
        try {
            const data = JSON.parse(message.text);
            if (data.type === 'chat') {
                appendChatMessage(data.user, data.msg);
            } else if (data.type === 'like') {
                showFloatingHeart();
            }
        } catch (e) { }
    };

    const showFloatingHeart = () => {
        const heart = document.createElement('div');
        heart.classList.add('hdlsa-floating-heart');
        const randomX = Math.floor(Math.random() * 50);
        heart.style.right = randomX + 'px';
        document.getElementById('hdlsa-hearts-container').appendChild(heart);
        setTimeout(() => { heart.remove(); }, 3000);
    };

    const appendChatMessage = (user, text) => {
        const div = $("<div>").addClass("hdlsa-chat-msg");

        let content = `<span class="user">${user}:</span> ${text}`;

        // Add reply button if it's not me
        if (user !== 'Me') {
            content += ` <span class="hdlsa-chat-reply-btn" data-user="${user}" title="Reply to ${user}">↩</span>`;
        }

        div.html(content);
        $("#hdlsa-chat-messages").append(div);

        const container = document.getElementById("hdlsa-chat-messages");
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    };

    // Handle Reply Click
    $(document).on('click', '.hdlsa-chat-reply-btn', function () {
        const user = $(this).data('user');
        const input = $("#hdlsa-chat-input");
        const mention = `@${user} `;

        // Append mention to current input
        input.val(input.val() + mention);
        input.focus();
    });

    $("#hdlsa-chat-send").click(async function () {
        if (!rtmChannel) {
            alert("Chat is unavailable (RTM not connected).");
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
                user: hdlsa_params.host_name,
                msg: filteredText
            };

            const msgString = JSON.stringify(msgData);
            await rtmChannel.sendMessage({ text: msgString });

            // Save to DB for history
            $.post(hdlsa_params.ajax_url, {
                action: 'hdlsa_save_chat_message',
                post_id: hdlsa_params.post_id,
                user: hdlsa_params.host_name,
                msg: filteredText,
                nonce: hdlsa_params.nonce
            });

            appendChatMessage('Me', filteredText);
            $("#hdlsa-chat-input").val('');
        } catch (error) {
            console.error("Failed to send chat message:", error);
            alert("Failed to send message.");
        }
    });

    initAgora();

    window.addEventListener('beforeunload', () => {
        if (rtmChannel) rtmChannel.leave();
        if (rtmClient) rtmClient.logout();
    });
});
