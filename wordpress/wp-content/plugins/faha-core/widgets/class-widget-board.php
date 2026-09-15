<?php
namespace FaHa\Widgets;

defined( 'ABSPATH' ) || exit;

class Board extends Base {
	public function get_name() { return 'faha_board'; }
	public function get_title() { return __( 'FaHa Board grid', 'faha' ); }
	public function get_icon() { return 'eicon-person'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Board members', 'faha' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => __( 'Members, roles and photos are edited under Dashboard → Board members. Order follows the Order field.', 'faha' ) ) );
		$this->end_controls_section();
	}
	protected function render() {
		echo faha_render_board(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
