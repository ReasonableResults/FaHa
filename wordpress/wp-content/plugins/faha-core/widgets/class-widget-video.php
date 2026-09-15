<?php
namespace FaHa\Widgets;

defined( 'ABSPATH' ) || exit;

class Video extends Base {
	public function get_name() { return 'faha_video'; }
	public function get_title() { return __( 'FaHa Video (click to play)', 'faha' ); }
	public function get_icon() { return 'eicon-youtube'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Video', 'faha' ) ) );
		$this->text_control( 'url', __( 'YouTube URL', 'faha' ), '', __( 'Any youtube.com or youtu.be link. The player is only created when a visitor clicks; it never autoplays.', 'faha' ) );
		$this->text_control( 'title', __( 'Title (read by screen readers and shown on the button)', 'faha' ), __( 'Video', 'faha' ) );
		$this->add_control( 'poster', array( 'label' => __( 'Poster image (optional)', 'faha' ), 'type' => \Elementor\Controls_Manager::MEDIA ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		echo faha_render_video( array( 'url' => $s['url'], 'title' => $s['title'], 'poster' => $s['poster']['url'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
