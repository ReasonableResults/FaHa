<?php
/**
 * Plugin Name: FaHa Core
 * Plugin URI:  https://github.com/ReasonableResults/FaHa
 * Description: Content types, shortcodes and Elementor widgets for the Fredericksburg Area Homeschool Association site. Keeps structured content (resources, board, discounts) out of page layouts so it survives redesigns.
 * Version:     0.1.0
 * Requires PHP: 8.0
 * Author:      FaHa
 * License:     GPL-2.0-or-later
 * Text Domain: faha
 */
defined( 'ABSPATH' ) || exit;

define( 'FAHA_CORE_VERSION', '0.1.0' );
define( 'FAHA_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once FAHA_CORE_DIR . 'includes/post-types.php';
require_once FAHA_CORE_DIR . 'includes/render.php';
require_once FAHA_CORE_DIR . 'includes/shortcodes.php';

/* Elementor widgets: registered only when Elementor is active. Each wraps a shortcode renderer, so the
   same markup appears whether an editor uses the widget, the shortcode, or the classic editor. */
add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	require_once FAHA_CORE_DIR . 'widgets/class-widget-base.php';
	require_once FAHA_CORE_DIR . 'widgets/class-widget-board.php';
	require_once FAHA_CORE_DIR . 'widgets/class-widget-resources.php';
	require_once FAHA_CORE_DIR . 'widgets/class-widget-video.php';
	require_once FAHA_CORE_DIR . 'widgets/class-widget-embed.php';
	$widgets_manager->register( new \FaHa\Widgets\Board() );
	$widgets_manager->register( new \FaHa\Widgets\Resources() );
	$widgets_manager->register( new \FaHa\Widgets\Video() );
	$widgets_manager->register( new \FaHa\Widgets\Embed() );
} );
add_action( 'elementor/elements/categories_registered', function ( $elements_manager ) {
	$elements_manager->add_category( 'faha', array( 'title' => __( 'FaHa', 'faha' ), 'icon' => 'fa fa-graduation-cap' ) );
} );

register_activation_hook( __FILE__, function () {
	faha_register_post_types();
	flush_rewrite_rules();
} );
