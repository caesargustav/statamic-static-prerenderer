# Statamic Static Prerenderer

> A Statamic addon that prerenders your site to static HTML files which can be fetched via an API.

## Features

This addon does:

- Prerender static page content
- Inserts Tailwind CSS styles
- Makes sure, styles are correctly isolated so content can be loaded into existing pages
- Invalidates prerendered HTML when asset data or files change
- Revalidates cached Glide responses so reuploads and crops appear immediately
- Uses Statamic focal points for responsive `object-cover` images
- Tracks collection queries during prerendering and refreshes only affected pages

## Entry cache behavior

The addon records collection-scoped entry queries while it prerenders a page. No template tags,
fields, naming rules, or project configuration are needed. Saving, unpublishing, or deleting an
entry clears every HTML and CSS query variant for that entry and for pages that queried its
collection. The default variant is rebuilt at once when the page remains public; other query
variants are rebuilt when they are requested again.

Direct entry references remain supported. If a page both references an entry and queries its
collection, the addon still renders that page only once. A successful rerender replaces the
page's old collection dependency set, so removed queries also remove their old graph edges.

Existing cache files from an older addon version have no dependency record. The next entry change
or request rebuilds each cached page once instead of trusting the old file and creates its
dependency record. Entry queries outside an active prerender, including Control Panel queries, are
not tracked.

## Image cache behavior

The addon clears both the affected asset's server-side Glide variants and its full prerender
cache whenever an asset is saved, reuploaded, or deleted. This happens synchronously, so a
browser revalidation cannot receive a queued, stale Glide variant.
It also changes dynamic Glide responses from a one-year browser cache to
`public, no-cache, must-revalidate`. Browsers can keep the image locally, but must validate it
before reuse. Unchanged images return `304 Not Modified`; changed images return the new content.

Both behaviors can be disabled in `config/statamic-static-prerenderer.php`:

```php
return [
    'invalidate_on_asset_change' => true,
    'revalidate_glide_responses' => true,
];
```

The default image templates render responsive Glide variants. The image-with-text block creates
a fixed crop frame and applies the asset's `focus_css` value as `object-position`. This lets the
focal point control the visible part of the image without cropping an already cropped source a
second time. The standalone image block keeps the source aspect ratio and therefore needs no
focal crop.

## How to Install

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

``` bash
composer require caesargustav/statamic-static-prerenderer
```
