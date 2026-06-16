=== 7th Traditioner ===
Contributors: computeralex
Tags: contributions, 7th-tradition, paypal, 12-step, aa, na, subscriptions, self-support
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.1.17
Requires PHP: 7.4
Requires Plugins: 12-step-meeting-list
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A 7th Tradition system for voluntary member contributions in 12-step fellowships. PayPal integration, multi-currency support, and automatic receipts.

== Description ==

7th Traditioner helps fellowships accept voluntary contributions from their own members, honoring the 7th Tradition:

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

= 1.1.17 =
* Feature: Export contributions to CSV from the admin Contributions screen (respects current filters)

= 1.1.16 =
* Copy: Rename "Monthly contribution" button to "Recurring contribution"

= 1.1.15 =
* Change: Remove built-in 7th Tradition notice (handled on the page separately)

= 1.1.14 =
* Copy: Tone down form language (PayPal/tax context); keep voluntary member contributions and no dues or fees

= 1.1.13 =
* Copy: 7th Tradition language throughout — voluntary member contributions only; no dues, fees, gifts, or donations

= 1.1.12 =
* Copy: Replace "gift" wording with 7th Tradition self-support language

= 1.1.11 =
* Change: Replace monthly checkbox with upfront one-time vs monthly choice (locked after selection, like currency)

= 1.1.10 =
* Fix: Monthly contribution checkbox no longer greyed out (removed lock; moved above contributor type)

= 1.1.9 =
* Fix: Contributor type can be switched without losing payment buttons (same SDK, re-render only)
* Change: Monthly checkbox locks after payment buttons load (PayPal cannot swap subscription/one-time in-place)

= 1.1.8 =
* Fix: PayPal + debit/credit available for both individual and group one-time contributions
* Fix: Payment buttons stay visible when switching between individual and group
* Change: Only monthly recurring contributions are PayPal-only (PayPal account required)

= 1.1.7 =
* Change: Payment buttons load only after contributor type is selected

= 1.1.6 =
* Fix: PayPal buttons load on first page view (removed invalid data-namespace SDK param PayPal now rejects with HTTP 400)
* Fix: Load one PayPal SDK at a time; swap capture/subscription when monthly toggle changes

= 1.1.5 =
* Fix: PayPal buttons render on first load (no longer refresh when contributor type changes without a layout shift)

= 1.1.4 =
* Fix: PayPal buttons stay visible when switching contributor type (individual vs group)

= 1.1.3 =
* Fix: PayPal buttons reappear when toggling monthly contribution on/off (separate capture/subscription SDK namespaces)

= 1.1.2 =
* Fix: Saving PayPal, reCAPTCHA, or Email settings no longer resets currencies or other General tab options

= 1.1.1 =
* Fix: Load reCAPTCHA v3 dynamically when page cache strips the enqueued script
* Security: Issue gate token after server-side reCAPTCHA passes; withhold PayPal client ID until then
* Security: Require gate token on payment and save endpoints
* Improve: Clearer reCAPTCHA error logging (missing token, low score, API errors)

= 1.0.3 =
* Security: Add reCAPTCHA gate verification on currency selection
* Security: Prevent card testing attacks before form loads
* Fix: Move reCAPTCHA verification to before payment (prevents blocking after PayPal capture)
* Add: Loading spinner during reCAPTCHA verification
* Add: "Try Again" button if reCAPTCHA verification fails
* Improve: Better fraud prevention workflow

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

= 1.0.3 =
Important security improvement: reCAPTCHA verification now happens before payment form loads, preventing card testing attacks and fixing issue where verification blocked contributions after PayPal already captured payment.

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
