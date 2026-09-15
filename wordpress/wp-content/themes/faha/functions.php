<?php
/**
 * FaHa theme bootstrap.
 *
 * Design rules encoded here:
 *  - fast on low-powered machines: no jQuery on the front end, no emoji script, no oEmbed discovery, no block-library CSS
 *  - nothing autoplays: see faha_strip_autoplay()
 *  - Elementor edits everything: theme registers Elementor locations and stays out of the way
 */
defined( 'ABSPATH' ) || exit;

define( 'FAHA_VERSION', '0.1.0' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'height' => 88, 'width' => 88, 'flex-height' => true, 'flex-width' => true ) );
	register_nav_menus( array(
		'primary' => __( 'Primary menu', 'faha' ),
		'utility' => __( 'Utility strip (top right)', 'faha' ),
		'footer'  => __( 'Footer: Get involved', 'faha' ),
	) );
	remove_theme_support( 'widgets-block-editor' );
} );

/* Elementor: register header/footer locations so Elementor Pro theme builder can take over. */
add_action( 'elementor/theme/register_locations', function ( $manager ) {
	$manager->register_all_core_location();
} );
/* Elementor free: let it know the theme's content width and that we handle page titles ourselves. */
add_action( 'after_setup_theme', function () {
	$GLOBALS['content_width'] = 1152;
} );
add_filter( 'elementor/page_templates/canvas/body_class', fn( $c ) => $c );

/* Assets */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'faha', get_template_directory_uri() . '/assets/site.css', array(), FAHA_VERSION );
	wp_enqueue_script( 'faha', get_template_directory_uri() . '/assets/site.js', array(), FAHA_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	// Drop things a content site does not need on the front end.
	if ( ! is_admin() && ! ( defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'global-styles' );
	}
}, 20 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
add_filter( 'the_generator', '__return_empty_string' );

/* Never autoplay. Strips autoplay from any embed or video markup that passes through the_content or oEmbed. */
function faha_strip_autoplay( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, 'autoplay' ) ) {
		return $html;
	}
	$html = preg_replace( '/([?&])autoplay=1/i', '$1autoplay=0', $html );
	$html = preg_replace( '/\s(autoplay|allow="[^"]*autoplay[^"]*")/i', ' ', $html );
	return $html;
}
add_filter( 'the_content', 'faha_strip_autoplay', 99 );
add_filter( 'embed_oembed_html', 'faha_strip_autoplay', 99 );
add_filter( 'oembed_result', 'faha_strip_autoplay', 99 );
add_filter( 'elementor/frontend/the_content', 'faha_strip_autoplay', 99 );

/* Lazy-load every iframe WordPress did not already mark. */
add_filter( 'wp_lazy_loading_enabled', '__return_true' );
add_filter( 'the_content', function ( $html ) {
	return preg_replace( '/<iframe(?![^>]*loading=)/i', '<iframe loading="lazy"', $html );
}, 100 );

/* Body classes used by the stylesheet. */
add_filter( 'body_class', function ( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'is-home';
	}
	return $classes;
} );

require_once get_template_directory() . '/inc/template-tags.php';
