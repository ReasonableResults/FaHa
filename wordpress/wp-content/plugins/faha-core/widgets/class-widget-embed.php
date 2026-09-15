<?php
namespace FaHa\Widgets;

defined( 'ABSPATH' ) || exit;

class Embed extends Base {
	public function get_name() { return 'faha_embed'; }
	public function get_title() { return __( 'FaHa Lazy embed (Zeffy, Google Form)', 'faha' ); }
	public function get_icon() { return 'eicon-form-horizontal'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Embed', 'faha' ) ) );
		$this->text_control( 'url', __( 'Embed URL', 'faha' ), '', __( 'The iframe URL from Zeffy or Google Forms. Loaded only after the visitor clicks.', 'faha' ) );
		$this->text_control( 'label', __( 'Button label', 'faha' ), __( 'Open the form', 'faha' ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		echo faha_render_embed( array( 'url' => $s['url'], 'label' => $s['label'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
