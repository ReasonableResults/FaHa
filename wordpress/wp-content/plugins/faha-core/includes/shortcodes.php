<?php
/**
 * Shortcodes. Usable in Elementor's Shortcode widget, the classic editor, or a Gutenberg shortcode block.
 *
 * [faha_board]
 * [faha_resources approach="eclectic" topic="coding" compare="no"]
 * [faha_discounts kind="discount|day"]
 * [faha_video url="https://www.youtube.com/watch?v=..." title="..."]
 * [faha_embed url="https://www.zeffy.com/embed/donation-form/..." label="Open the donation form"]
 */
defined( 'ABSPATH' ) || exit;

add_shortcode( 'faha_board', fn( $a ) => faha_render_board( (array) $a ) );
add_shortcode( 'faha_resources', fn( $a ) => faha_render_resources( (array) $a ) );
add_shortcode( 'faha_discounts', fn( $a ) => faha_render_discounts( (array) $a ) );
add_shortcode( 'faha_video', fn( $a ) => faha_render_video( (array) $a ) );
add_shortcode( 'faha_embed', fn( $a ) => faha_render_embed( (array) $a ) );
