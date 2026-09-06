# Ad-monetised storefront

A PHP storefront modelled on `social.theblogbeast.in`: a product catalogue with a
funnel (product → cart → checkout → confirmation) where every step carries a
configurable ad slot, plus an admin panel to manage products, ad slots, orders
and settings.

No database and no Composer — data lives in JSON files.

## Requirements

PHP 7.4+ (tested on 8.1) and Apache with `mod_rewrite`, or PHP's built-in server
for local work.

## Run it locally

```bash
php -S 127.0.0.1:8000 router.php
```

Then open <http://127.0.0.1:8000/>. `router.php` mirrors the `.htaccess`
rewrites so clean URLs work without Apache; on a real server Apache uses
`.htaccess` and `router.php` is ignored.

First visit to `/admin/` sends you to `/admin/setup.php` to choose an admin
password. It is stored as a hash in `config/admin.json`, and the setup page
locks itself once that file exists.

## Before going live

1. **`/admin/settings.php`** — site name, tagline, description, contact details,
   products per page.
2. **`/admin/ads.php`** — your AdSense publisher ID (`ca-pub-…`) and Ad Manager
   network code, then the ad unit name for each slot.
3. **`robots.txt`** — replace `https://example.com/sitemap.xml` with your domain.
4. **`config/`, `data/`, `includes/`** must not be web-readable. The root
   `.htaccess` blocks them and each folder carries its own `Require all denied`
   as a fallback; if your host sets `AllowOverride None`, move these folders
   above the web root instead.

## Deploying to Vercel (demo)

