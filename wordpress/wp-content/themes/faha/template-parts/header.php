<?php
/** Default header. Replaced entirely when an Elementor Pro header template is published. */
defined( 'ABSPATH' ) || exit;
?>
<header class="site-header">
	<div class="utility"><div class="wrap">
		<?php
		wp_nav_menu( array( 'theme_location' => 'utility', 'container' => false, 'items_wrap' => '%3$s', 'depth' => 1, 'fallback_cb' => false ) );
		echo '<a href="mailto:' . esc_attr( get_option( 'admin_email' ) ) . '">' . esc_html( get_option( 'admin_email' ) ) . '</a>';
		?>
	</div></div>
	<div class="wrap masthead">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php
			if ( has_custom_logo() ) {
				echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), array( 44, 44 ), false, array( 'class' => 'brand-mark', 'alt' => '' ) );
			} else {
				echo '<svg class="brand-mark" viewBox="0 0 44 44" aria-hidden="true"><rect width="44" height="44" rx="6" fill="#0f4c5c"/><path d="M10 30 22 12l12 18H10z" fill="#fff"/><circle cx="22" cy="27" r="3" fill="#c2410c"/></svg>';
			}
			?>
			<span><?php bloginfo( 'name' ); ?><small><?php bloginfo( 'description' ); ?></small></span>
		</a>
		<div class="nav-desktop"><?php faha_primary_nav( __( 'Primary', 'faha' ) ); ?></div>
		<details class="nav-toggle"><summary><?php esc_html_e( 'Menu', 'faha' ); ?></summary><?php faha_primary_nav( __( 'Primary', 'faha' ) ); ?></details>
	</div>
</header>
