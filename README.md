# 7th Traditioner

A 7th Tradition contribution system for 12-step fellowships. Integrates with TSML (12 Step Meeting List) and PayPal for secure, PCI-compliant contributions.

**Author:** Alex M
**Contributors:** Built with assistance from Claude Code

---

## Overview

7th Traditioner provides a seamless way for 12-step fellowship groups to accept voluntary member contributions online while honoring the 7th Tradition:

> **"Every group ought to be fully self-supporting, declining outside contributions."**

## Features

✅ **Works with Any Fellowship** - AA, NA, RCA, and all 12-step programs
✅ **TSML Integration** - Automatically pulls groups from 12 Step Meeting List plugin
✅ **PayPal Payment Processing** - Secure, PCI-compliant payment handling
✅ **Multi-Currency Support** - 24 currencies supported with proper display
✅ **reCAPTCHA v3 Protection** - Prevents card testing attacks
✅ **Automatic Receipts** - Beautiful HTML email receipts with meeting day and group info
✅ **Contribution Tracking** - View all contributions with date, currency, and meeting details
✅ **Responsive Design** - Works beautifully on mobile and desktop
✅ **Single-Currency Mode** - Automatically simplifies form when only one currency enabled

---

## Requirements

- **WordPress** 5.8 or higher
- **PHP** 7.4 or higher
- **12 Step Meeting List (TSML)** plugin installed and active
- PayPal Business account

---

## Installation

1. Upload the `7th-traditioner` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure the **12 Step Meeting List (TSML)** plugin is installed and active
4. Navigate to **7th Traditioner** in the WordPress admin menu
5. Configure your settings (see Configuration below)

---

## Configuration

### General Settings

1. Go to **7th Traditioner > General**
2. Set your **Fellowship / Service Body Name** (e.g., "Central Area RCA")
3. Select your **Default Currency**

### PayPal Setup

