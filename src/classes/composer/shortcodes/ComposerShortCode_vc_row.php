<?php

class ComposerShortCode_vc_row extends ComposerShortCode {

	protected $predefined_atts = [
		'el_class' => '',
	];
    
    protected function content($atts, $content = null) {

		$prefix = '';
		return $prefix . $this->loadTemplate($atts, $content);
	}

	
	/* This returs block controls
---------------------------------------------------------- */
	public function getColumnControls($controls, $extended_css = '') {

		global $row_layouts;
		$controls_start = '<div class="controls controls_row vc_clearfix">';
		$controls_end = '</div>';
		$vc_manager = ephenyx_manager();
		$controls_layout = '<span class="vc_row_layouts vc_control">';

		foreach ($row_layouts as $layout) {
			$controls_layout .= '<a class="vc_control-set-column set_columns ' . $layout['icon_class'] . '" data-cells="' . $layout['cells'] . '" data-cells-mask="' . $layout['mask'] . '" title="' . $layout['title'] . '"></a> ';
		}

		$controls_layout .= '<br/><a class="vc_control-set-column set_columns custom_columns" data-cells="custom" data-cells-mask="custom" title="' . $this->l('Custom layout') . '">' . $this->l('Custom layout') . '</a> ';
		$controls_layout .= '</span>';

		$controls_move = ' <a class="vc_control column_move" href="javascript:void(0)" title="' . $this->l('Drag row to reorder') . '"><i class="fa-duotone fa-arrows-up-down-left-right"></i></a>';
		$controls_add = ' <a class="vc_control column_add" href="javascript:void(0)" title="' . $this->l('Add column') . '"><i class="fa-duotone fa-plus"></i></a>';
		$controls_delete = '<a class="vc_control column_delete" href="javascript:void(0)" title="' . $this->l('Delete this row') . '"><i class="fa-duotone fa-trash"></i></a>';
		$controls_edit = ' <a class="vc_control column_edit" href="javascript:void(0)" title="' . $this->l('Edit this row') . '"><i class="fa-duotone fa-pen-to-square"></i></a>';
		$controls_clone = ' <a class="vc_control column_clone" href="javascript:void(0)" title="' . $this->l('Clone this row') . '"><i class="fa-duotone fa-copy"></i></a>';
		$controls_toggle = ' <a class="vc_control column_toggle" href="javascript:void(0)" title="' . $this->l('Toggle row') . '"><i class="vc_icon"></i></a>';

		$row_edit_clone_delete = '<span class="vc_row_edit_clone_delete">';
		$row_edit_clone_delete .= $controls_delete . $controls_clone . $controls_edit . $controls_toggle;

		$column_controls_full = $controls_start . $controls_move . $controls_layout . $controls_add . $row_edit_clone_delete . $controls_end;

		return $column_controls_full;
	}

	public function contentAdmin($atts, $content = null) {

		$width = $el_class = '';
		$atts = Composer::shortcode_atts($this->predefined_atts, $atts);
		extract($atts);

		$output = '';

		$column_controls = $this->getColumnControls($this->settings('controls'));

		if (is_string($width)) {
			$width = [$width];
		}

		for ($i = 0; $i < count($width); $i++) {
			$output .= '<div' . $this->customAdminBockParams() . ' data-element_type="' . $this->settings["base"] . '" class="wpb_' . $this->settings['base'] . ' wpb_sortable">';
			$output .= str_replace("%column_size%", 1, $column_controls);
			$output .= '<div class="wpb_element_wrapper">';
			$output .= '<div class="vc_row vc_row-fluid wpb_row_container vc_container_for_children">';

			if ($content == '' && !empty($this->settings["default_content_in_template"])) {
				$output .= Composer::do_shortcode(Composer::shortcode_unautop($this->settings["default_content_in_template"]));
			} else {
				$output .= Composer::do_shortcode(Composer::shortcode_unautop($content));
			}

			$output .= '</div>';

			if (isset($this->settings['params'])) {
				$inner = '';

				foreach ($this->settings['params'] as $param) {
					$param_value = isset($atts[$param['param_name']]) ? $atts[$param['param_name']] : '';

					if (is_array($param_value)) {
						reset($param_value);
						$first_key = key($param_value);
						$param_value = $param_value[$first_key];
					}

					$inner .= $this->singleParamHtmlHolder($param, $param_value);
				}

				$output .= $inner;
			}

			$output .= '</div>';
			$output .= '</div>';
		}

		return $output;
	}

	public function customAdminBockParams() {

		return '';
	}

}
