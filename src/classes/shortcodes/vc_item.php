<?php
$output = $el_class = $width = '';
extract(Composer::shortcode_atts([
	'el_class' => '',
], $atts));

$el_class = $this->getExtraClass($el_class);

echo '<div class="vc_items' . $el_class . '">' . __('Item', "js_composer") . '</div>';
