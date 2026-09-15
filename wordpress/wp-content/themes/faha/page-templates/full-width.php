<?php
/**
 * Template Name: Full width (hubs, home)
 * Template Post Type: page
 *
 * Same as page.php without the reading-measure wrapper, so card grids can use the full container.
 */
get_header();
the_post();
$built_with_elementor = defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->documents->get( get_the_ID() )->is_built_with_elementor();
if ( ! $built_with_elementor ) {
	faha_hero();
}
?>
<div class="wrap content">
	<?php faha_subnav(); ?>
	<?php the_content(); ?>
</div>
<?php get_footer();
