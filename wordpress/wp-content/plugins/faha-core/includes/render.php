<?php
/**
 * Renderers shared by shortcodes and Elementor widgets. Markup mirrors the static prototype so the
 * one stylesheet covers both.
 */
defined( 'ABSPATH' ) || exit;

function faha_render_board( array $args = array() ) : string {
	$q = new WP_Query( array( 'post_type' => 'faha_board', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true ) );
	if ( ! $q->have_posts() ) {
		return '<p class="is-placeholder">' . esc_html__( 'No board members added yet. Add them under Board members in the dashboard.', 'faha' ) . '</p>';
	}
	$placeholder = get_template_directory_uri() . '/assets/placeholder-person.svg';
	$out = '<div class="board">';
	foreach ( $q->posts as $p ) {
		$img = has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 'medium', array( 'loading' => 'lazy' ) ) : '<img class="placeholder-img" src="' . esc_url( $placeholder ) . '" alt="" width="600" height="600" loading="lazy">';
		$role = get_post_meta( $p->ID, 'faha_role', true );
		$out .= '<article class="person">' . $img . '<h3>' . esc_html( $p->post_title ) . '</h3>';
		if ( $role ) {
			$out .= '<p class="role">' . esc_html( $role ) . '</p>';
		}
		$out .= wp_kses_post( wpautop( $p->post_content ) ) . '</article>';
	}
	return $out . '</div>';
}

function faha_render_resources( array $args = array() ) : string {
	$args = wp_parse_args( $args, array( 'approach' => '', 'topic' => '', 'compare' => '' ) );
	$tax  = array();
	if ( $args['approach'] ) {
		$tax[] = array( 'taxonomy' => 'faha_approach', 'field' => 'slug', 'terms' => array_map( 'sanitize_title', explode( ',', $args['approach'] ) ) );
	}
	if ( $args['topic'] ) {
		$tax[] = array( 'taxonomy' => 'faha_topic', 'field' => 'slug', 'terms' => array_map( 'sanitize_title', explode( ',', $args['topic'] ) ) );
	}
	$q = new WP_Query( array( 'post_type' => 'faha_resource', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'tax_query' => $tax, 'no_found_rows' => true ) );
	if ( ! $q->have_posts() ) {
		return '<p class="is-placeholder">' . esc_html__( 'No resources match yet. Add some under Resources in the dashboard.', 'faha' ) . '</p>';
	}
	$out = '<div class="grid cards">';
	foreach ( $q->posts as $p ) {
		$url   = get_post_meta( $p->ID, 'faha_url', true );
		$title = $url ? '<a href="' . esc_url( $url ) . '" rel="noopener">' . esc_html( $p->post_title ) . '</a>' : esc_html( $p->post_title );
		$out  .= '<article class="card"><h3>' . $title . '</h3>';
		if ( 'yes' === $args['compare'] ) {
			$out .= '<dl>';
			foreach ( array( 'faha_grades' => 'Which grades?', 'faha_accredited' => 'Accredited?', 'faha_diploma' => 'Offers diploma?', 'faha_live' => 'Live classes?', 'faha_cost' => 'Cost?' ) as $k => $label ) {
				$v = get_post_meta( $p->ID, $k, true );
				if ( $v ) {
					$out .= '<dt>' . esc_html( $label ) . '</dt><dd>' . wp_kses_post( $v ) . '</dd>';
				}
			}
			$out .= '</dl>';
		} else {
			$out .= wp_kses_post( wpautop( $p->post_excerpt ?: $p->post_content ) );
			$price = get_post_meta( $p->ID, 'faha_price', true );
			if ( $price ) {
				$out .= '<p class="meta">' . esc_html( $price ) . '</p>';
			}
		}
		$out .= '</article>';
	}
	return $out . '</div>';
}

function faha_render_discounts( array $args = array() ) : string {
	$args = wp_parse_args( $args, array( 'kind' => 'discount' ) );
	$q    = new WP_Query( array( 'post_type' => 'faha_discount', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'meta_key' => 'faha_kind', 'meta_value' => sanitize_key( $args['kind'] ), 'no_found_rows' => true ) );
	if ( ! $q->have_posts() ) {
		return '<p class="is-placeholder">' . esc_html__( 'Nothing listed yet. Add entries under Days and discounts in the dashboard.', 'faha' ) . '</p>';
	}
	$out = '<div class="grid cards">';
	foreach ( $q->posts as $p ) {
		$url   = get_post_meta( $p->ID, 'faha_url', true );
		$title = $url ? '<a href="' . esc_url( $url ) . '" rel="noopener">' . esc_html( $p->post_title ) . '</a>' : esc_html( $p->post_title );
		$out  .= '<article class="card"><h3>' . $title . '</h3>' . wp_kses_post( wpautop( $p->post_content ) ) . '</article>';
	}
	return $out . '</div>';
}

/** YouTube/Vimeo id from any common URL form. */
function faha_video_id( string $url ) : string {
	if ( preg_match( '~(?:v=|youtu\.be/|embed/|shorts/)([\w-]{6,})~', $url, $m ) ) {
		return $m[1];
	}
	return $url;
}

/**
 * Click-to-play video. Loads a still image only; the player iframe is created on click, never
 * autoplays and uses the privacy-enhanced YouTube host.
 */
function faha_render_video( array $args = array() ) : string {
	$args = wp_parse_args( $args, array( 'url' => '', 'title' => __( 'Video', 'faha' ), 'poster' => '' ) );
	if ( ! $args['url'] ) {
		return '';
	}
	$id     = faha_video_id( $args['url'] );
	$poster = $args['poster'] ?: 'https://i.ytimg.com/vi/' . rawurlencode( $id ) . '/hqdefault.jpg';
	return '<div class="video" data-video="' . esc_attr( $id ) . '"><img src="' . esc_url( $poster ) . '" alt="" loading="lazy" width="480" height="360"><button type="button" data-video-play aria-label="' . esc_attr( sprintf( __( 'Play video: %s', 'faha' ), $args['title'] ) ) . '"><span>&#9654; ' . esc_html__( 'Play:', 'faha' ) . ' ' . esc_html( $args['title'] ) . '</span></button></div>';
}

/** Lazy third-party embed (Zeffy, Google Forms). Nothing loads until the visitor asks. */
function faha_render_embed( array $args = array() ) : string {
	$args = wp_parse_args( $args, array( 'url' => '', 'label' => __( 'Open the form', 'faha' ) ) );
	if ( ! $args['url'] ) {
		return '<div class="embed is-placeholder"><p class="embed-open">' . esc_html( $args['label'] ) . '</p></div>';
	}
	$direct = str_replace( '/embed/', '/en-US/', $args['url'] );
	return '<div class="embed" data-embed="' . esc_url( $args['url'] ) . '"><p class="embed-open"><a class="btn" href="' . esc_url( $direct ) . '" rel="noopener" data-embed-toggle>' . esc_html( $args['label'] ) . '</a></p><p class="embed-note">' . esc_html__( 'Opens here if scripts are on, otherwise in a new tab. Nothing loads until you ask for it.', 'faha' ) . '</p></div>';
}
