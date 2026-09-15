<?php
/** Generic fallback (blog index, archives). FaHa is a pages-first site, so this stays plain. */
get_header();
?>
<section class="hero"><div class="wrap"><h1><?php echo is_home() ? esc_html__( 'Updates', 'faha' ) : wp_kses_post( get_the_archive_title() ); ?></h1></div></section>
<div class="wrap content prose">
<?php
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		echo '<article><h2><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';
		the_excerpt();
		echo '</article>';
	}
	the_posts_pagination();
} else {
	echo '<p>' . esc_html__( 'Nothing here yet.', 'faha' ) . '</p>';
}
?>
</div>
<?php get_footer();
