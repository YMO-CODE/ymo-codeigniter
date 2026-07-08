# WordPress (GoDaddy) + YMO booking integration

Your **marketing site stays on GoDaddy WordPress**. The booking engine runs on the VPS at `booking.yourmechaniconline.com`.

## 1. Update “Book now” links

In WordPress (Elementor, menus, buttons, widgets):

| Old (if any) | New |
|--------------|-----|
| Internal booking page | `https://booking.yourmechaniconline.com/` |
| Package links | `https://booking.yourmechaniconline.com/packages` |

Use **full HTTPS URLs** to the `booking` subdomain — not relative paths.

## 2. Do not expose admin

Staff sign in at:

`https://admin.yourmechaniconline.com/login`

Do **not** add this link to the public WordPress site.

## 3. Contact forms → CRM leads (optional)

To create CRM leads from WordPress enquiry forms, POST JSON to:

```
POST https://booking.yourmechaniconline.com/api/webhooks/website
Content-Type: application/json
X-CRM-Signature: sha256=<hmac-sha256-hex-of-raw-body>
```

Set `CRM_WEBHOOK_SECRET` in the VPS `.env` (same value used to sign requests).

### Option A — Code Snippets plugin

1. Install **Code Snippets** (or use a custom mu-plugin).
2. Copy [`ymo-lead-webhook.php`](ymo-lead-webhook.php) into `wp-content/mu-plugins/` **or** paste its hook logic into a snippet.
3. Edit the constants at the top: `YMO_WEBHOOK_URL`, `YMO_WEBHOOK_SECRET`.
4. Uncomment the `add_action` line for your form plugin (Contact Form 7 example included).

### Option B — Form plugin native webhook

If your form builder supports **webhooks** (WPForms, Fluent Forms, etc.):

- URL: `https://booking.yourmechaniconline.com/api/webhooks/website`
- Method: POST
- Body (JSON):

```json
{
  "name": "{field-name}",
  "mobile": "{field-mobile}",
  "email": "{field-email}",
  "message": "{field-message}",
  "source": "website"
}
```

If the plugin cannot send HMAC headers, use **Option A** or enter leads manually in **Admin → Leads**.

## 4. Email

- **Marketing** email: keep GoDaddy / WordPress mail as today.
- **Transactional** (bookings, OTP, invoices): configured via `YMO_MAIL_*` on the VPS — see [CRM_SETUP.md](../CRM_SETUP.md).

## 5. Site offers popup (optional)

Promotional popups are managed in **Admin → Offers** on the VPS. They appear on the booking site automatically and on WordPress after you install the mu-plugin.

1. Copy [`ymo-offers.php`](ymo-offers.php) to `wp-content/mu-plugins/ymo-offers.php` on GoDaddy.
2. Edit the constants at the top if your booking URL differs (`YMO_OFFERS_API`, `YMO_OFFERS_ASSETS`).
3. Create or activate an offer in **Admin → Offers** on `admin.yourmechaniconline.com`.
4. Visit any WordPress page — the popup should appear within ~5 minutes (WordPress caches the API response for 5 min).
5. Logged-in WP admins can bypass cache with `?ymo_offers_refresh=1` on any URL.

Dismissals are stored in the visitor’s browser (`localStorage`). Editing an offer in admin changes `updated_at`, which shows the popup again to users who dismissed the old version.

## 6. Checklist

- [ ] All “Book now” CTAs point to `https://booking.…`
- [ ] No admin URLs on public pages
- [ ] Test booking flow from a WordPress button
- [ ] (Optional) Test enquiry form → lead appears in admin CRM
- [ ] (Optional) Install `ymo-offers.php` mu-plugin and test offer popup on WordPress + booking
