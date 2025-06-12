<?php
$output = $el_class = $video = $css_animation = '' ;
extract(Composer::shortcode_atts([
	'video'           => '',
	'el_class'    => '',
	'text_color'    => '',
	'css_animation'             => '',
], $atts));

$el_class = $this->getExtraClass($el_class);
$video_id = md5($video);

$css_class = 'wpb_text_column  ' . $el_class;
$animation = $this->getCSSAnimation($css_animation);



$output .= "\n\t" . '<div class="parallax-conteneur"><div class="jarallax-video jarallax_'.$video_id.'" data-jarallax data-jarallax-video="'.$video.'"></div><div class="' . $css_class . '">';
$output .= "\n\t\t" . '<div class="wpb_wrapper '.$animation.'"  style="color:'.$text_color.'">';
$output .= "\n\t\t\t" . js_remove_wpautop($content, true);
$output .= "\n\t\t" . '</div></div> ' . $this->endBlockComment('.wpb_wrapper');
$output .= "\n\t" . '</div><script type="text/javascript">$(".jarallax_'.$video_id.'").jarallax({
  speed: 0.2,
});</script>';
$output .= "\n\t" . $this->endBlockComment('.wpb_text_column');

echo $output;