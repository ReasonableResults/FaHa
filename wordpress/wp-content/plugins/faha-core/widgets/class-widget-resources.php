<?php
namespace FaHa\Widgets;

defined( 'ABSPATH' ) || exit;

class Resources extends Base {
	public function get_name() { return 'faha_resources'; }
	public function get_title() { return __( 'FaHa Resource cards', 'faha' ); }
	public function get_icon() { return 'eicon-posts-grid'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Filter', 'faha' ) ) );
		$this->text_control( 'approach', __( 'Approach slug(s)', 'faha' ), '', __( 'unschooling, eclectic or structured. Comma-separate for several.', 'faha' ) );
		$this->text_control( 'topic', __( 'Topic slug(s)', 'faha' ), '', __( 'e.g. coding, stem, writing-and-literature', 'faha' ) );
		$this->add_control( 'compare', array(
			'label'       => __( 'Comparison table layout', 'faha' ),
			'type'        => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'description' => __( 'Shows the five structured-program fields (grades, accreditation, diploma, live classes, cost) instead of the description.', 'faha' ),
		) );
		$this->end_controls_section();
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		echo faha_render_resources( array( 'approach' => $s['approach'], 'topic' => $s['topic'], 'compare' => $s['compare'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
