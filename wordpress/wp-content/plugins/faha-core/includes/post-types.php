<?php
/**
 * Structured content. Three post types, two taxonomies.
 *
 * faha_resource  - one learning resource (tool, book, program). Taxonomies: faha_approach, faha_topic.
 *                  Meta: faha_url, faha_price, plus the five comparison fields for structured programs.
 * faha_board     - one board member. Meta: faha_role. Featured image = headshot.
 * faha_discount  - one local business discount or annual homeschool day. Meta: faha_url, faha_kind.
 */
defined( 'ABSPATH' ) || exit;

function faha_register_post_types() {
	register_post_type( 'faha_resource', array(
		'labels'       => array( 'name' => __( 'Resources', 'faha' ), 'singular_name' => __( 'Resource', 'faha' ), 'add_new_item' => __( 'Add resource', 'faha' ) ),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'resource' ),
		'menu_icon'    => 'dashicons-book-alt',
		'supports'     => array( 'title', 'editor', 'excerpt', 'custom-fields', 'page-attributes', 'thumbnail' ),
		'show_in_rest' => true,
	) );
	register_taxonomy( 'faha_approach', 'faha_resource', array(
		'labels'       => array( 'name' => __( 'Approach', 'faha' ), 'singular_name' => __( 'Approach', 'faha' ) ),
		'hierarchical' => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'approach' ),
	) );
	register_taxonomy( 'faha_topic', 'faha_resource', array(
		'labels'       => array( 'name' => __( 'Topics', 'faha' ), 'singular_name' => __( 'Topic', 'faha' ) ),
		'hierarchical' => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'topic' ),
	) );
	register_post_type( 'faha_board', array(
		'labels'       => array( 'name' => __( 'Board members', 'faha' ), 'singular_name' => __( 'Board member', 'faha' ), 'add_new_item' => __( 'Add board member', 'faha' ) ),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-groups',
		'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes' ),
		'show_in_rest' => true,
	) );
	register_post_type( 'faha_discount', array(
		'labels'       => array( 'name' => __( 'Days and discounts', 'faha' ), 'singular_name' => __( 'Day or discount', 'faha' ), 'add_new_item' => __( 'Add day or discount', 'faha' ) ),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-tickets-alt',
		'supports'     => array( 'title', 'editor', 'custom-fields', 'page-attributes' ),
		'show_in_rest' => true,
	) );

	foreach ( array( 'faha_url', 'faha_price', 'faha_grades', 'faha_accredited', 'faha_diploma', 'faha_live', 'faha_cost' ) as $key ) {
		register_post_meta( 'faha_resource', $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
	}
	register_post_meta( 'faha_board', 'faha_role', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
	register_post_meta( 'faha_discount', 'faha_url', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw' ) );
	register_post_meta( 'faha_discount', 'faha_kind', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field', 'default' => 'discount' ) );

	// Seed the approach terms the resource pages are organised by.
	if ( ! get_option( 'faha_terms_seeded' ) ) {
		foreach ( array( 'Unschooling', 'Eclectic', 'Structured' ) as $t ) {
			if ( ! term_exists( $t, 'faha_approach' ) ) {
				wp_insert_term( $t, 'faha_approach' );
			}
		}
		update_option( 'faha_terms_seeded', 1 );
	}
}
add_action( 'init', 'faha_register_post_types' );
