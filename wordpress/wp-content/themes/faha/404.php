<?php
get_header();
?>
<section class="hero"><div class="wrap"><h1><?php esc_html_e( 'Page not found', 'faha' ); ?></h1><p class="lede"><?php esc_html_e( 'The address may have changed in the rebuild. Try the menu above.', 'faha' ); ?></p></div></section>
<div class="wrap content prose"><?php get_search_form(); ?></div>
<?php get_footer();
