<?php

abstract class ComposerShortCodeUniversalAdmin extends ComposerShortCode {

    protected $html_editor_already_is_used = true;

    public function __construct($settings) {

        $this->settings = $settings;

        $this->context = Context::getContext();

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();

        }

        if (!isset($this->context->_composer)) {
            $this->context->_composer = Composer::getInstance();

        }

        $this->addShortCode($this->settings['base'], [$this, 'output']);
    }

    protected function content($atts, $content = null) {

        return '';
    }

    public function l($string, $idLang = null, $context = null) {

        $class = 'ComposerShortCodeUniversalAdmin';

        if (isset($this->context->translations)) {
            return $this->context->translations->getClassTranslation($string, $class);
        }

        return $string;

    }

    public function contentAdmin($atts, $content = null) {

        $output = '';
        $this->loadParams();

        $content = $el_position = '';

        if (isset($this->settings['params'])) {
            $vc_manager = ephenyx_manager();
            $shortcode_attributes = [];

            foreach ($this->settings['params'] as $param) {

                if ($param['param_name'] != 'content') {
                    $shortcode_attributes[$param['param_name']] = $param['value'];
                } else

                if ($param['param_name'] == 'content' && $content === null) {
                    $content = $param['value'];
                }

            }

            $atts = Composer::shortcode_atts($shortcode_attributes, $atts);
            extract($atts);
            $editor_css_classes = apply_filters('vc_edit_form_class', ['vc_col-sm-12', 'wpb_edit_form_elements']);
            $output .= '<div class="' . implode(' ', $editor_css_classes) . '"><h2>' . $this->l('Edit') . ' ' . $this->settings['name'] . '</h2>';

            foreach ($this->settings['params'] as $param) {
                $param_value = isset($atts[$param['param_name']]) ? $atts[$param['param_name']] : null;

                if (is_array($param_value) && !empty($param['type']) && $param['type'] != 'checkbox') {

                    reset($param_value);
                    $first_key = key($param_value);
                    $param_value = $param_value[$first_key];
                }

                $output .= $this->singleParamEditHolder($param, $param_value);
            }

            $output .= '<div class="edit_form_actions"><a href="#" class="wpb_save_edit_form button-primary">' . $this->l('Save') . '</a></div>';

            $output .= '</div>'; //close wpb_edit_form_elements
        }

        $output = str_replace('<input>', '', $output);
        return $output;
    }

    protected function singleParamEditHolder($param, $param_value) {

        $vc_main = $this->context->_composer;
        $param['vc_single_param_edit_holder_class'] = ['wpb_el_type_' . $param['type'], 'vc_shortcode-param'];

        if (!empty($param['param_holder_class'])) {
            $param['vc_single_param_edit_holder_class'][] = $param['param_holder_class'];
        }

        $style = '';

        if (!empty($param['param_holder_style'])) {
            $style = 'style="' . $param['param_holder_style'] . '"';
        }

        $param = ComposerShortcodeEditForm::changeEditFormFieldParams($param);

        $output = '<div class="' . implode(' ', $param['vc_single_param_edit_holder_class']) . '" data-param_name="' . $vc_main->esc_attr($param['param_name']) . '" data-param_type="' . $vc_main->esc_attr($param['type']) . '" data-param_settings="' . $vc_main->esc_attr(Tools::jsonEncode($param)) . '" ' . $style . '>';
        $output .= (isset($param['heading'])) ? '<div class="wpb_element_label">' . $param['heading'] . '</div>' : '';
        $output .= '<div class="edit_form_line">';
        $output .= $this->singleParamEditForm($param, $param_value);
        $output .= (isset($param['description'])) ? '<span class="vc_description vc_clearfix">' . $param['description'] . '</span>' : '';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    protected function generateRandomString($length = 5) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    protected function singleParamEditForm($param, $param_value) {

        $param_line = '';
        $file = fopen("testsingleParamEditForm.txt", "a");
        $vc_manager = $this->context->_composer;

        switch ($param['type']) {
        case 'textfield':
            $value = $param_value;

            $param_line .= '<input name="' . $param['param_name'] . '" class="wpb_vc_param_value wpb-textinput ' . $param['param_name'] . ' ' . $param['type'] . '" type="text" value="' . $value . '"/>';
            break;
        case 'dropdown':
            $selectId = $this->generateRandomString();
            $css_option = get_dropdown_option($param, $param_value);
            $param_line .= '<select id="' . $selectId . '" name="' . $param['param_name'] . '" class="wpb_vc_param_value wpb-input wpb-select ' . $param['param_name'] . ' ' . $param['type'] . ' ' . $css_option . '" data-option="' . $css_option . '">';

            if (isset($param['value'])) {

                foreach ($param['value'] as $text_val => $val) {

                    if (is_numeric($text_val) && (is_string($val) || is_numeric($val))) {
                        $text_val = $val;
                    }

                    $selected = '';

                    if ($param_value !== '' && (string) $val === (string) $param_value) {
                        $selected = ' selected="selected"';
                    }

                    $param_line .= '<option class="' . $val . '" value="' . $val . '"' . $selected . '>' . htmlspecialchars($text_val) . '</option>';
                }

            }

            $param_line .= '</select>';
            $param_line .= '<script type="text/javascript">
                    $("#' . $selectId . '").selectmenu({
                        width: 645,
                        icons: { button: "fa-duotone fa-bars" },
                        classes: {
                            "ui-selectmenu-menu": "selectComposer"
                        }
                    });
                </script>';
            break;
        case 'animation':
            $selectId = $this->generateRandomString();
            $param_line .= '<link rel="stylesheet" href="/content/backoffice/composer/animate.min.css" type="text/css" media="all" />';
            $param_line .= '<div class="vc_row">';

            if (isset($param['value'])) {
                $styles = $param['value'];
            } else {
                $styles = $this->animationStyles();
            }

            if (is_array($param_value)) {
                $param_value = 'none';
            }

            if (is_array($styles) && !empty($styles)) {
                $left_side = '<div class="vc_col-sm-6">';
                $param_line .= '<input type="hidden" class="wpb_vc_param_value animation-style" data-id="' . $selectId . '" name="' . $param['param_name'] . '" value="' . $param_value . '">';
                $build_style_select = '<select id="' . $selectId . '" name="' . $param['param_name'] . '" class="vc_param-animation-style">';

                foreach ($styles as $key => $style) {

                    if ($key == 0) {
                        continue;
                    }

                    $build_style_select .= '<optgroup ' . (isset($style['label']) ? 'label="' . htmlspecialchars($style['label']) . '"' : '') . '>';

                    if (is_array($style['values']) && !empty($style['values'])) {

                        foreach ($style['values'] as $key => $value) {
                            $selected = '';

                            if (isset($param_value)) {
                                $val = (is_array($value) ? $value['value'] : $value);

                                if (is_string($val) && is_string($param_value) && $val === $param_value) {
                                    $selected = ' selected="selected"';
                                }

                            }

                            $build_style_select .= '<option value="' . (is_array($value) ? $value['value'] : $value) . '" ' . $selected . '>' . (is_array($value) ? $value['value'] : $value) . '</option>';
                        }

                    }

                    $build_style_select .= '</optgroup>';
                }

                $build_style_select .= '</select>';
                $left_side .= $build_style_select;
                $left_side .= '</div>';
                $param_line .= $left_side;

                $right_side = '<div class="vc_col-sm-6">';
                $right_side .= '<div class="vc_param-animation-style-preview"><button class="vc_btn vc_btn-grey vc_btn-sm vc_param-animation-style-trigger">' . $this->l('Animate it') . '</button></div>';
                $right_side .= '</div>';
                $param_line .= $right_side;
            }

            $param_line .= '</div>'; // Close Row
            $param_line .= sprintf('<input name="%s" class="wpb_vc_param_value  %s %s_field" type="hidden" value="%s"  />', htmlspecialchars($param['param_name']), htmlspecialchars($param['param_name']), htmlspecialchars($param['type']), $param_value);
            $param_line .= '<script type="text/javascript">
                    $("#' . $selectId . '").selectmenu({
                        width: 300,
                        icons: { button: "fa-duotone fa-bars" },
                        classes: {
                            "ui-selectmenu-menu": "selectComposer"
                        },
                        change: function( event, ui ) {
                            $(".wpb_vc_param_value.animation-style").val(ui.item.value);
                            var animation = ui.item.value;
                            if("none" !== animation) {
                                animation_style_test($(".vc_param-animation-style-preview"), "vc_param-animation-style-preview " + animation)
                            }
                        }
                    });
                    function animation_style_test(el, x) {
                        $(el).removeClass().addClass(x + " animated").one("webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend", function() {
                            $(this).removeClass().addClass("vc_param-animation-style-preview")
                        })
                    }
                </script>';
            break;
        case 'textarea_html':

            if ($this->html_editor_already_is_used !== false) {
                $param_line .= '<div id="wp-wpb_tinymce_content-wrap" class="wp-core-ui wp-editor-wrap html-active">';
                $param_line .= '<div id="wp-wpb_tinymce_content-editor-container" class="wp-editor-container">';
                $param_line .= '<textarea id="wpb_tinymce_content" name="wpb_tinymce_content" class="wpb-textarea visual_composer_tinymce content textarea_html wp-editor-area rte autoload_rte">' . $param_value . '</textarea>';

                ob_start();
                ?>
                    <script type="text/javascript">
                         $(function(){
                             var tempClass = 'visual_composer_tinymce' + Math.floor(Math.random() * 99999999).toString().padStart(8, '0');
                             $(' .visual_composer_tinymce').addClass(tempClass);
                             loadTiny(tempClass);
                          });
                    </script>
                    <?php
$param_line .= ob_get_clean();
                $param_line .= '</div>';
                $param_line .= '</div>';
            } else {
                $this->html_editor_already_is_used = $param['param_name'];
                $param_line .= do_shortcode_param_settings_field('textarea_html', $param, $param_value);
            }

            break;
        case 'checkbox':
            $current_value = explode(",", $param_value);
            $values = is_array($param['value']) ? $param['value'] : [];

            foreach ($values as $label => $v) {
                $checked = in_array($v, $current_value) ? ' checked="checked"' : '';

                if (!$checked && isset($param['default_check'])) {
                    $checked = $param['default_check'];
                }

                $param_line .= ' <input id="' . $param['param_name'] . '-' . $v . '" value="' . $v . '" class="wpb_vc_param_value ' . $param['param_name'] . ' ' . $param['type'] . '" type="checkbox" name="' . $param['param_name'] . '"' . $checked . '> ' . $label;
            }

            break;
        case 'posttypes':
            $args = [
                'public' => true,
            ];
            $post_types = get_post_types($args);

            foreach ($post_types as $post_type) {
                $checked = "";

                if ($post_type != 'attachment') {

                    if (in_array($post_type, explode(",", $param_value))) {
                        $checked = ' checked="checked"';
                    }

                    $param_line .= ' <input id="' . $param['param_name'] . '-' . $post_type . '" value="' . $post_type . '" class="wpb_vc_param_value ' . $param['param_name'] . ' ' . $param['type'] . '" type="checkbox" name="' . $param['param_name'] . '"' . $checked . '> ' . $post_type;
                }

            }

            break;
        case 'taxonomies':
        case 'taxomonies':
            $post_types = get_post_types(['public' => false, 'name' => 'attachment'], 'names', 'NOT');

            foreach ($post_types as $type) {
                $taxonomies = get_object_taxonomies($type, '');

                foreach ($taxonomies as $tax) {
                    $checked = "";

                    if (in_array($tax->name, explode(",", $param_value))) {
                        $checked = ' checked="checked"';
                    }

                    $param_line .= ' <label data-post-type="' . $type . '"><input id="' . $param['param_name'] . '-' . $tax->name . '" value="' . $tax->name . '" data-post-type="' . $type . '" class="wpb_vc_param_value ' . $param['param_name'] . ' ' . $param['type'] . '" type="checkbox" name="' . $param['param_name'] . '"' . $checked . '> ' . $tax->label . '</label>';
                }

            }

            break;
        case 'exploded_textarea':
            $param_value = str_replace(",", "\n", $param_value);
            $param_line .= '<textarea name="' . $param['param_name'] . '" class="wpb_vc_param_value wpb-textarea ' . $param['param_name'] . ' ' . $param['type'] . '">' . $param_value . '</textarea>';
            break;
        case 'textarea_raw_html':
            $param_line .= '<textarea name="' . $param['param_name'] . '" class="wpb_vc_param_value wpb-textarea_raw_html ' . $param['param_name'] . ' ' . $param['type'] . '" rows="16">' . htmlentities(rawurldecode(base64_decode($param_value)), ENT_COMPAT, 'UTF-8') . '</textarea>';
            break;
        case 'textarea_raw_code':
            $param_line .= '<input type="hidden" id="ace_textarea_raw_code" class="wpb_vc_param_value wpb-textarea_code_html ' . $param['param_name'] . ' ' . $param['type'] . '"  name="' . $param['param_name'] . '" value="' . $param_value . '">';
            $param_line .= '<div class="ace-editor" id="ace_' . $param['param_name'] . '">' . $param_value . '</div>';
            $param_line .= '<script type="text/javascript">
                    $(document).ready(function(){
                        initComposerAce("ace_' . $param['param_name'] . '", false, true);
                    });
                </script>';
            break;
        case 'textarea_safe':
            $param_line .= '<textarea name="' . $param['param_name'] . '" class="wpb_vc_param_value wpb-textarea_raw_html ' . $param['param_name'] . ' ' . $param['type'] . '">' . value_from_safe($param_value, true) . '</textarea>';
            break;
        case 'textarea':
            $param_value = $param_value;
            $param_line .= '<textarea name="' . $param['param_name'] . '" class="wpb_vc_param_value wpb-textarea ' . $param['param_name'] . ' ' . $param['type'] . '">' . $param_value . '</textarea>';
            break;
        case 'attach_images':

            $param_value = removeNotExistingImgIDs($param_value);
            $param_line .= '<script type="text/javascript">';
            $param_line .= 'var imgpath = "composer/";';
            $param_line .= '</script>';
            $param_line .= '<input type="hidden" class="wpb_vc_param_value gallery_widget_attached_images_ids ' . $param['param_name'] . ' ' . $param['type'] . '" name="' . $param['param_name'] . '" value="' . $param_value . '"/>';
            $param_line .= '<div class="gallery_widget_attached_images">';
            $param_line .= '<ul class="gallery_widget_attached_images_list">';
            $param_line .= ($param_value != '') ? phenyxFieldAttachedImages(explode(",", $param_value)) : '';
            $param_line .= '</ul>';
            $param_line .= '</div>';
            $param_line .= '<div class="gallery_widget_site_images">';
            $param_line .= '</div>';
            $param_line .= '<a class="gallery_widget_add_images" href="javascript:void(0)" title="' . $this->l('Add images') . '">' . $this->l('Add images') . '</a>';
            break;
        case 'attach_image':
            fwrite($file, print_r($param, true));
            fwrite($file, "param_value" . PHP_EOL . print_r($param_value, true));
            $param_value = removeNotExistingImgIDs(preg_replace('/[^\d]/', '', $param_value));
            $param_line .= '<script type="text/javascript">';
            $param_line .= 'var imgpath = "composer/";';
            $param_line .= '</script>';
            $param_line .= '<input type="hidden" class="wpb_vc_param_value gallery_widget_attached_images_ids ' . $param['param_name'] . ' ' . $param['type'] . '" name="' . $param['param_name'] . '" value="' . $param_value . '"/>';
            $param_line .= '<div class="gallery_widget_attached_images">';
            $param_line .= '<ul class="gallery_widget_attached_images_list">';
            $param_line .= ($param_value != '') ? phenyxFieldAttachedImages(explode(",", $param_value)) : '';
            $param_line .= '</ul>';
            $param_line .= '</div>';
            $param_line .= '<div class="gallery_widget_site_images">';
            $param_line .= '</div>';
            $param_line .= '<a class="gallery_widget_add_images" href="#" use-single="true" title="' . $this->l('Add image') . '">' . $this->l('Add image') . '</a>';
            fwrite($file, $param_line . PHP_EOL);
            break;
        case 'media_dropdown':
            $css_option = get_dropdown_option($param, $param_value);
            $param_line .= '<select id="widget_select-media" name="' . $param['param_name'] . '" class="wpb_vc_param_value wpb-input widget_select-media ' . $param['param_name'] . ' ' . $param['type'] . ' ' . $css_option . '" data-option="' . $css_option . '">';

            if (isset($param['value'])) {

                foreach ($param['value'] as $text_val => $val) {

                    if (is_numeric($text_val) && (is_string($val) || is_numeric($val))) {
                        $text_val = $val;
                    }

                    $selected = '';

                    if ($param_value !== '' && (string) $val === (string) $param_value) {
                        $selected = ' selected="selected"';
                    }

                    $param_line .= '<option class="' . $val . '" value="' . $val . '"' . $selected . '>' . htmlspecialchars($text_val) . '</option>';
                }

            }

            $param_line .= '</select>';
            $param_line .= '<script type="text/javascript">
                    $("#widget_select-media").selectmenu({
                        width: 645,
                        icons: { button: "fa-duotone fa-bars" },
                        classes: {
                            "ui-selectmenu-menu": "selectComposer"
                        },
                        change: function(event, ui) {
                            if (ui.item.value > 0) {
                                $("#widget_attached_pdf").val(ui.item.label);
                            }
                        }
                    });
                </script>';
            break;
        case 'attach_media':
            $param_line .= '<input type="hidden" id="widget_attached_pdf" class="wpb_vc_param_value widget_attached_pdf ' . $param['param_name'] . ' ' . $param['type'] . '" name="' . $param['param_name'] . '" value="' . $param_value . '"/>';
            $src = '<img src="/content/backoffice/blacktie/img/pdf-downbload.png" width="300" id="imageMedia">';
            $param_line .= '<script type="text/javascript">';
            $param_line .= 'var totalPdfs = [];';
            $param_line .= '</script>';
            $param_line .= '<script type="text/javascript" src="/content/js/pdfuploadify.min.js"></script>';
            $param_line .= '<div id="imageMedia_dragBox"><div class="imageuploadify imageuploadify-container-image">' . $src . '</div></div><input id="MediaFile" type="file" data-target="imageMedia" accept="application/pdf" multiple>';
            $param_line .= '<script type="text/javascript">';
            $param_line .= '$(document).ready(function() {';
            $param_line .= '$("#MediaFile").pdfuplodify({
                    afterreadAsDataURL: function() {
                        proceedSaveAttachment();
                    }
                });';
            $param_line .= '});';
            $param_line .= '</script>';
            break;
        case 'widgetised_sidebars':
            $wpb_sidebar_ids = [];
            $sidebars = $GLOBALS['wp_registered_sidebars'];
            $param_line .= '<select name="' . $param['param_name'] . '" class="wpb_vc_param_value dropdown wpb-input wpb-select ' . $param['param_name'] . ' ' . $param['type'] . '">';

            foreach ($sidebars as $sidebar) {
                $selected = '';

                if ($sidebar["id"] == $param_value) {
                    $selected = ' selected="selected"';
                }

                $sidebar_name = $sidebar["name"];
                $param_line .= '<option value="' . $sidebar["id"] . '"' . $selected . '>' . $sidebar_name . '</option>';
            }

            $param_line .= '</select>';
            break;
        case 'colorpicker':
            $colorPickerId = $this->generateRandomString();
            $param_line .= '<input size="20" type="text" id="' . $colorPickerId . '" name="' . $param['param_name'] . '" data-id="new" class="wpb_vc_param_value pm_colorpicker" value="' . $param_value . '"/>';
            $param_line .= '<div class="col-lg-4 metroiPicker"><div id="metroiPicker_new" style="height: 40px;"></div></div>';
            $param_line .= '<script type="text/javascript">';
            $param_line .= '$("#' . $colorPickerId . '").colorpicker({
                select: function(event, color) {
                    $("#metroiPicker_new").css("background-color", "#"+color.formatted);
                },
                close: function(event, color) {
                    $("#metroiPicker_new").css("background-color", "#"+color.formatted);
                },
                ok: function(event, color) {
                    $("#metroiPicker_new").css("background-color", "#"+color.formatted);
                    $(this).val("#"+color.formatted);
                },
            })</script>';

            break;
        case 'tab_id':

            $dependency = generate_dependencies_attributes($param);
            $param_line .= '<div class="my_param_block">';
            $param_line .= '<input name="' . $param['param_name'];
            $param_line .= '" class="wpb_vc_param_value wpb-textinput ';
            $param_line .= $param['param_name'] . ' ' . $param['type'] . '_field" type="hidden" value="' . $param_value . '" ' . $dependency . ' />';
            $param_line .= '<label>' . $param_value . '</label>';
            $param_line .= '</div>';

            break;
        case 'extra_css':

            if (isset($param['param_value']) && is_array($param['param_value'])) {

                foreach ($param['param_value'] as $css_uri => $media) {
                    $param_line .= '<link rel="stylesheet" href="' . $css_uri . '" type="text/css" media="' . $media . '" />';
                }

            }

            break;
        case 'extra_js':

            if (isset($param['param_value']) && is_array($param['param_value'])) {

                foreach ($param['param_value'] as $js_uri) {
                    $param_line .= '<script type="text/javascript" src="' . $js_uri . '"></script>';
                }

            }

            break;
        case 'css_editor':

            $css_editor = ComposerCssEditor::getInstance();
            $css_editor->settings($param);
            $css_editor->value($param_value);
            $param_line .= $css_editor->render();

            break;
        case 'column_offset':

            $column_offset = new ComposerColumnOffset($param, $param_value);
            $param_line .= $column_offset->render();

            break;
        default:
            $extraType = Context::getContext()->_hook->exec('actionSingleParamEditForm', ['param' => $param, 'param_value' => $param_value], null, true);

            if (is_array($extraType) && count($extraType)) {

                foreach ($extraType as $plugin => $value) {
                    $param_line .= $value;
                }

            } else {
                $param_line .= do_shortcode_param_settings_field($param['type'], $param, $param_value);
            }

            break;

        }

        return $param_line;
    }

    protected function getTinyHtmlTextArea($param_value, $param = []) {

        $param_line = '';

        if (function_exists('wp_editor')) {
            $default_content = $param_value;
            $output_value = '';
            ob_start();
            wp_editor($default_content, 'wpb_tinymce_' . $param['param_name'], ['editor_class' => 'wpb_vc_param_value wpb-textarea visual_composer_tinymce ' . $param['param_name'] . ' ' . $param['type'], 'media_buttons' => true, 'wpautop' => true]);
            $output_value = ob_get_contents();
            ob_end_clean();
            $param_line .= $output_value;
        }

        return $param_line;
    }

    protected function animationStyles() {

        $vc_manager = ephenyx_manager();
        $styles = [
            [
                'values' => [
                    $this->l('None') => 'none',
                ],
            ],
            [
                'label'  => $this->l('Attention Seekers'),
                'values' => [
                    // text to display => value
                    $this->l('bounce')     => [
                        'value' => 'bounce',
                        'type'  => 'other',
                    ],
                    $this->l('flash')      => [
                        'value' => 'flash',
                        'type'  => 'other',
                    ],
                    $this->l('pulse')      => [
                        'value' => 'pulse',
                        'type'  => 'other',
                    ],
                    $this->l('rubberBand') => [
                        'value' => 'rubberBand',
                        'type'  => 'other',
                    ],
                    $this->l('shake')      => [
                        'value' => 'shake',
                        'type'  => 'other',
                    ],
                    $this->l('swing')      => [
                        'value' => 'swing',
                        'type'  => 'other',
                    ],
                    $this->l('tada')       => [
                        'value' => 'tada',
                        'type'  => 'other',
                    ],
                    $this->l('wobble')     => [
                        'value' => 'wobble',
                        'type'  => 'other',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Bouncing Entrances'),
                'values' => [
                    // text to display => value
                    $this->l('bounceIn')      => [
                        'value' => 'bounceIn',
                        'type'  => 'in',
                    ],
                    $this->l('bounceInDown')  => [
                        'value' => 'bounceInDown',
                        'type'  => 'in',
                    ],
                    $this->l('bounceInLeft')  => [
                        'value' => 'bounceInLeft',
                        'type'  => 'in',
                    ],
                    $this->l('bounceInRight') => [
                        'value' => 'bounceInRight',
                        'type'  => 'in',
                    ],
                    $this->l('bounceInUp')    => [
                        'value' => 'bounceInUp',
                        'type'  => 'in',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Bouncing Exits'),
                'values' => [
                    // text to display => value
                    $this->l('bounceOut')      => [
                        'value' => 'bounceOut',
                        'type'  => 'out',
                    ],
                    $this->l('bounceOutDown')  => [
                        'value' => 'bounceOutDown',
                        'type'  => 'out',
                    ],
                    $this->l('bounceOutLeft')  => [
                        'value' => 'bounceOutLeft',
                        'type'  => 'out',
                    ],
                    $this->l('bounceOutRight') => [
                        'value' => 'bounceOutRight',
                        'type'  => 'out',
                    ],

                    $this->l('bounceOutUp')    => [
                        'value' => 'bounceOutUp',
                        'type'  => 'out',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Fading Entrances'),
                'values' => [
                    // text to display => value
                    $this->l('fadeIn')         => [
                        'value' => 'fadeIn',
                        'type'  => 'in',
                    ],
                    $this->l('fadeInDown')     => [
                        'value' => 'fadeInDown',
                        'type'  => 'in',
                    ],
                    $this->l('fadeInDownBig')  => [
                        'value' => 'fadeInDownBig',
                        'type'  => 'in',
                    ],
                    $this->l('fadeInLeft')     => [
                        'value' => 'fadeInLeft',
                        'type'  => 'in',
                    ],
                    $this->l('fadeInLeftBig')  => [
                        'value' => 'fadeInLeftBig',
                        'type'  => 'in',
                    ],
                    $this->l('fadeInRight')    => [
                        'value' => 'fadeInRight',
                        'type'  => 'in',
                    ],
                    $this->l('fadeInRightBig') => [
                        'value' => 'fadeInRightBig',
                        'type'  => 'in',
                    ],
                    $this->l('fadeInUp')       => [
                        'value' => 'fadeInUp',
                        'type'  => 'in',
                    ],
                    $this->l('fadeInUpBig')    => [
                        'value' => 'fadeInUpBig',
                        'type'  => 'in',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Fading Exits'),
                'values' => [
                    $this->l('fadeOut')         => [
                        'value' => 'fadeOut',
                        'type'  => 'out',
                    ],
                    $this->l('fadeOutDown')     => [
                        'value' => 'fadeOutDown',
                        'type'  => 'out',
                    ],
                    $this->l('fadeOutDownBig')  => [
                        'value' => 'fadeOutDownBig',
                        'type'  => 'out',
                    ],
                    $this->l('fadeOutLeft')     => [
                        'value' => 'fadeOutLeft',
                        'type'  => 'out',
                    ],
                    $this->l('fadeOutLeftBig')  => [
                        'value' => 'fadeOutLeftBig',
                        'type'  => 'out',
                    ],
                    $this->l('fadeOutRight')    => [
                        'value' => 'fadeOutRight',
                        'type'  => 'out',
                    ],
                    $this->l('fadeOutRightBig') => [
                        'value' => 'fadeOutRightBig',
                        'type'  => 'out',
                    ],
                    $this->l('fadeOutUp')       => [
                        'value' => 'fadeOutUp',
                        'type'  => 'out',
                    ],
                    $this->l('fadeOutUpBig')    => [
                        'value' => 'fadeOutUpBig',
                        'type'  => 'out',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Flippers'),
                'values' => [
                    $this->l('flip')     => [
                        'value' => 'flip',
                        'type'  => 'other',
                    ],
                    $this->l('flipInX')  => [
                        'value' => 'flipInX',
                        'type'  => 'in',
                    ],
                    $this->l('flipInY')  => [
                        'value' => 'flipInY',
                        'type'  => 'in',
                    ],
                    $this->l('flipOutX') => [
                        'value' => 'flipOutX',
                        'type'  => 'out',
                    ],
                    $this->l('flipOutY') => [
                        'value' => 'flipOutY',
                        'type'  => 'out',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Lightspeed'),
                'values' => [
                    $this->l('lightSpeedIn')  => [
                        'value' => 'lightSpeedIn',
                        'type'  => 'in',
                    ],
                    $this->l('lightSpeedOut') => [
                        'value' => 'lightSpeedOut',
                        'type'  => 'out',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Rotating Entrances'),
                'values' => [
                    $this->l('rotateIn')          => [
                        'value' => 'rotateIn',
                        'type'  => 'in',
                    ],
                    $this->l('rotateInDownLeft')  => [
                        'value' => 'rotateInDownLeft',
                        'type'  => 'in',
                    ],
                    $this->l('rotateInDownRight') => [
                        'value' => 'rotateInDownRight',
                        'type'  => 'in',
                    ],
                    $this->l('rotateInUpLeft')    => [
                        'value' => 'rotateInUpLeft',
                        'type'  => 'in',
                    ],
                    $this->l('rotateInUpRight')   => [
                        'value' => 'rotateInUpRight',
                        'type'  => 'in',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Rotating Exits'),
                'values' => [
                    $this->l('rotateOut')          => [
                        'value' => 'rotateOut',
                        'type'  => 'out',

                    ],
                    $this->l('rotateOutDownLeft')  => [
                        'value' => 'rotateOutDownLeft',
                        'type'  => 'out',
                    ],
                    $this->l('rotateOutDownRight') => [
                        'value' => 'rotateOutDownRight',
                        'type'  => 'out',
                    ],
                    $this->l('rotateOutUpLeft')    => [
                        'value' => 'rotateOutUpLeft',
                        'type'  => 'out',
                    ],
                    $this->l('rotateOutUpRight')   => [
                        'value' => 'rotateOutUpRight',
                        'type'  => 'out',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Specials'),
                'values' => [
                    $this->l('hinge')   => [
                        'value' => 'hinge',
                        'type'  => 'out',
                    ],
                    $this->l('rollIn')  => [
                        'value' => 'rollIn',
                        'type'  => 'in',
                    ],
                    $this->l('rollOut') => [
                        'value' => 'rollOut',
                        'type'  => 'out',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Zoom Entrances'),
                'values' => [
                    $this->l('zoomIn')      => [
                        'value' => 'zoomIn',
                        'type'  => 'in',
                    ],
                    $this->l('zoomInDown')  => [
                        'value' => 'zoomInDown',
                        'type'  => 'in',
                    ],
                    $this->l('zoomInLeft')  => [
                        'value' => 'zoomInLeft',
                        'type'  => 'in',
                    ],
                    $this->l('zoomInRight') => [
                        'value' => 'zoomInRight',
                        'type'  => 'in',
                    ],
                    $this->l('zoomInUp')    => [
                        'value' => 'zoomInUp',
                        'type'  => 'in',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Zoom Exits'),
                'values' => [
                    $this->l('zoomOut')      => [
                        'value' => 'zoomOut',
                        'type'  => 'out',
                    ],
                    $this->l('zoomOutDown')  => [
                        'value' => 'zoomOutDown',
                        'type'  => 'out',
                    ],
                    $this->l('zoomOutLeft')  => [
                        'value' => 'zoomOutLeft',
                        'type'  => 'out',
                    ],
                    $this->l('zoomOutRight') => [
                        'value' => 'zoomOutRight',
                        'type'  => 'out',
                    ],
                    $this->l('zoomOutUp')    => [
                        'value' => 'zoomOutUp',
                        'type'  => 'out',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Slide Entrances'),
                'values' => [
                    $this->l('slideInDown')  => [
                        'value' => 'slideInDown',
                        'type'  => 'in',
                    ],
                    $this->l('slideInLeft')  => [
                        'value' => 'slideInLeft',
                        'type'  => 'in',
                    ],
                    $this->l('slideInRight') => [
                        'value' => 'slideInRight',
                        'type'  => 'in',
                    ],
                    $this->l('slideInUp')    => [
                        'value' => 'slideInUp',
                        'type'  => 'in',
                    ],
                ],
            ],
            [
                'label'  => $this->l('Slide Exits'),
                'values' => [
                    $this->l('slideOutDown')  => [
                        'value' => 'slideOutDown',
                        'type'  => 'out',
                    ],
                    $this->l('slideOutLeft')  => [
                        'value' => 'slideOutLeft',
                        'type'  => 'out',
                    ],
                    $this->l('slideOutRight') => [
                        'value' => 'slideOutRight',
                        'type'  => 'out',
                    ],
                    $this->l('slideOutUp')    => [
                        'value' => 'slideOutUp',
                        'type'  => 'out',
                    ],
                ],
            ],
        ];

        /**
         * Used to override animation style list
         * @since 4.4
         */

        return $styles;
    }

    public function groupStyleByType($styles, $type) {

        $grouped = [];

        foreach ($styles as $group) {
            $inner_group = ['values' => []];

            if (isset($group['label'])) {
                $inner_group['label'] = $group['label'];
            }

            foreach ($group['values'] as $key => $value) {

                if ((is_array($value) && isset($value['type']) && ((is_string($type) && $value['type'] === $type) || is_array($type) && in_array($value['type'], $type, true))) || !is_array($value) || !isset($value['type'])) {
                    $inner_group['values'][$key] = $value;
                }

            }

            if (!empty($inner_group['values'])) {
                $grouped[] = $inner_group;
            }

        }

        return $grouped;
    }

}
