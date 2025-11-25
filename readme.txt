=== 7th Traditioner ===
Contributors: computeralex
Tags: donations, paypal, 12-step, aa, na, contributions, subscriptions, fundraising
Donate link: https://github.com/computeralex/7th-traditioner
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.2
Requires PHP: 7.4
Requires Plugins: 12-step-meeting-list
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A 7th Tradition contribution system for 12-step fellowships with PayPal integration, multi-currency support, and automatic receipts.

== Description ==

7th Traditioner provides a seamless way for 12-step fellowship groups to accept voluntary member contributions online while honoring the 7th Tradition:

> "Every group ought to be fully self-supporting, declining outside contributions."

= Features =

* **Works with Any Fellowship** - AA, NA, RCA, and all 12-step programs
* **TSML Integration** - Automatically pulls groups from 12 Step Meeting List plugin
* **PayPal Payment Processing** - Secure, PCI-compliant payment handling
* **Multi-Currency Support** - 24 currencies supported with proper display
* **reCAPTCHA v3 Protection** - Prevents card testing attacks
* **Automatic Receipts** - Beautiful HTML email receipts with meeting day and group info
* **Contribution Tracking** - View all contributions with date, currency, and meeting details
* **Responsive Design** - Works beautifully on mobile and desktop
* **Single-Currency Mode** - Automatically simplifies form when only one currency enabled

= How It Works =

**For Members (Contributors):**

1. Visit the contribution page
2. Select their group from the dropdown (populated from TSML)
3. Enter contribution amount and currency
4. Optionally add name and notes
5. Click PayPal button to complete payment
6. Receive email receipt automatically

**For Administrators:**

1. View all contributions in admin dashboard
2. See member names, group names, amounts, and transaction IDs
3. Filter and search contributions
4. All data stored securely in WordPress database

= Security & PCI Compliance =

* No card data touches your server - PayPal handles all payment processing
* PCI-DSS compliant by design using PayPal JavaScript SDK
* reCAPTCHA v3 protection for invisible spam and fraud prevention
* WordPress nonce verification protects against CSRF attacks
* All user input sanitized and validated
* HTTPS required for payment pages

= Supported Currencies =

24 currencies supported including: USD, EUR, GBP, CAD, AUD, JPY, CHF, NOK, SEK, DKK, PLN, HUF, CZK, ILS, MXN, BRL, MYR, PHP, THB, SGD, HKD, TWD, NZD, CNY

== Installation ==

1. Install and activate the **12 Step Meeting List (TSML)** plugin
2. Upload the plugin files to `/wp-content/plugins/7th-traditioner/`, or install through WordPress plugins screen
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Navigate to **7th Traditioner** in the WordPress admin menu
5. Configure your settings:
   - General: Set fellowship name and default currency
   - PayPal: Add your PayPal Client ID (get from developer.paypal.com)
   - reCAPTCHA: Add Site Key and Secret Key (optional but recommended)
   - Email: Customize email subject and from address
6. Add the shortcode `[seventh_traditioner]` to any page

== Frequently Asked Questions ==

= Does this require the 12 Step Meeting List plugin? =

Yes, this plugin requires the 12 Step Meeting List (TSML) plugin to be installed and active. It pulls group information from TSML to populate the meeting dropdown.

= Do I need a PayPal Business account? =

Yes, you need a PayPal Business account to accept contributions. You can create one for free at paypal.com/business.

= Is this PCI compliant? =

Yes! Because all payment processing happens on PayPal's servers using their JavaScript SDK, no card data ever touches your server. This means you're PCI-DSS compliant by design.

= Can I accept recurring contributions? =

Not currently, but it's planned for a future release. The plugin currently supports one-time contributions only.

= What currencies are supported? =

24 currencies are supported including USD, EUR, GBP, CAD, AUD, JPY, and many more. See the full list in the Description section.

= Where can I get support? =

For issues and questions, please visit the [GitHub repository](https://github.com/computeralex/7th-traditioner/issues).

== Screenshots ==

1. Contribution form with currency selector and group dropdown
2. Admin contributions dashboard with filtering
3. Contribution details view
4. Plugin settings page
5. Email receipt example

== Changelog ==

= 1.0.2 =
* Fix: Meeting day display for Sunday (PHP empty('0') bug)
* Fix: Cache form data to ensure reliable capture before PayPal popup
* Fix: Single-currency mode loading issues
* Fix: Start Over button functionality
* Add: Date column to contributions table
* Add: Proper currency display (shows actual currency instead of forcing USD)
* Add: Multiple currency handling with proper messaging
* Add: Meeting Day in contribution details
* Update: Email displays meeting day abbreviation (Sun, Mon, etc.)
* Update: Remove dash between time and meeting name in emails
* Update: Email subject line (no auto-append fellowship name)
* Update: LICENSE to GPL v2.0 (WordPress standard)

= 1.0.1 =
* Initial release with basic contribution functionality
* PayPal integration
* Multi-currency support
* Email receipts
* TSML integration

== Upgrade Notice ==

= 1.0.2 =
Critical bug fixes for Sunday contributions and single-currency mode. Admin improvements and better currency handling. Recommended update for all users.

== Third-Party Services ==

This plugin relies on the following third-party services:

**PayPal**
* Service: Payment processing
* Website: https://www.paypal.com/
* Privacy Policy: https://www.paypal.com/privacy
* Terms: https://www.paypal.com/webapps/mpp/ua/useragreement-full
* Data Shared: Transaction amount, currency, payer email/name (if provided)

**Google reCAPTCHA** (Optional)
* Service: Spam and fraud prevention
* Website: https://www.google.com/recaptcha/
* Privacy Policy: https://policies.google.com/privacy
* Terms: https://policies.google.com/terms
* Data Shared: User interaction data for bot detection

Both services are only used when users actively make contributions through the plugin.
