<?php
/**
 * Small template helpers. Kept procedural so an Elementor-first site owner can read them.
 */
defined( 'ABSPATH' ) || exit;

/** Fallback primary menu (pages, top level) when no menu is assigned. */
function faha_primary_menu_fallback() {
	echo '<ul class="nav">';
	wp_list_pages( array( 'title_li' => '', 'depth' => 1, 'sort_column' => 'menu_order', 'exclude' => get_option( 'page_on_front' ) ) );
	echo '</ul>';
}

/** Primary navigation markup, used twice in header.php (desktop list and mobile <details>). */
function faha_primary_nav( $label ) {
	echo '<nav aria-label="' . esc_attr( $label ) . '">';
	wp_nav_menu( array(
		'theme_location' => 'primary',
		'container'      => false,
		'menu_class'     => 'nav',
		'depth'          => 1,
		'fallback_cb'    => 'faha_primary_menu_fallback',
	) );
	echo '</nav>';
}

/**
 * Page hero. Reads the same meta keys the WXR import sets (faha_hero_kicker, faha_hero_lede).
 * Pages built fully in Elementor carry their own hero and set hide_title, so this only prints for
 * pages rendered through the classic templates.
 */
function faha_hero() {
	if ( ! is_singular() ) {
		return;
	}
	$id     = get_the_ID();
	$kicker = get_post_meta( $id, 'faha_hero_kicker', true );
	$lede   = get_post_meta( $id, 'faha_hero_lede', true );
	$home   = is_front_page();
	echo '<section class="hero' . ( $home ? ' hero--home' : '' ) . '"><div class="wrap">';
	if ( $kicker ) {
		echo '<p class="kicker">' . esc_html( $kicker ) . '</p>';
	}
	echo '<h1>' . esc_html( get_the_title() ) . '</h1>';
	if ( $lede ) {
		echo '<p class="lede">' . esc_html( $lede ) . '</p>';
	}
	echo '</div></section>';
}

/** Sub-navigation for a page's siblings, mirrors the prototype's .toc block. */
function faha_subnav() {
	if ( ! is_page() ) {
		return;
	}
	$post   = get_post();
	$parent = $post->post_parent ? $post->post_parent : ( get_pages( array( 'parent' => $post->ID, 'number' => 1 ) ) ? $post->ID : 0 );
	if ( ! $parent ) {
		return;
	}
	$kids = get_pages( array( 'parent' => $parent, 'sort_column' => 'menu_order' ) );
	if ( ! $kids ) {
		return;
	}
	echo '<nav class="toc" aria-label="' . esc_attr( get_the_title( $parent ) ) . '"><strong><a href="' . esc_url( get_permalink( $parent ) ) . '">' . esc_html( get_the_title( $parent ) ) . '</a></strong><ol>';
	foreach ( $kids as $k ) {
		$cur = $k->ID === $post->ID ? ' aria-current="page"' : '';
		echo '<li><a href="' . esc_url( get_permalink( $k ) ) . '"' . $cur . '>' . esc_html( $k->post_title ) . '</a></li>';
	}
	echo '</ol></nav>';
}
