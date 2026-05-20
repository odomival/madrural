<?php

/**
 * @package Catch Plugins
 * @subpackage To Top
 * @since To Top 1.0
 */


//Custom control for icons
class To_Top_Custom_Icons extends WP_Customize_Control
{

	private $icon_options = array(
		'dashicons-arrow-up'      => 'Arrow Up',
		'dashicons-arrow-up-alt'  => 'Arrow Up Alt',
		'dashicons-arrow-up-alt2' => 'Arrow Up Alt 2',
	);

	public function render_content()
	{
		$output  = '';
		$output .= '<label>';
		$output .= '<span class="customize-control-title">' . esc_html($this->label) . '</span>';

		$output .= '<select ' . $this->get_link() . ' id="to-top-customizer-icon-type">';

		foreach ($this->icon_options as $key => $value) {
			$output .= '<option class="dashicons ' . esc_attr($key) . '" value="' . esc_attr($key) . '">';
			$output .= esc_html($value);
			$output .= '</option>';
		}

		$output .= '</select>';
		$output .= '</label>';

		$allowed_select_html = array(
			'label' => array(),
			'span'  => array(
				'class' => true,
			),
			'select' => array(
				'id'   => true,
				'name' => true,
				'class' => true,
				'data-customize-setting-link' => true,
			),
			'option' => array(
				'value'    => true,
				'class'    => true,
				'selected' => true,
			),
		);

		echo wp_kses($output, $allowed_select_html);
	}
}
