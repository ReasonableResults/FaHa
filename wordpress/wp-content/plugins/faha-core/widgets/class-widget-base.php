<?php
namespace FaHa\Widgets;

defined( 'ABSPATH' ) || exit;

/** Shared plumbing: every FaHa widget lives in the "FaHa" category and renders through a plugin function. */
abstract class Base extends \Elementor\Widget_Base {
	public function get_categories() {
		return array( 'faha' );
	}
	public function get_style_depends() {
		return array( 'faha' );
	}
	public function get_script_depends() {
		return array( 'faha' );
	}
	protected function text_control( string $id, string $label, string $default = '', string $description = '' ) {
		$this->add_control( $id, array(
			'label'       => $label,
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => $default,
			'description' => $description,
			'label_block' => true,
		) );
	}
}
