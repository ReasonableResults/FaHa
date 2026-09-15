<?php
/** Default footer. Replaced entirely when an Elementor Pro footer template is published. */
defined( 'ABSPATH' ) || exit;
?>
<footer class="site-footer"><div class="wrap">
	<div class="footer-grid">
		<div>
			<h2><?php esc_html_e( 'Get involved', 'faha' ); ?></h2>
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => '', 'depth' => 1, 'fallback_cb' => false ) ); ?>
		</div>
		<div>
			<h2><?php esc_html_e( 'Resources', 'faha' ); ?></h2>
			<?php
			$res = get_page_by_path( 'resources' );
			if ( $res ) {
				echo '<ul>';
				wp_list_pages( array( 'title_li' => '', 'child_of' => $res->ID, 'depth' => 1, 'sort_column' => 'menu_order' ) );
				echo '</ul>';
			}
			?>
		</div>
		<div>
			<h2><?php bloginfo( 'name' ); ?></h2>
			<address><?php echo wp_kses_post( nl2br( get_theme_mod( 'faha_address', "10408 Courthouse Rd\nPMB #42\nSpotsylvania, VA 22553" ) ) ); ?><br><a href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"><?php echo esc_html( get_option( 'admin_email' ) ); ?></a></address>
		</div>
	</div>
	<p class="fine"><?php echo esc_html( get_theme_mod( 'faha_fine_print', 'FaHa is a 501(c)(3) nonprofit. Secular, inclusive, member-run. What\'s shared in the group stays in the group.' ) ); ?></p>
</div></footer>
