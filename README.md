# Sunmerce Remote Gallery for Magento 2

Display product images hosted on a remote CDN instead of the native Magento media gallery — perfect for image syndication between servers, headless-style setups, or offloading image storage entirely.

## Features

- **Remote product galleries** — attach a list of CDN image URLs to any product and it replaces the native media gallery on the storefront.
- **Smart fallback** — products *without* remote gallery data automatically fall back to their standard Magento media gallery. Nothing breaks.
- **Full storefront coverage** — works on the product page gallery, category/search listings, widgets, related products, cart, checkout, minicart, and configurable product swatches.
- **Admin visibility** — remote images show up in the admin product grid thumbnails too.
- **CDN optimization** — optionally inject role-specific image optimization options (e.g. Cloudflare Images or query-string params) into every URL per image role: thumbnail, main image, full image, and listing.
- **Zero image storage** — no files are uploaded or copied to your `pub/media` directory.

---

## Requirements

- Magento Open Source / Adobe Commerce **2.4.8+**
- PHP version required by your Magento installation

---

## Installation

### 1. Install via Composer

```bash
composer require sunmerce/module-remote-gallery
```

### 2. Enable the module

```bash
bin/magento module:enable Sunmerce_RemoteGallery
bin/magento setup:upgrade
```

### 3. Compile and deploy (production mode only)

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

### 4. Flush caches

```bash
bin/magento cache:flush
```

The module is **enabled by default** once installed. A site admin can turn it off at any time under **Stores → Configuration → Catalog → Catalog → Remote Gallery (Sunmerce)** by setting **Enable Remote Gallery** to **No**.

---

## Configuration

All settings live under:

**Stores → Configuration → Catalog → Catalog → Remote Gallery (Sunmerce)**

Settings can be overridden per website and per store view.

| Setting | Description |
|---|---|
| **Enable Remote Gallery** | Turn remote galleries on/off. Defaults to **Yes** after installation. Products without remote data always fall back to the native gallery. |
| **Optimization Query Location** | How optimization options are inserted into remote URLs (see below). |
| **Thumbnail Optimization Options** | Applied to the small thumbnails in the PDP gallery controls. |
| **Main Image Optimization Options** | Applied to the main product page gallery image. |
| **Full Image Optimization Options** | Applied to the full-size (zoomed) gallery image. |
| **Listing Image Optimization Options** | Applied to category, search, widget, and related product images. |
| **Allowed CSP Image Hosts** | Hosts added to the browser Content-Security-Policy `img-src` directive, one per line (e.g. `cdn.example.com`). Required when Magento's CSP is in enforce mode so remote images are not blocked. |

### Optimization Query Location

Choose the strategy that matches your CDN:

| Mode | Behavior | Example |
|---|---|---|
| **After Domain** | Options are inserted as a path segment right after the hostname | `https://cdn.example.com/`**`cdn-cgi/image/width=88,height=110,fit=cover`**`/images/shoe.jpg` |
| **Append Query Parameters** | Options are appended to the URL's query string | `https://cdn.example.com/images/shoe.jpg`**`?width=88&height=110`** |
| **Replace `{OPTIMIZE_OPTIONS}` Placeholder** | The placeholder inside your stored URLs is replaced per role | `https://cdn.example.com/`**`{OPTIMIZE_OPTIONS}`**`/shoe.jpg` |

Leave the option fields empty if your CDN does not support optimization or you want to serve the URLs as-is.

---

## User Manual

### Adding remote images to a product

1. Go to **Catalog → Products** and open a product for editing.
2. Expand the **Remote Gallery** section (near the bottom of the form).
3. Click **Add** to create a row and fill in:
   - **Image URL** — the full `https://` URL of the image on your CDN.
   - **Name** — an optional label/alt text for the image.
   - **Position** — sort order; lower numbers appear first. The first image becomes the main image.
4. Add as many rows as you need, then **Save** the product.

The storefront gallery for that product now serves the remote images. Delete all rows to revert to the native Magento gallery.

### Where remote images appear

Once a product has remote gallery rows, its images are replaced everywhere Magento shows product images:

- Product detail page gallery (main image, full-size view, and thumbnails)
- Category and search result listings
- Product widgets (bestsellers, new products, etc.)
- Related / up-sell / cross-sell blocks
- Cart, minicart, and checkout summary
- Configurable swatch base images
- Admin product grid thumbnails

### Notes & limitations

- URLs must start with `http://` or `https://` — anything else is skipped.
- The first image (lowest position) is used as the product's base image in listings and cart.
- Remote images are never downloaded or cached locally; make sure your CDN is publicly reachable and fast.
- If you use the `{OPTIMIZE_OPTIONS}` placeholder mode, every stored URL must contain the placeholder.

---

## Troubleshooting

**Images don't show after installing**
Run `bin/magento setup:upgrade` and flush caches. Verify the module is enabled with `bin/magento module:status Sunmerce_RemoteGallery`, and confirm **Enable Remote Gallery** is set to **Yes** under **Stores → Configuration → Catalog → Catalog → Remote Gallery (Sunmerce)** (it defaults to **Yes** after installation).

**A product still shows its native gallery**
Open the product in the admin and confirm the **Remote Gallery** section has rows with valid `https://` URLs. Empty rows are silently ignored.

**Blocked by Content Security Policy (CSP)**
Add your CDN host(s) to the **Allowed CSP Image Hosts** setting (one per line). The module injects them into the `img-src` directive dynamically — no `csp_whitelist.xml` needed. If images are still blocked, check the browser console for the exact blocked host.

**Broken image URLs when optimization is enabled**
Check that the **Optimization Query Location** mode matches your CDN's URL format, and that the option values are correctly formatted (e.g. `width=88&height=110` for query mode).

---

## Uninstalling

```bash
composer remove sunmerce/module-remote-gallery
bin/magento setup:upgrade
bin/magento cache:flush
```

The `remote_gallery` product attribute created during installation should also be removed from **Stores → Attributes → Product** if you want a complete cleanup.

---

## Support

For questions, issues, or feature requests, please contact Sunmerce support.