1. Go to **7th Traditioner > PayPal**
2. Create a [PayPal Business Account](https://www.paypal.com/business) if needed
3. Log in to [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/)
4. Create a new app (or use existing)
5. Copy your **Client ID** and paste it into the plugin settings
6. Select **Sandbox** (for testing) or **Live** (for production)
7. Save settings

### reCAPTCHA Setup (Recommended)

To protect against card testing attacks:

1. Go to **7th Traditioner > reCAPTCHA**
2. Visit [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin)
3. Register a new site:
   - Label: Your site name
   - reCAPTCHA type: **reCAPTCHA v3**
   - Domains: Your domain(s)
4. Copy **Site Key** and **Secret Key**
5. Paste into plugin settings
6. Save settings

---

## Usage

### Add Contribution Form to a Page

Simply add this shortcode to any page or post:

```
[seventh_traditioner]
```

### Customize the Form

You can customize the title and description:

```
[seventh_traditioner title="Support Your Group" description="Your contribution helps keep our meetings running."]
```

### Example Page Setup

1. Create a new page (e.g., "Contribute")
2. Add the shortcode: `[seventh_traditioner]`
3. Publish the page
4. Share the link with your fellowship members

---

## How It Works

### For Members (Contributors)

1. Visit the contribution page
2. Select their group from the dropdown (populated from TSML)
3. Enter contribution amount and currency
4. Optionally add name and notes
5. Click PayPal button to complete payment
6. Receive email receipt automatically

### For Administrators

1. View all contributions in **7th Traditioner > Contributions**
2. See member names (if provided), group names, amounts, and transaction IDs
3. All data is stored securely in WordPress database
4. Export data or integrate with accounting systems

---

## Security & PCI Compliance

✅ **No card data touches your server** - PayPal handles all payment processing
✅ **PCI-DSS compliant by design** - Using PayPal JavaScript SDK
✅ **reCAPTCHA v3 protection** - Invisible spam and fraud prevention
✅ **WordPress nonce verification** - Protects against CSRF attacks
✅ **Data sanitization** - All user input is sanitized and validated
✅ **HTTPS required** - WordPress will enforce SSL for payment pages

---

## Database Schema

The plugin creates one custom table: `wp_seventh_trad_contributions`

**Fields:**
- `id` - Auto-increment primary key
- `transaction_id` - PayPal transaction ID
- `paypal_order_id` - PayPal order ID
- `member_name` - Contributor name (optional)
- `member_email` - Contributor email (for receipt)
- `member_phone` - Contributor phone (optional)
- `contribution_type` - "individual" or "group"
- `meeting_day` - Day of week (0-6, where 0=Sunday)
- `group_name` - Group name from TSML
- `group_id` - Group ID from TSML
- `amount` - Contribution amount
- `currency` - Currency code (USD, EUR, etc.)
- `contribution_date` - Timestamp
- `paypal_status` - PayPal transaction status
- `custom_notes` - Optional notes
- `ip_address` - Contributor IP (for fraud prevention)
- `user_agent` - Browser info
- `created_at` - Record creation timestamp
- `updated_at` - Record update timestamp

---

## Supported Currencies

24 currencies supported:

- AUD - Australian Dollar
- BRL - Brazilian Real
- CAD - Canadian Dollar
- CNY - Chinese Renminbi
- CZK - Czech Koruna
- DKK - Danish Krone
- EUR - Euro
- HKD - Hong Kong Dollar
- HUF - Hungarian Forint
- ILS - Israeli New Shekel
- JPY - Japanese Yen
- MYR - Malaysian Ringgit
- MXN - Mexican Peso
- TWD - New Taiwan Dollar
- NZD - New Zealand Dollar
- NOK - Norwegian Krone
- PHP - Philippine Peso
- PLN - Polish Złoty
- GBP - Pound Sterling
- SGD - Singapore Dollar
- SEK - Swedish Krona
- CHF - Swiss Franc
- THB - Thai Baht
- USD - United States Dollar

---

## Email Receipts

Receipts are automatically sent and include:

- Contribution amount and currency
- Group name with meeting day (e.g., "Sun 10:30 am Santa Cruz RCA")
- Group ID (if provided)
- Date and transaction ID
- Optional custom notes
- 7th Tradition statement

### Improving Email Deliverability

For better email delivery, we recommend:
- [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/)
- [SendGrid](https://wordpress.org/plugins/sendgrid-email-delivery-simplified/)
- [Mailgun](https://wordpress.org/plugins/mailgun/)

---

## Troubleshooting

### PayPal button not showing

- Check that PayPal Client ID is configured
- Check browser console for errors
- Ensure you're in correct mode (sandbox vs live)

### Receipts not sending

- Test email using **7th Traditioner > Email** tab
- Check WordPress email settings
- Consider using an SMTP plugin

### Groups not appearing

- Ensure TSML plugin is installed and active
- Check that groups exist in TSML
- Verify groups have proper data

### reCAPTCHA errors

- Verify Site Key and Secret Key are correct
- Check that domain is registered in reCAPTCHA console
- Ensure reCAPTCHA v3 (not v2) is selected

---

## Developer Hooks & Filters

### Actions

```php
// Before contribution is saved
do_action('seventh_trad_before_save_contribution', $contribution_data);

// After contribution is saved
do_action('seventh_trad_after_save_contribution', $contribution_id, $contribution_data);

// Before receipt email is sent
do_action('seventh_trad_before_send_receipt', $contribution_id);

// After receipt email is sent
do_action('seventh_trad_after_send_receipt', $contribution_id, $success);
```

### Filters

```php
// Modify contribution data before saving
apply_filters('seventh_trad_contribution_data', $data);

// Modify receipt email subject
apply_filters('seventh_trad_receipt_subject', $subject, $contribution);

// Modify receipt email content
apply_filters('seventh_trad_receipt_content', $content, $contribution);

// Modify supported currencies
apply_filters('seventh_trad_currencies', $currencies);
```

---

## Roadmap

Future enhancements being considered:

- [ ] Contribution reports and analytics
- [ ] CSV export
- [ ] Stripe integration option
- [ ] Integration with accounting software
- [ ] Anonymous contribution option
- [ ] Group-specific contribution pages
- [ ] Mobile app integration

---

## Contributing

Contributions are welcome! Please submit pull requests or issues on GitHub.

---

## Support

For issues and questions:
- [GitHub Issues](https://github.com/computeralex/7th-traditioner/issues)

---

## License

GNU General Public License v2.0 or later

See LICENSE file for full details.

---

## Credits

**Author:** Alex M

**Built with assistance from:** Claude Code

**Integrates with:**
- [12 Step Meeting List (TSML)](https://wordpress.org/plugins/12-step-meeting-list/)
- [PayPal](https://www.paypal.com/)
- [Google reCAPTCHA](https://www.google.com/recaptcha/)

---

## The 7th Tradition

> "Every group ought to be fully self-supporting, declining outside contributions."

This plugin honors the 7th Tradition by providing a secure, transparent way for fellowship members to support their groups without accepting money from outside sources. All contributions are voluntary and from members only.
