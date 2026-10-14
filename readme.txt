=== HDWebmobile Live Shopping with Agora ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: live shopping, agora, livestream, commerce, woocommerce
Requires at least: 5.8
Tested up to: 7.1
Stable tag: 1.0.4
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enable live shopping streams on your WordPress site using Agora.io.

== Description ==

HDWebmobile Live Shopping with Agora allows you to host interactive live shopping events directly on your WordPress site.
Broadcasters can feature products from WooCommerce during the stream, and viewers can chat and purchase items in real-time.

**Key Features:**
*   Live Video Streaming via Agora.io RTC.
*   Real-time Chat via Agora.io RTM.
*   Host/Broadcaster UI with camera/mic controls.
*   Product integration with WooCommerce.
*   "Floating Hearts" user engagement.
*   Analytics dashboard (viewers, duration, peak count).

== External Services ==

This plugin relies on the third-party service **Agora.io** to provide real-time video, audio, and messaging functionality, as well as usage analytics for the admin dashboard.

**What the service is and what it is used for:**
Agora.io provides the Real-Time Engagement platform required for live streaming (RTC) and real-time chat (RTM). This plugin uses these services to enable broadcasters to stream video/audio and interact with viewers. Additionally, it uses the Agora REST API (specifically `https://api.agora.io/dev/v3/usage`) to fetch and display streaming usage data in the WordPress admin dashboard.

**What data is sent and when:**
*   **Live Stream (RTC):** Real-time video and audio data are transmitted through Agora's servers whenever a stream is active.
*   **Chat Messaging (RTM):** Text messages and "like" interactions are sent through Agora's messaging servers when users communicate in the chat.
*   **Usage Analytics (REST API):** The plugin sends the Agora project ID and date ranges to `api.agora.io` to retrieve monthly usage minutes. This occurs when an administrator views the plugin's dashboard.
*   **User Identification:** A randomly generated or non-personally identifiable User ID (UID) is sent to manage connection sessions. No personal WordPress user data (like emails or passwords) is sent to Agora.

**Terms and Privacy:**
This service is provided by **Agora.io**:
*   [Agora.io Terms of Service](https://www.agora.io/en/terms-of-service/)
*   [Agora.io Privacy Policy](https://www.agora.io/en/privacy-policy/)

= Enjoying this plugin? =
If it helps your site, please consider [leaving an honest review on WordPress.org](https://wordpress.org/support/plugin/hdwebmobile-live-shopping-with-agora/reviews/#new-post) -- it helps other WordPress users find it. Found a problem or have an idea? Please [open a support topic](https://wordpress.org/support/plugin/hdwebmobile-live-shopping-with-agora/) first so it can be fixed.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/hdwebmobile-live-shopping-with-agora` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Go to "Live Shopping" -> "Settings" to configure your Agora App ID and Certificate.
4.  Create a new Livestream post and start selling!

== How to Use ==

= 1. Get your Agora.io credentials =
Sign up at [agora.io](https://www.agora.io/) and create a project in the Agora Console. You'll need:

* **App ID** — always required.
* **App Certificate** — optional, but strongly recommended; it enables token-based security for your stream.
* **Customer ID** and **Customer Secret** (found under RESTful API in the console) — optional, only needed if you want usage-minutes tracking on the dashboard.
* **Project ID** (found under Project Management — different from the App ID) — also only for usage tracking.

= 2. Enter your credentials =
Go to **Live Shopping > Settings** (Screenshot 1) and paste in your App ID and Certificate. If you also want the "Agora Service Usage" figure on the dashboard, fill in the Customer ID/Secret and Project ID too. The Diagnostic Checklist on this page tells you at a glance what's configured and what's still missing — including a reminder that **Signaling (RTM)** must be enabled for your project in the Agora Console, or chat won't work even with a correct App ID.

= 3. Create a livestream =
Go to **Live Shopping > Add New Stream**. Give it a title, and configure:

* **Host Name / Host Avatar** — who's shown as the broadcaster.
* **Featured Products** — tick any WooCommerce products you want pinned to the shopping bag during this stream.

Publish the post. In the **Embed Shortcode** box in the sidebar, copy the `[hdlsa_stream id="..."]` shortcode (Screenshot 4).

= 4. Embed it on a page =
Paste the shortcode into any page or post. Your theme's header/footer wraps around it normally — the stream itself renders as a self-contained player with chat, a "floating hearts" like button, and a shopping-bag button that opens the Featured Products you selected (Screenshot 2).

= 5. Go live =
Open the page as the host (the same WordPress user who owns the livestream post, or an administrator) to get broadcaster controls (camera/mic, start/stop). Anyone else who opens the same page joins as a viewer automatically — no separate viewer link needed.

= 6. After the stream =
Duration, unique viewers, and peak concurrent viewers are recorded automatically and shown both on the stream's own edit screen and on the aggregate **Live Shopping > Dashboard** (Screenshot 3), which also totals your last-30-days Agora usage minutes if you entered REST API credentials. Chat history and the analytics report are both exportable as CSV from the stream's edit screen.

== Screenshots ==

1. Settings screen — enter your Agora App ID/Certificate and REST API credentials, with a live diagnostic checklist.
2. The live stream player as embedded on a page: video area, chat, like button, and shopping-bag product panel.
3. The aggregate dashboard: lifetime viewers, peak viewers, airtime, Agora usage minutes, and recent streams.
4. The stream edit screen: host details, featured products, analytics, and the ready-to-copy embed shortcode.

== Changelog ==

= 1.0.4 =
* Confirmed compatibility with WordPress 7.1.

= 1.0.3 =
* Confirmed compatibility with WordPress 7.0.
