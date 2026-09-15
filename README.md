# FaHa website rebuild

Fredericksburg Area Homeschool Association (FaHa), a secular, member-run 501(c)(3) homeschool group.
This repository holds everything needed to move the site off Google Sites and onto WordPress + Elementor.

## What is in here

| Path | What it is |
|---|---|
| `content/source-archive/` | Verbatim capture of the old Google Site: 12 pages as Markdown, site structure, image manifest, reference-site notes |
| `content/pages/` | Curated copy for the new site, one Markdown file per page. **This is the single source of truth.** |
| `design/site.css` | The one stylesheet (about 11 KB, no web fonts, no framework) |
| `scripts/build.mjs` | Node script, no dependencies. Turns `content/pages` into everything below |
| `prototype/` | Static HTML build of the design. Deployed to Vercel for review. Committed so Vercel needs no install |
| `wordpress/wp-content/themes/faha/` | Lightweight Elementor-compatible theme |
| `wordpress/wp-content/plugins/faha-core/` | Post types (resources, board, discounts), shortcodes, four Elementor widgets |
| `wordpress/elementor-templates/` | One Elementor template JSON per page, importable via Templates → Saved Templates → Import |
| `wordpress/import/faha-content.xml` | WordPress import file: all pages with Elementor layout data, HTML fallback, and the primary menu |
| `docs/` | Deployment and editing guides |

## The important caveat: Vercel does not run WordPress

Vercel serves static files and serverless JavaScript. It cannot run PHP or MySQL, so it cannot host WordPress or Elementor.
The Vercel deployment is the **static prototype**: identical markup and CSS to the theme, so design, copy, information
architecture and performance can be reviewed live. The WordPress theme and plugin go to a PHP host
(see `docs/deploy-wordpress.md`).

## Design rules this build enforces

1. **Student centric, parent and educator led.** Copy is in the members' own voice. Structure leads with what a family needs to do next.
2. **Resources follow a logical flow.** Start here (Virginia basics) → choose an approach (unschooling / eclectic / structured) → local days and discounts. Every resource card has a price line.
3. **Fast on low-powered machines.** One CSS file, one 1 KB script, system fonts, no jQuery, no block-library CSS, lazy images, third-party embeds (Zeffy, Google Forms) load only on click. Mobile menu uses `<details>` so it works with scripts off.
4. **Video never autoplays.** The video component shows a still and creates the player on click. The theme also strips `autoplay` from any embed WordPress outputs.
5. **Lorem ipsum, not invented copy.** Paragraphs that start with `LOREM:` in the content files render with a visible "Placeholder copy" marker. Search the repo for `LOREM:` to find every gap.

## Working on it

```sh
node scripts/build.mjs        # regenerates prototype/, elementor templates, WXR, theme CSS
npx --yes serve prototype     # local preview at http://localhost:3000
npm run lint:php              # syntax-check the theme and plugin
```

Edit copy in `content/pages/*.md`, rebuild, commit. Pushes to `main` redeploy the Vercel preview automatically once the project is linked.

### Content file format

Front matter, then a small Markdown dialect. One source line is one paragraph.

```
---
title: Page title
slug: url-slug
parent: resources          # optional, nests the URL and the WordPress page
nav_title: Short label
menu_order: 3
template: hub              # optional: full-width grids instead of reading measure
hero_kicker: Small label above the heading
hero_heading: The H1
hero_lede: One or two sentences under the H1
cta_label: Button text      # optional, cta2_* for a second ghost button
cta_url: /join/
---
## Section heading
A paragraph. [A link](https://example.com) and **bold**.
LOREM: A placeholder paragraph.

:::paths                    # link cards
### [Card title](/where/)
One sentence.
:::

:::cards                    # resource cards
### [Resource](https://...)
Description.
Price: Free
:::

:::steps  /  :::board  /  :::faq  /  :::quote  /  :::notice
:::embed url=https://... label=Open the form
:::video url=https://youtube.com/watch?v=... title=What it is
```

## Images

The old site's photos (six board headshots, a logo, seven banners) are on Google's `lh3.googleusercontent.com` CDN, which
refused every automated download during this build (HTTP 403). URLs are in `content/source-archive/images/manifest.json`.
Run `content/source-archive/images/fetch-images.sh` from a normal desktop connection, or export from the Google Sites editor,
drop the files into that folder using the manifest ids as filenames, and rebuild. Until then the build uses an SVG placeholder.
