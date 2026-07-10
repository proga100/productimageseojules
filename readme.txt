=== Product Image SEO — AI Alt Text & Image SEO Audit ===
Contributors: flance
Tags: product images, image seo, alt text, ecommerce seo, bulk alt text
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI alt text and image SEO audit for product catalogs. Per-product dashboard, bulk fix by category, Google Image Search readiness scoring.

== Description ==

**Built specifically for online stores.** Product Image SEO scans your
entire product catalog, generates accurate AI-powered alt text for every
product image using your product titles, categories, attributes, and SKUs
as context, and helps your products rank in Google Image Search.

Unlike generic alt text plugins, Product Image SEO understands product
data. It treats variation images, gallery images, and featured images
differently. It uses your product attributes as context. It gives you a
product-by-product catalog dashboard with SKUs, prices, and quality scores.
And it exports a full image SEO audit report for your entire store.

= Why product image SEO matters =

Google Image Search drives over 20% of all Google traffic. For online
stores, it is a massive source of buyer-intent traffic. But Google cannot
"see" your images — it reads alt text to understand what each image shows.
Poorly written or missing alt text means your products are invisible to
image search, invisible to Google Shopping, and increasingly invisible to
AI shopping assistants like ChatGPT and Perplexity.

Product Image SEO fixes this at scale.

= Key features =

* **Product catalog dashboard** — see alt text status across your entire store at a glance
* **Context-aware AI generation** — uses product title, category, attributes, SKU, and tags
* **Bulk processing** — fix thousands of product images in one run with progress tracking
* **Auto-generate on save** — new products get alt text automatically
* **Variation image support** — handles product variation images correctly
* **Gallery image support** — processes featured image plus all gallery images
* **Image SEO audit reports** — full CSV export of catalog image status
* **Quality scoring** — see which products are ready for image search and which need attention
* **Filter by category, stock, date** — prioritize which products to fix first
* **SEO-focused output** — alt text written for search engines AND accessibility

= Who this is for =

* Online store owners with 100+ product images
* E-commerce managers running product catalogs
* Agencies managing client e-commerce sites
* Anyone who wants products to rank in Google Image Search

= How it works =

1. Install the plugin and activate it (WooCommerce must be active)
2. Enter your Alt Audit API key (free account includes 25 credits/month)
3. Open the Product Image SEO dashboard under your store menu
4. Click "Scan Products" to see current alt text status across your catalog
5. Bulk-generate alt text for products that need it
6. Review and approve AI suggestions
7. Export an image SEO audit report for your records

= Requires Alt Audit API =

This plugin connects to the Alt Audit service at altaudit.com. The plugin
itself is free and open source. Alt text generation uses credits from your
Alt Audit account:

* **Free plan:** 25 credits/month forever (no credit card)
* **Pro plan:** 3,000 credits/month
* **Agency plan:** 10,000 credits/month

