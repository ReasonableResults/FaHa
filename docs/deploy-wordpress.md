# Deploying the WordPress build

## 1. Host
Any PHP 8.0+ / MySQL host. For a small nonprofit with low traffic: a managed WordPress plan (Kinsta, WP Engine, SiteGround,
Cloudways) or a $5–10/month shared plan is enough. Point `fahahome.com` at it when ready.

## 2. Install
1. Install WordPress. Set Settings → General → Site title "Fredericksburg Area Homeschool Association", Tagline "Secular homeschool community, Fredericksburg VA", and the admin email to `helpdesk@fahahome.com` (the header and footer print the admin email).
2. Settings → Permalinks → "Post name".
3. Upload and activate the theme: zip `wordpress/wp-content/themes/faha` and upload under Appearance → Themes → Add New → Upload.
4. Upload and activate the plugin: zip `wordpress/wp-content/plugins/faha-core` and upload under Plugins → Add New → Upload.
5. Install Elementor (free). Elementor Pro is optional; it adds the theme-builder header/footer, which this theme already supports through registered locations.

## 3. Import content
1. Tools → Import → WordPress → install the importer → upload `wordpress/import/faha-content.xml`.
2. Assign posts to an existing user. Skip "Download and import file attachments" (there are none).
3. Settings → Reading → "A static page" → Homepage: **Fredericksburg Area Homeschool Association** (slug `home`).
4. Appearance → Menus: the import creates a **Primary** menu. Assign it to the "Primary menu" location. Create a small "Utility" menu (Check membership status → Zeffy payments link, Donate → Zeffy form) and a "Footer" menu (Join, Donate, Funding request, Contact us) and assign them.
5. Elementor → Tools → Regenerate CSS & Data, then Sync Library.

Every imported page opens in Elementor with the layout already in place (hero container + content container). Text is in
Text Editor widgets, section headings in Heading widgets, card grids in HTML widgets, forms and video in Shortcode widgets.
If Elementor is not installed, the same pages render from the HTML fallback stored in the post content.

## 4. Alternative: templates only
`wordpress/elementor-templates/*.json` can be imported one at a time under Templates → Saved Templates → Import Templates,
then inserted into any page. Use this when rebuilding a single page rather than the whole site.

## 5. Structured content (optional, recommended for the long run)
The plugin adds three post types under the dashboard: **Resources** (with Approach and Topic taxonomies and a price field),
**Board members** (role field, headshot as featured image), **Days and discounts**. The imported pages currently hold this content
as static cards inside HTML widgets. To make it editable as records instead of layout, add the entries as posts and replace the
HTML widget with the matching FaHa widget (Board grid, Resource cards) or shortcode. Both render the same markup.

## 6. Performance settings
- Elementor → Settings → Features: turn on "Optimized DOM Output", "Improved Asset Loading", "Improved CSS Loading", "Inline Font Icons".
- Elementor → Settings → Advanced: CSS Print Method "External file"; Google Fonts load: disable (theme uses system fonts).
- Add a page cache plugin (WP Super Cache or the host's cache). No CDN needed at this traffic level.
