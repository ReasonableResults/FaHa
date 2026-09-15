<?php
/**
 * Default page template: hero from meta, sibling sub-navigation, then content in a reading measure.
 * Elementor-built pages set hide_title and render their own hero, so the hero is skipped for them.
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
	<div class="prose"><?php the_content(); ?></div>
</div>
<?php get_footer();
