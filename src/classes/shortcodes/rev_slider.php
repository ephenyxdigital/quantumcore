<?php
extract(Composer::shortcode_atts([
    'alias' => '', //standard, button_count, box_count
    'el_class'    => '',
    'display_mobile' => 'not_display_mobile',
    'display_tablet' => 'not_display_tablet',
], $atts));
$output = '';
$context = Context::getContext();
if($display_mobile == 'not_display_mobile' && $context->isMobileDevice()) {
    $output = '';
} else if($display_tablet == 'not_display_tablet' && $context->isTabletDevice()) {
   $output = '';
} else {
    $output = '[rev_slider alias="'.$alias.'"]';
}
 echo $output;