Vercel runs PHP through the community
[`vercel-php`](https://github.com/vercel-community/php) runtime, already wired up
in `vercel.json`. Every request that is not a static asset goes to
`api/index.php`, which uses the same URL map as local development
(`includes/routes.php`).

1. Push this repo to GitHub.
2. On [vercel.com](https://vercel.com) → **Add New → Project** → import the repo.
3. Framework preset **Other**, no build command, leave the root directory alone.
4. **Environment Variables** → add `ADMIN_PASSWORD` with the password you want
   for `/admin/`. Deploy.

If a deploy misbehaves, `/?__diag=1` reports the PHP version, resolved paths and
which project files actually made it into the Lambda bundle. Setting `APP_DEBUG=1`
in the environment variables prints the real PHP error instead of Vercel's
generic crash page.

`.vercelignore` keeps `.git`, `node_modules` and the Apache / `php -S` front
controllers out of the upload: the PHP runtime copies the whole project
directory into the Lambda, and AWS caps that at 250 MB uncompressed.

### What "demo" means here

Vercel is serverless: the deployment is read-only and each instance gets a
private temp directory that is wiped when it recycles. The site handles that by
treating the JSON files in the repo as read-only seed data and copying them into
the temp directory on first write (`includes/config.php`).

So everything **works** — you can place an order, see it in `/admin/orders.php`,
toggle ad slots, edit products — but that data disappears when the instance
recycles, and two visitors can land on different instances with different data.
A banner across the top of the site says so.

`ADMIN_PASSWORD` exists for the same reason: a password written to
`config/admin.json` would vanish with the instance and send you back to the setup
screen, so on Vercel the env var is the password and the setup screen is skipped.

### Making it a real store

Point it at a host with a normal writable disk — Hostinger, any cPanel shared
plan, Railway, Render, or a VPS. Nothing in the code changes: `IS_SERVERLESS` is
false there, writes go to the project directory, and orders, uploads and the
admin password all persist. That is what the reference site runs on.

## Layout

```
index.php               Home: hero, category tiles, featured grid, /page/N
router.php              Front controller for `php -S` (local only)
api/index.php           Front controller for Vercel serverless
vercel.json             Vercel runtime + route config
media.php               Serves runtime-uploaded images on serverless hosts
category/view.php       Category listing, /category/<CAT>/<page>
products/view.php       Product detail, /products/<id>/<slug>
cart/view.php           Cart
checkout/full-name.php  Delivery details form
confirm/thanks.php      Order confirmation, writes data/orders.json
pages/                  about-us, FAQ, contact-us, policies
admin/                  setup, login, dashboard, products, ads, orders, settings
includes/
  config.php            Config, storage paths, sessions, site() / e() / money()
  routes.php            URL map shared by router.php and api/index.php
  functions.php         Catalogue queries, slugs, pagination
  ads.php               Ad slot rendering and the single GPT block
  components.php        product_card(), pagination_nav(), breadcrumbs()
  header.php footer.php Page chrome
config/
  site.json             Site settings (admin/settings.php writes this)
  ads.json              Ad slots (admin/ads.php writes this)
  admin.json            Admin password hash — created on first run
products/products.json  Catalogue
data/orders.json        Orders
data/messages.json      Contact form submissions
assets/img/             Product images
```

## Ad slots

Every ad position is a key under `positions` in `config/ads.json`, rendered by
`ad_slot('<position>')` in a template. Positions currently wired up:

| Position | Where it renders |
|---|---|
| `global_top` | Above the header on every page — AdSense loader, top banner, interstitial, bottom anchor |
| `global_pixels` | Meta / Snapchat / GA4 tags (measurement, not ads) |
| `home_below_grid` | Home, under the featured grid |
| `category_below_grid` | Category page, under the grid |
| `product_above_add_to_cart` | Product page, directly above Add to Cart |
| `product_below_description` | Product page, under the description |
| `cart_above_checkout_btn` | Cart, above Proceed to Checkout |
| `cart_below_checkout_btn` | Cart, below the button |
| `checkout_above_form` | Checkout, above the form card |
| `checkout_above_button` | Checkout, above Place Order |
| `checkout_below_button` | Checkout, below the button |
| `thanks_below_continue` | Confirmation page |

Unit types: `adsense_loader`, `adsense`, `gpt`, `gpt_interstitial`, `gpt_anchor`,
`label`, `html`. Toggle any unit on or off from `/admin/ads.php` without editing
JSON. To add a position, add the key to `config/ads.json` and call
`ad_slot('your_key')` from the template you want it on.

All GPT slots on a page are collected while the page renders and defined in
**one** `googletag.cmd.push` from the footer, with `enableServices()` called
exactly once (`ads_flush_gpt()` in `includes/ads.php`). Defining slots in
separate pushes with repeated `enableServices()` calls — as the reference site
does — leaves the later slots unfilled.

## Products

Manage them at `/admin/products.php`: create, edit, delete, upload up to five
images per product, search by title or category. Blank lines in a description
become separate paragraphs on the product page.

Editing `products/products.json` by hand works too. Each record:

```json
{
  "id": 43,
  "title": "Floral Printed Designer Kurti",
  "description": "Paragraph one.\n\nParagraph two.",
  "price": 149,
  "category": "LOFT_STYLE",
  "img1": "/assets/img/....png",
  "img2": "", "img3": "", "img4": "", "img5": ""
}
```

`category` goes straight into the URL, so keep it to letters, digits and
underscores. It is displayed title-cased (`LOFT_STYLE` → "Loft Style").

## Orders

`confirm/thanks.php` validates the submission, appends it to `data/orders.json`
under an exclusive file lock, and burns the form token so a refresh cannot
record the order twice. View and export them at `/admin/orders.php`.

There is no payment gateway — checkout is Cash on Delivery. Wiring a gateway
means replacing the `POST` handler in `confirm/thanks.php`.

## Catalogue data

`products/products.json` and `assets/img/` are seeded with the reference site's
catalogue so the storefront is browsable immediately. Replace both with your own
products and photography before launch — those images belong to whoever shot
them.

## Ad policy

The ad placements here are dense by design, and several sit inside the checkout
flow. Google AdSense and Ad Manager both prohibit ads that are placed to induce
accidental clicks, and require that a site offering products for sale actually
fulfils orders. Placement that pushes the Add to Cart or Place Order button
below an ad is the kind of layout that draws a policy review. Worth reading
before you scale traffic to it:

- [AdSense Program policies](https://support.google.com/adsense/answer/48182)
- [Better ad placement practices](https://support.google.com/adsense/answer/1346295)
