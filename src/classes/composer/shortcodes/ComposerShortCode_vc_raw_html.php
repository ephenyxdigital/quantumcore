<?php


namespace EphenyxDigital\QuantumCore;
class ComposerShortCode_vc_raw_html extends ComposerShortCode {

	public function singleParamHtmlHolder($param, $value) {

		$output = '';

		// Defensive: ensure $value is a string to avoid "Array to string conversion" warnings.
		if (is_array($value)) {
			$flat = [];
			array_walk_recursive($value, function ($item) use (&$flat) {
				if (is_scalar($item) || is_null($item)) {
					$flat[] = (string) $item;
				}
			});
			$value = implode(',', $flat);
		} else if (is_object($value)) {
			$value = method_exists($value, '__toString') ? (string) $value : '';
		} else if (is_null($value)) {
			$value = '';
		} else {
			$value = (string) $value;
		}

		$old_names = ['yellow_message', 'blue_message', 'green_message', 'button_green', 'button_grey', 'button_yellow', 'button_blue', 'button_red', 'button_orange'];
		$new_names = ['alert-block', 'alert-info', 'alert-success', 'btn-success', 'btn', 'btn-info', 'btn-primary', 'btn-danger', 'btn-warning'];
		$value = str_ireplace($old_names, $new_names, $value);
		
		$param_name = isset($param['param_name']) ? $param['param_name'] : '';
		$type = isset($param['type']) ? $param['type'] : '';
		$class = isset($param['class']) ? $param['class'] : '';

		if (isset($param['holder']) == true && $param['holder'] != 'hidden') {

			if ($param['type'] == 'textarea_raw_html') {
				$output .= '<' . $param['holder'] . ' class="wpb_vc_param_value ' . $param_name . ' ' . $type . ' ' . $class . '" name="' . $param_name . '">' . htmlentities(rawurldecode(base64_decode(strip_tags($value))), ENT_COMPAT, 'UTF-8') . '</' . $param['holder'] . '><input type="hidden" name="' . $param_name . '_code" class="' . $param_name . '_code" value="' . strip_tags($value) . '" />';
			} else {
				$output .= '<' . $param['holder'] . ' class="wpb_vc_param_value ' . $param_name . ' ' . $type . ' ' . $class . '" name="' . $param_name . '">' . $value . '</' . $param['holder'] . '>';
			}

		}

		return $output;
	}

}
