=== CWS Turnstile ===
Contributors: albertomake
Tags: security, spam, captcha, cloudflare, turnstile, form, protection
Requires at least: 5.3
Tested up to: 6.8.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates Cloudflare Turnstile protection for WordPress search forms to prevent spam and bot submissions.

== Description ==

CWS Turnstile is a lightweight plugin that adds Cloudflare Turnstile protection to your WordPress search forms. Cloudflare Turnstile is a privacy-focused CAPTCHA alternative that helps protect your site from spam and abuse.

### Features

* Easy integration with WordPress search forms
* Admin interface to manage your Turnstile API keys
* Simple setup - just add your Cloudflare Turnstile keys
* Securely validates form submissions
* Privacy-focused alternative to traditional CAPTCHA services

### How It Works

1. The plugin adds the Turnstile widget to your search form
2. When a user submits a search, Turnstile validates they're human
3. If validation passes, the search proceeds normally
4. If validation fails, the user is redirected to the home page

### Requirements

* A Cloudflare account
* Cloudflare Turnstile site key and secret key

== Installation ==

1. Upload the `cws-turnstile` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > CWS Turnstile to configure your API keys

== Frequently Asked Questions ==

= Where do I get my Cloudflare Turnstile keys? =

You can obtain your Turnstile site key and secret key by:
1. Logging into your Cloudflare account
2. Going to the Turnstile section
3. Creating a new site
4. Copying the site key and secret key

= Does this work with all WordPress themes? =

The plugin works with most WordPress themes. If your theme has a custom search form implementation, you may need to make minor adjustments to integrate the Turnstile widget.

= Is this compatible with caching plugins? =

Yes, CWS Turnstile is compatible with most caching plugins as the verification happens on form submission.

= Can I use this on other forms besides search? =

Currently, the plugin is designed specifically for WordPress search forms. Future versions may include support for additional form types.

== Screenshots ==

1. The Turnstile widget embedded in a search form
2. Plugin settings page where you can enter your API keys

== Changelog ==

= 1.0.2 =
* Add admin settings to allow users to select Theme and Size options

= 1.0.1 =
* Added uninstall.php to clean up plugin options on uninstall

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of CWS Turnstile plugin.

== Privacy Policy ==

CWS Turnstile uses Cloudflare Turnstile for form protection. When a user interacts with the Turnstile widget, data may be sent to Cloudflare's servers for verification. This may include the user's IP address and browser information. For more information about Cloudflare's privacy practices, please visit [Cloudflare's Privacy Policy](https://www.cloudflare.com/privacypolicy/).