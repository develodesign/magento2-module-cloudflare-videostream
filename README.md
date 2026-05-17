# Develo_CloudflareVideo

## What this module does

`Develo_CloudflareVideo` adds first-class Cloudflare Stream video support to a Mage-OS / Magento 2 storefront running the Hyvä 1.4 frontend. It recognises Cloudflare Stream URLs pasted into the product media gallery, extracts the video UID server-side, and renders a native Cloudflare `<iframe>` embed on the Product Detail Page (PDP) instead of the default YouTube/Vimeo player. An admin RequireJS mixin short-circuits the core AJAX metadata lookup so the video field accepts Cloudflare URLs without an API error.

## Requirements

- Magento 2.x or Mage-OS 2.x
- Hyvä Theme 1.4+
- `Hyva_CompatModuleFallback` (registers the Hyvä gallery PHTML override)
- PHP 8.x
- `mage-os/module-product-video` (or the Magento 2 equivalent `Magento_ProductVideo`)

## Installation

Copy the module into your project:

```
app/code/Develo/CloudflareVideo/
```

Or, if distributed as a Composer package:

```bash
composer require develo/module-cloudflare-video
```

Then enable and compile:

```bash
bin/magento module:enable Develo_CloudflareVideo
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy adminhtml
bin/magento cache:flush
```

## Configuration

Navigate to **Stores > Configuration > Develo > Cloudflare Video > Customer Code**.

The system config XML path is `develo_cloudflare_video/general/customer_code`.

The customer code is the short identifier shown in the Cloudflare Stream dashboard under **Account > Stream** (it appears in your default stream subdomain, e.g. `customer-<code>.cloudflarestream.com`). Copy and paste it into the field.

**Important notes:**

- The customer code is a **public identifier** that is rendered into every PDP's HTML. It is not a secret and must not be stored as an encrypted value.
- Changing the customer code only affects newly rendered pages. To refresh cached PDPs run:

  ```bash
  bin/magento cache:flush full_page
  ```

## Adding a video to a product

1. Open the product in the Magento admin.
2. Go to the **Images and Videos** tab and click **Add Video**.
3. Paste a Cloudflare Stream URL (any of the supported forms listed below) into the **URL** field.
4. Upload a poster/thumbnail image in the **Preview Image** field — this image is shown in the gallery before the video plays.
5. Save the product.

> **Note:** The admin preview pane will display only the uploaded poster image, not an inline Cloudflare player. This is intentional; the live player is rendered on the storefront PDP only.

## Supported URL forms

The module accepts any of the following as a valid video source:

| Form | Example |
|------|---------|
| `customer-<code>.cloudflarestream.com` | `https://customer-abc123.cloudflarestream.com/a1b2c3d4e5f6…/iframe` |
| `watch.cloudflarestream.com` | `https://watch.cloudflarestream.com/a1b2c3d4e5f6…` |
| `iframe.videodelivery.net` | `https://iframe.videodelivery.net/a1b2c3d4e5f6…` |
| `videodelivery.net` | `https://videodelivery.net/a1b2c3d4e5f6…` |
| Bare 32-hex-char UID | `a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4` |

All four host patterns and the bare 32-character lowercase hexadecimal UID form are recognised. Any path suffix (`/iframe`, `/watch`, `/manifest/…`) and query string are stripped; only the 32-char UID is stored.

## Architecture overview

Server-side, `Model/UidExtractor` parses a Cloudflare URL or bare UID and returns the 32-character video UID. `Model/EmbedUrlBuilder` uses that UID and the configured customer code (`Model/Config`) to build the `<iframe>` `src` URL. `ViewModel/CloudflareVideo` wires these three classes together and exposes `isCloudflareVideo()`, `getEmbedUrl()`, and `getCustomerCode()` to the PHTML template. On the storefront, a Hyvä gallery PHTML override (`view/frontend/templates/Magento_Catalog/templates/product/view/gallery.phtml`) replaces the stock Hyvä gallery; it is registered via `Hyva\CompatModuleFallback\Model\CompatModuleRegistry` in `etc/frontend/di.xml` so it wins over the default template without a theme-level override. On the admin side, a RequireJS mixin on `Magento_ProductVideo/js/get-video-information` (`view/adminhtml/web/js/get-video-information-mixin.js`) intercepts URL validation and the AJAX metadata call so Cloudflare URLs are accepted without error.

## Limitations

- **Public videos only.** Only publicly accessible Cloudflare Stream videos can be embedded; signed URLs (JWT tokens / private video access) are **not supported**.
- No Cloudflare API integration — the module does not call the Cloudflare API to validate video IDs or fetch metadata.
- No GraphQL exposure — the embed URL is not surfaced through any GraphQL schema.

## Maintenance notes

### Regex parity — keep these three files in sync

The Cloudflare URL pattern is implemented **twice** in different languages because the two execution environments cannot share code:

| File | Language | Purpose |
|------|----------|---------|
| `Model/UidExtractor.php` | PHP | Server-side UID extraction |
| `view/adminhtml/web/js/get-video-information-mixin.js` | JavaScript | Admin URL validation (browser) |
| `view/frontend/templates/Magento_Catalog/templates/product/view/gallery.phtml` | JavaScript (inline) | Storefront video type detection (browser) |

All three regex implementations must remain **functionally equivalent**. They cannot be deduplicated because one runs server-side in PHP and the others run browser-side in JavaScript. If Cloudflare changes its URL structure (new subdomain pattern, new path format, etc.), update all three files together and update the unit tests in `Test/Unit/Model/UidExtractorTest.php` to cover the new patterns.

## License

Licensed under the [Open Software License version 3.0 (OSL-3.0)](https://opensource.org/license/osl-3-0-php/). See [`COPYING.txt`](COPYING.txt) for the full license text.