One credit processes one product image. Sign up at [altaudit.com](https://altaudit.com).

= Related plugin =

If you run a content site or blog (not an online store), see our general
purpose [Alt Audit plugin](https://wordpress.org/plugins/alt-audit/) instead.
Both plugins share the same Alt Audit account.

== Installation ==

1. Go to Plugins > Add New in your WordPress admin
2. Search for "Product Image SEO"
3. Click Install Now, then Activate
4. Make sure WooCommerce is installed and active
5. Open the new "Product Image SEO" submenu under your store menu
6. Click Settings and enter your Alt Audit API key (get one free at altaudit.com)
7. Click Dashboard and run your first scan

== Frequently Asked Questions ==

= Does this work with product variations? =

Yes. Product Image SEO processes featured images, gallery images, and
variation images. Each variation can get its own alt text based on its
specific attributes like color, size, and style.

= How is this different from the Alt Audit plugin? =

Both plugins are by the same author and share the same Alt Audit account
for AI credits, but they solve different problems:

**Alt Audit** is a media library auditor. It scans every image in your
WordPress media library, scores each one against WCAG 2.1 quality criteria,
and helps you fix attachments one at a time or in bulk. It works on any
WordPress site (blogs, content sites, brochure sites).

**Product Image SEO** is a product catalog auditor. It scans your products
and tracks image SEO coverage at the product level — featured image,
gallery images, variation images, all together. It uses product-specific
data (SKU, attributes, categories, variations, price) when generating alt
text, which Alt Audit does not. It exports CSV audit reports organized by
product and category, not by individual image.

If you run a blog, use Alt Audit. If you run an online store, use Product
Image SEO. If you run a store with a blog, use both — they don't conflict.

= Does this work with my page builder? =

Yes. Product Image SEO works with Elementor, Divi, Gutenberg, WPBakery,
Beaver Builder, and any other page builder. It writes to the standard
WordPress image alt attribute, which every builder reads.

= Do you store my product images? =

No. The Alt Audit service accesses image URLs temporarily to generate alt
text, then discards the image data. Only the generated text and usage
metadata are stored. See the privacy policy at altaudit.com/privacy-policy
for details.

= What AI does this use? =

The Alt Audit service uses Google Gemini multimodal AI to analyze product
images and generate contextual alt text. Gemini understands image content
and combines it with product data the plugin sends (title, category, attributes).

= Can I edit AI suggestions before saving? =

Yes. Every generated alt text goes into a review queue where you can accept,
edit, or rewrite before it is saved to your product.

= Is this compatible with Yoast SEO / Rank Math / AIOSEO? =

Yes. Product Image SEO writes only to the standard WP image alt attribute.
It does not conflict with any SEO plugin. It also reads focus keywords from
all three for better AI context.

= Will this slow down my store? =

No. All AI generation happens on Alt Audit servers, not yours. The plugin
writes finished alt text to your database and nothing else. Background
processing uses Action Scheduler (the same system WooCommerce uses) so
nothing blocks your admin or front-end.

= What happens if I run out of credits? =

The plugin continues to work for manual edits, audit reports, and CSV
exports. New AI generation pauses until you upgrade or wait for your
monthly credit reset.

= Is this GPL licensed? =

Yes, GPLv2 or later. The plugin code is free and open source.

== Screenshots ==

1. Product catalog dashboard with alt text status, SKUs, and quality scores
2. Bulk fix interface with category filters and progress tracking
3. Settings page with API key and alt text style options
4. Single product view with AI-generated alt text suggestions
5. Image SEO audit report ready for CSV export

== External services ==

This plugin connects to the Alt Audit API (https://altaudit.com/api/v1) to
generate AI alt text for product images. This service is required for the
plugin's core functionality.

**What is sent and when:**
- When a user clicks "Generate Alt Text" on a product, the plugin sends
  the product image URL, product title, category path, attributes, SKU,
  and short description to the Alt Audit API.
- When a user runs a bulk generation job from the Bulk Fix or Catalog
  screens, the plugin sends the same product data for each selected
  product to the Alt Audit API.
- When the "Auto-generate on save" setting is enabled in the plugin
  settings, the plugin also sends product data to the Alt Audit API
  automatically each time a product is saved or updated in WooCommerce
  (whether via the admin UI, REST API, CSV import, or programmatic save).
  This setting is disabled by default — no data is sent automatically
  unless the user explicitly enables it.
- No data is sent on plugin activation, deactivation, or during normal
  browsing.
- The API key is sent with each request to authenticate the account.

**Data handling:**
- Alt Audit does not store uploaded product images on its servers.
- Only the generated alt text and usage metadata are retained.
- Privacy policy: https://altaudit.com/privacy-policy
- Terms of service: https://altaudit.com/terms-of-service

== Changelog ==

= 1.0.0 =
* Initial release.
* Product catalog dashboard with alt text status per product.
* Single-product and bulk AI alt text generation.
* WooCommerce context awareness (title, category, attributes, SKU, tags, price).
* Auto-generation on product save (optional).
* CSV export of full image SEO audit.
* Settings page with style and length controls.
* Variation and gallery image support.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and scan your product catalog to boost image SEO.