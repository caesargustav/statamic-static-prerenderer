# Statamic + Static Prerenderer Setup Guide

Step-by-step guide for setting up a new Statamic project with the `caesargustav/statamic-static-prerenderer` plugin.

---

## 1. Create the Statamic project

```bash
composer create-project statamic/statamic your-project-name --prefer-dist
cd your-project-name
```

## 2. Install the static prerenderer plugin

The plugin is not on Packagist, so add the VCS repository first.

Add to `composer.json` (before the closing `}`):

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/caesargustav/statamic-static-prerenderer"
    }
]
```

Then require it:

```bash
composer require caesargustav/statamic-static-prerenderer:@dev
```

## 3. Publish the plugin views

```bash
php artisan vendor:publish --provider="Caesargustav\StaticPrerenderer\ServiceProvider"
```

This copies views to `resources/views/vendor/statamic-static-prerenderer/`.

## 4. Set up the default template

**`resources/views/default.antlers.html`** — delegate to the headless partial:

```antlers
{{ partial src="statamic-static-prerenderer::headless" }}
```

Don't render `{{ content }}` or loop through `{{ pagebuilder }}` here directly.

## 5. Set up the layout with Tailwind CDN

**`resources/views/layout.antlers.html`** — load Tailwind CDN with the `hls-` prefix:

```html
<!doctype html>
<html lang="{{ site:short_locale }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ title ?? site:name }}</title>
    {{ vite src="resources/js/site.js|resources/css/site.css" }}
</head>
<body>
<script src="https://cdn.tailwindcss.com?plugins=typography,container-queries"></script>
<script>
    tailwind.config = {
        prefix: 'hls-',
    }
</script>

{{ template_content }}

</body>
</html>
```

The Tailwind CDN is needed because the plugin only generates CSS at **prerender time** (via a standalone Tailwind binary). For live preview and direct page views, the CDN provides the styles at runtime.

You can extend `tailwind.config` with custom fonts, colors, and screens as needed:

```js
tailwind.config = {
    theme: {
        screens: { xs: '480px', sm: '640px', md: '992px', lg: '1200px', xl: '1386px' },
        fontFamily: { sans: ['Your Font', 'sans-serif'], serif: ['Your Serif Font'] },
        extend: {
            colors: { primary: '#yourcolor', 'primary-dark': '#yourdarker', black: '#22292f' },
        },
    },
    prefix: 'hls-',
}
```

## 6. Create the pages blueprint with pagebuilder

Create `resources/blueprints/collections/pages/page.yaml`:

```yaml
title: Page
tabs:
  main:
    display: Main
    sections:
      -
        fields:
          -
            handle: title
            field:
              type: text
              required: true
              display: Title
              localizable: true
          -
            import: 'statamic-static-prerenderer::pagebuilder'
  sidebar:
    display: Sidebar
    sections:
      -
        fields:
          -
            handle: slug
            field:
              type: slug
              localizable: true
          -
            handle: template
            field:
              type: template
              display: Template
              localizable: true
```

The `import: 'statamic-static-prerenderer::pagebuilder'` line pulls in the full pagebuilder replicator with all block types.

## 7. Link to Herd (optional)

```bash
cd your-project-name
herd link --secure
```

Site available at `https://your-project-name.test`

## 8. Create a user

```bash
php artisan statamic:make:user your@email.com --super --password=password
```

CP available at `https://your-project-name.test/cp`

---

## Template rendering chain

```
layout.antlers.html          (HTML shell + Tailwind CDN)
  └─ {{ template_content }}
      └─ default.antlers.html    (delegates to headless)
          └─ headless.antlers.html   (loops pagebuilder blocks)
              └─ setup.antlers.html      (CSS reset + base styles via slot)
                  └─ pagebuilder/{type}.antlers.html  (individual blocks)
```

## Available pagebuilder blocks (default)

| Block | Handle | Description |
|-------|--------|-------------|
| Abstand | `spacing` | Vertical spacer (4 sizes) |
| Bildelement | `image` | Image with container settings |
| Bildelement mit Text | `image_with_text` | Image + rich text side by side |
| WYSIWYG | `wysiwyg` | Rich text editor (Bard) |
| Infobox | `infobox` | Highlighted info box |

All blocks support the shared **container** fieldset (width, centering, padding).

## Customizing block templates

Override any block by editing files in `resources/views/vendor/statamic-static-prerenderer/pagebuilder/`. All classes use the `hls-` Tailwind prefix.

## Using the setup partial standalone

The `setup` partial provides CSS reset and base styling via a slot pattern. You can use it in custom templates (e.g. tag archive pages):

```antlers
{{ partial:statamic-static-prerenderer::setup }}
    <h1 class="hls-text-2xl">{{ title }}</h1>
    <!-- your content -->
{{ /partial:statamic-static-prerenderer::setup }}
```

## Adding custom fieldsets

To override the plugin's fieldsets locally, copy them to:

```
resources/fieldsets/vendor/statamic-static-prerenderer/pagebuilder.yaml
resources/fieldsets/vendor/statamic-static-prerenderer/container.yaml
```

## Notes

- The plugin auto-injects SEO fields ("Externe Daten" tab) into all entry/term blueprints
- API endpoints: `GET /api/static-prerenderer/` (list) and `GET /api/static-prerenderer/{type}/{id}` (detail)
- The Tailwind binary is downloaded automatically during `composer install` — used only for static prerendering, not for live preview
