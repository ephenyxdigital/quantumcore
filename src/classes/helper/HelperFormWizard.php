<?php

/**
 * Class HelperFormWizard
 *
 * @since 1.8.1.0
 */
class HelperFormWizard extends HelperForm {

    

    // @codingStandardsIgnoreEnd

    /**
     * HelperFormWizard constructor.
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function __construct() {
        
       parent::__construct();
        $this->base_tpl = 'form_wizard.tpl';
        
    }

    public function generate() {

        $this->tpl = $this->createTemplate($this->base_tpl);

        if (is_null($this->submit_action)) {
            $this->submit_action = 'submitAdd' . $this->table;
        }

        $categories = true;
        $color = true;
        $date = true;
        $tinymce = true;
        $textareaAutosize = true;
        foreach ($this->fields_form['steps'] as $fieldsetKey => &$fieldset) {

            
            if (isset($fieldset['input'])) {

                foreach ($fieldset['input'] as $key => &$params) {
                    // If the condition is not met, the field will not be displayed

                    if (isset($params['condition']) && !$params['condition']) {
                        unset($this->fields_form[$fieldsetKey]['form']['input'][$key]);
                    }
                    
                    switch ($params['type']) {
                    	case 'select':
                        	$fieldName = (string) $params['name'];
                        	// If multiple select check that 'name' field is suffixed with '[]'

                        	if (isset($params['multiple']) && $params['multiple'] && stripos($fieldName, '[]') === false) {
                           		$params['name'] .= '[]';
                        	}

                        	break;
                        
                    	case 'image':
                           
                            $script = true;

                            if (isset($params['script'])) {
                                $script = $params['script'];
                            }

                            $src = '<img src="' . $params['image'] . '" id="image' . $params['name'] . '"  alt="' . $params['label'] . '" style="background-color:white; width: 300px">';
                            $html = '<div id="image' . $params['name'] . '_dragBox">';
                            $html .= '<div class="imageuploadify imageuploadify-container-image">' . $src . '</div>';
                            $html .= '</div>';
                            $html .= '<input id="' . $params['name'] . 'File" type="file" data-target="image' . $params['name'] . '" accept="image/*" multiple>';

                            if ($script) {
                                $html .= '<script type="text/javascript">
                                    $(document).ready(function() {
                                       $("#' . $params['name'] . 'File").imageuploadify();
                                    });
                                 </script>';
                            }

                            $params['paramImage'] = $html;
                            break;
                        case 'pdf':
                           
                            $src = '<img src="' . $params['pdf'] . '" width="300" id="image' . $params['name'] . '"  alt="' . $params['label'] . '">';

                        	
                        	$html = '<div id="image' . $params['name'] . '_dragBox"><div class="imageuploadify imageuploadify-container-image">' . $src . '</div></div><input id="' . $params['name'] . 'File" type="file" data-target="image' . $params['name'] . '" accept="application/pdf" multiple>
                            <script type="text/javascript">
                                $(document).ready(function() {
                                    $("#' . $params['name'] . 'File").pdfuplodify();
                                });
                            </script>';

                        	$params['paramPdf'] = $html;
                        	break;
                        case 'media':
                           
                            $src = '<img src="' . $params['media'] . '" width="300" id="image' . $params['name'] . '"  alt="' . $params['label'] . '">';

                        	
                        	$html = '<div id="image' . $params['name'] . '_dragBox"><div class="imageuploadify imageuploadify-container-image">' . $src . '</div></div><input id="' . $params['name'] . 'File" type="file" data-target="image' . $params['name'] . '" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,.xls,.xlsx,.pps" multiple>
                            <script type="text/javascript">
                                $(document).ready(function() {
                                    $("#' . $params['name'] . 'File").mediauplodify();
                                });
                            </script>';

                        	$params['paramMedia'] = $html;
                        	break;
                        case 'logo':

                        	$src = '<img src="'.$params['image'].'" id="image'.$params['name'].'"  alt="'.$params['label'].'">';
							
							
							$html = '<div id="objet_'.$params['objId'].'"><div class="imageuploadify-container-image">'.$src.'</div><input id="'.$params['name'].'File" type="file" data-target="image'.$params['name'].'" accept="image/*" multiple></div>
							<script type="text/javascript">
								$(document).ready(function() {
									$("#'.$params['name'].'File").imageuploadify({
                                        afterreadAsDataURL: function(){
                                            loadLogo();			
		                                  }
                                    });
								});
							</script>';
							
						      $params['paramlogo'] = $html;
                        	break;
                    	case 'file':

                        	$uploader = new HelperUploader();
                        	$uploader->setId(isset($params['id']) ? $params['id'] : null);
                        	$uploader->setName($params['name']);
                        	$uploader->setUrl(isset($params['url']) ? $params['url'] : null);
                        	$uploader->setMultiple(isset($params['multiple']) ? $params['multiple'] : false);
                        	$uploader->setUseAjax(isset($params['ajax']) ? $params['ajax'] : false);
                        	$uploader->setMaxFiles(isset($params['max_files']) ? $params['max_files'] : null);

                        	if (isset($params['files']) && $params['files']) {
								$uploader->setFiles($params['files']);
                        	} else if (isset($params['image']) && $params['image']) {
                            	// Use for retrocompatibility
                            	$uploader->setFiles(
                                	[
                                   		0 => [
                                        	'type'       => HelperUploader::TYPE_IMAGE,
                                        	'image'      => isset($params['image']) ? $params['image'] : null,
                                        	'size'       => isset($params['size']) ? $params['size'] : null,
                                        	'delete_url' => isset($params['delete_url']) ? $params['delete_url'] : null,
                                    	],
                                	]
                            	);
                        	}

                        	if (isset($params['file']) && $params['file']) {
                            	// Use for retrocompatibility
                            	$uploader->setFiles(
                                	[
                                    	0 => [
                                        	'type'         => HelperUploader::TYPE_FILE,
                                        	'size'         => isset($params['size']) ? $params['size'] : null,
                                        	'delete_url'   => isset($params['delete_url']) ? $params['delete_url'] : null,
                                        	'download_url' => isset($params['file']) ? $params['file'] : null,
										],
                                	]
                            	);
                        	}

                        	if (isset($params['thumb']) && $params['thumb']) {
                            	// Use for retrocompatibility
                            	$uploader->setFiles(
                                	[
                                    	0 => [
                                        	'type'  => HelperUploader::TYPE_IMAGE,
                                        	'image' => isset($params['thumb']) ? '<img src="' . $params['thumb'] . '" alt="' . (isset($params['title']) ? $params['title'] : '') . '" title="' . (isset($params['title']) ? $params['title'] : '') . '" />' : null,
                                    	],
                                	]
                            	);
                        	}

                        	$uploader->setTitle(isset($params['title']) ? $params['title'] : null);
                        	$params['file'] = $uploader->render();
                        	break;
                    	case 'color':

                        	if ($color) {
                            // Added JS file
                            	$this->context->controller->addJqueryPlugin('colorpicker');
                            	$color = false;
                        	}

                        	break;
                    	case 'date':

                        	if ($date) {
                            	$this->context->controller->addJqueryUI('ui.datepicker');
                            	$date = false;
                        	}

                        	break;
                    	case 'textarea':

                        	if ($tinymce) {
                            	$iso = $this->context->language->language_code;
                            	$this->tpl_vars['iso'] = $iso;
                            	$this->tpl_vars['path_css'] = $this->context->theme->css_theme;
                            	$this->tpl_vars['ad'] = __EPH_BASE_URI__ . basename(_EPH_ROOT_DIR_);
                            	$this->tpl_vars['tinymce'] = true;

                            	$this->context->controller->addJS(_EPH_JS_DIR_.'tinymce/tinymce.min.js');
                            	$this->context->controller->addJS(_EPH_JS_DIR_.'tinymce.inc.js');
                            	$tinymce = false;
                        	}

                        	if ($textareaAutosize) {
                            	$this->context->controller->addJqueryPlugin('autosize');
                            	$textareaAutosize = false;
                        	}

                        	break;
                    	case 'tags':
                       		$this->context->controller->addJqueryPlugin('tagify');
                        	break;
                    	case 'code':
                        	$this->context->controller->addJS(_EPH_JS_DIR_ .'ace/ace.js');
                        	break;
                    	case 'shadow':

                        	if (!empty($this->fields_value[$params['name']])) {
                            	$keys = explode(' ', $this->fields_value[$params['name']]);
                        	} else {
                            	$keys = explode(' ', $params['default_val']);
                        	}

                        	if (is_array($keys)) {

                            	foreach ($keys as &$key) {
                                	$key = (int) preg_replace('#px#', '', $key);
                            	}

                        	} else {
                            	$keys = [0, 0, 0, 0];
                        	}

                        	$html = ' <div class="input-group" style="display: flex">
                            <span style="line-height:40px; marfin-right:10px">' . $this->l('x') . '</span>
                            <input size="3" type="text" class="css-margin" name="' . $params['name'] . '[]" value="' . $keys[0] . '" /> &nbsp;
                            <span style="line-height:40px; marfin-right:10px">' . $this->l('y') . '</span>
                            <input size="3" type="text" class="css-margin" name="' . $params['name'] . '[]" value="' . $keys[1] . '" /> &nbsp;
                            <span style="line-height:40px; marfin-right:10px">' . $this->l('blur') . '</span>
                            <input size="3" type="text" class="css-margin" name="' . $params['name'] . '[]" value="' . $keys[2] . '" /> &nbsp;
                            <span style="line-height:40px; marfin-right:10px">' . $this->l('spread distance') . '</span>
                            <input size="3" type="text" class="css-margin" name="' . $params['name'] . '[]" value="' . $keys[3] . '" /></div>';
                        	$params['shadow'] = $html;

                        	break;
                    	case 'gradient':

                        	if (!empty($this->fields_value[$params['name']])) {
                            	$keys = explode('-', $this->fields_value[$params['name']]);
                        	} else {
                            	$keys = explode('-', $params['default_val']);
                        	}

                        	$key_1 = !empty($keys[1]) ? $this->timberpress_rgb_to_hex($keys[1]) : '';
                        	$key_2 = !empty($keys[2]) ? $keys[2] : '';
                        	$key_3 = !empty($keys[3]) ? $keys[3] : '';
                        	$html = '<div class="col-lg-2">
                                <input data-hex="true" type="text" name="' . $params['name'] . '[]" id="' . $params['name'] . '_0" value="' . $this->timberpress_rgb_to_hex($keys[0]) . '" class="pm_colorpicker" />
                                </div>&nbsp;
                                <div class="col-lg-2" id="' . $params['name'] . '_gradient">
                                <input data-hex="true" type="text" name="' . $params['name'] . '[]" id="' . $params['name'] . '_1" value="' . $key_1 . '" class="pm_colorpicker" placeholder="optionnel" /></div><label class="control-label" style="line-height: 40px; float:left; padding 0 20px;">Profondeur</label>
                                <div class="col-lg-2" id="' . $params['name'] . '_gradient">
                                <input type="text" name="' . $params['name'] . '[]" id="' . $params['name'] . '_1" value="' . $key_2 . '" placeholder="optionnel" /></div>
                                <div class="col-lg-2" id="' . $params['name'] . '_gradient">
                                <select name="' . $params['name'] . '[]" id="' . $params['name'] . '_type">
                                <option value="">Choisir le tupe de propagation</option>
                                <option value="%"
                                ';

                        	if ($key_3 == '%') {
                            	$html .= ' selected="selected"';
                        	}

                        	$html .= '>Percent</option><option value="px"';

                        	if ($key_3 == 'px') {
                            	$html .= ' selected="selected"';
                        	}

                        	$html .= '>px</option></select></div>';
                        	$html .= '<script type="text/javascript">
                                $("' . $params['name'] . '_type").selectmenu();
                            </script>';

                        	$params['gradient'] = $html;

                        	break;
                    	case 'rvb_opacity':

                        	if (!empty($this->fields_value[$params['name']])) {
                            	$value = str_replace('rgba(', '', $this->fields_value[$params['name']]);
                            	$value = str_replace(')', '', $value);
                            	$keys = explode(',', $value);
                        	} else {
                            	$value = str_replace('rgba(', '', $params['default_val']);
                            	$value = str_replace(')', '', $value);
                            	$keys = explode(',', $value);
                        	}

                        	$rvb = 'rvb(' . $keys[0] . ',' . $keys[1] . ',' . $keys[2] . ')';
                        	$hex = $this->timberpress_rgb_to_hex($rvb);

                        	if (!isset($keys[3])) {
                            	$keys[3] = 1;
                        	}

                        	$color = '
                                    <div class="col-lg-2">
                                        <div class="row">
                                            <div class="input-group">
                                                <input type="text"
                                                data-hex="true"
                                                class="pm_colorpicker"
                                                name="' . $params['name'] . '[]"
                                                value="' . $hex . '" />
                                            </div>
                                        </div>
                                    </div>
                                ';
                        	$slide = '<div class="pm_slider">
                                        <input type="hidden" id="' . $params['name'] . '" name="' . $params['name'] . '[]" value="' . $keys[3] . '" />
                                        <div id="slider-' . $params['name'] . '"></div>&nbsp;&nbsp;&nbsp;&nbsp;
                                        <em id="slider-suffix-' . $params['name'] . '">' . $keys[3] . ' %</em>
                                        <script type="text/javascript">
                                            $(function() {
                                            $("#slider-' . $params['name'] . '").slider({
                                                range: false,
                                                min: 0,
                                                max: 1,
                                                step: 0.1,
                                                value: $("#' . $params['name'] . '").val(),
                                                slide: function(event, ui) {
                                                    $("#' . $params['name'] . '").val(ui.value);
                                                    $("#slider-suffix-' . $params['name'] . '").html(ui.value + " %");
                                                }
                                            });
                                            $("#slider-' . $params['name'] . '").slider("value", $("#' . $params['name'] . '").val());
                                            });
                                        </script>
                                </div>';
                        	$html = '<div class="form-group" style="line-height:40px">' . $color . '<div class="col-lg-1"></div>' . $slide . '</div>';
                       	 	$params['rvb_opacity'] = $html;

                        	break;
                    	case 'select_font_size':
                        	$suffix = '';

                        	if (!empty($this->fields_value[$params['name']])) {
                            	$suffix = substr($this->fields_value[$params['name']], -2);
                            	$fields_value = $this->fields_value[$params['name']];
                        	} else {
                            	$fields_value = $params['default_val'];
                        	}

                        	if (isset($value[1])) {
                            	$suffix = $value[1];
                        	}

                        	$select1 = '<select id="' . $params['name'] . '_type" name="' . $params['name'] . '"><option value=""';

                        	if ($suffix == '') {
                            	$select1 .= ' selected="selected"';
                        	}

                        	$select1 .= '>Veuillez choisir le type</option><option value="px"';

                        	if ($suffix == 'px') {
                            	$select1 .= ' selected="selected"';
                        	}

                        	$select1 .= '>px absolut unit</option><option value="vw"';

                        	if ($suffix == 'vw') {
                            	$select1 .= ' selected="selected"';
                        	}

                        	$select1 .= '>Relative to viewport‘s width</option><option value="em"';

                        	if ($suffix == 'em') {
                            	$select1 .= ' selected="selected"';
                        	}

                        	$select1 .= '>Relative to the parent element</option></select>';
                        	$select1 .= '<script type="text/javascript">
                                $("#' . $params['name'] . '_type").selectmenu({
                                    change: function(event, ui) {
                                        $(".' . $params['name'] . '_font_size").each(function( i ) {
                                            $(this).slideUp();
                                        })
                                        $("#' . $params['name'] . '_"+ui.item.value).slideDown();
                                    }
                                });
                                </script>';
                        	$select2 = '<select class="' . $params['name'] . '_font_size" name="' . $params['name'] . '_px" id="' . $params['name'] . '_px"';

                        	if ($suffix != 'px') {
                            	$select2 .= ' style="display:none"';
                        	}

                        	$select2 .= '>';

                        	for ($i = 7; $i < 25; $i++) {
                            	$select2 .= '<option value="' . $i . '"';

                            	if ($fields_value == $i . 'px') {
                                	$select2 .= ' selected="selected"';
                            	}

                            	$select2 .= '>' . $i . ' px</option>';
                        	}

                        	$select2 .= '</select>';
                        	$select2 .= '<script type="text/javascript">
                            $("' . $params['name'] . '_px").selectmenu({
                                    classes: {
                                        "ui-selectmenu-menu": "scrollable"
                                    }
                            });
                        	</script>';
							$select2 .= '<select class="' . $params['name'] . '_font_size" name="' . $params['name'] . '_vw" id="' . $params['name'] . '_vw"';

                        	if ($suffix != 'vw') {
                            	$select2 .= ' style="display:none"';
                        	}

                        	$select2 .= '>';

                        	for ($i = 0.1; $i < 2; $i = $i + 0.1) {
                            	$select2 .= '<option value="' . $i . '"';

                            	if ($fields_value == $i . 'vw') {
                                	$select2 .= ' selected="selected"';
                            	}

                            	$select2 .= '>' . $i . ' vw</option>';
                        	}

                        	$select2 .= '</select>';
                        	$select2 .= '<script type="text/javascript">
                            $("' . $params['name'] . '_vw").selectmenu({
                                    classes: {
                                        "ui-selectmenu-menu": "scrollable"
                                    }
                            });
                        	</script>';
                        	$select2 .= '<select class="' . $params['name'] . '_font_size" name="' . $params['name'] . '_em" id="' . $params['name'] . '_em"';

                        	if ($suffix != 'em') {
                            	$select2 .= ' style="display:none"';
                        	}

                        	$select2 .= '>';

                        	for ($i = 0.1; $i < 2; $i = $i + 0.1) {
                            	$select2 .= '<option value="' . $i . '"';

                            	if ($fields_value == $i . 'em') {
                                	$select2 .= ' selected="selected"';
                            	}

                            	$select2 .= '>' . $i . ' em</option>';
                        	}

                        	$select2 .= '</select>';
                        	$select2 .= '<script type="text/javascript">
                            $("' . $params['name'] . '_em").selectmenu({
                                    classes: {
                                        "ui-selectmenu-menu": "scrollable"
                                    }
                            });
                        	</script>';
                        	$html = '<div class="form-group" style="line-height:40px">' . $select1 . '<div class="col-lg-2">' . $select2 . '</div></div>';
                        	$params['select_font_size'] = $html;
                        	break;
                    	case 'element_before':
                    	case 'element_after':
                        	$height = 0;
                        	$position = ['0px', '0px', '0px', '0px'];
							$radius = '0 0 0 0';
                        	$bck_type = '';
                        	$bck_value = 'rgb(255, 255, 255)-rgb(255, 255, 255)-100-%';
                        	$bck_color = 'rgb(255, 255, 255)';
							$border_color = 'rgb(255, 255, 255)';
                        	$top = 0;
                        	$right = 0;
                        	$bottom = 0;
                        	$left = 0;
							$border_size = '1px';

                        	if (!empty($this->fields_value[$params['name']])) {
                            	$fields_value = PhenyxTool::getInstance()->jsonDecode($this->fields_value[$params['name']]);
                            	$height = isset($fields_value->height) ? $fields_value->height : 0;
                            	$position = $fields_value->position;
                            	$bck_type = $fields_value->bck_type;

                            	if ($bck_type == 'bck') {
                                	$bck_color = $fields_value->bck_color;
                            	} else if ($bck_type == 'gradient') {
                                	$bck_value = $fields_value->bck_value;
                            	} else {
									$radius = explode(' ', $fields_value->radius);
									$border_color = $fields_value->border_color;
									$border_size = $fields_value->border_size;
								}
                        	}

                        	$keys = explode('-', $bck_value);
                        	$key_1 = !empty($keys[1]) ? $this->timberpress_rgb_to_hex($keys[1]) : '';
                        	$key_2 = !empty($keys[2]) ? $keys[2] : '';
                        	$key_3 = !empty($keys[3]) ? $keys[3] : '';
                        	$html = '<div class="form-group" style="height:100%">';
                        	$html .= '<div class="input-group col-lg-3" style="display: flex">
                                        <label style="display:block; line-height:40px">Hauteur</label>
                                        <input type="text"
                                            name="' . $params['name'] . '"
                                            id="' . $params['name'] . '"
                                            value="' . $height . '"
                                            style="width: 60px; text-align: right"
                                        />
                                        <span class="input-group-addon" style="line-height: 30px; font-size: large; margin-left: 10px;">px</span>
                                    </div>';
                        	$html .= '<div class="form-group position col-lg-3"><div class="vc_css-editor vc_row">
                            <div class="vc_layout-onion vc_col-xs-7">
                            <div class="vc_margin">
                            <label>Position</label>
                            <input type="text" name="' . $params['name'] . '_top" data-name="margin-top" class="vc_top" placeholder="-" data-attribute="margin" value="' . $position[0] . '">
                            <input type="text" name="' . $params['name'] . '_right" data-name="margin-right" class="vc_right" placeholder="-" data-attribute="margin" value="' . $position[1] . '">
                            <input type="text" name="' . $params['name'] . '_bottom" data-name="margin-bottom" class="vc_bottom" placeholder="-" data-attribute="margin" value="' . $position[2] . '">
                            <input type="text" name="' . $params['name'] . '_left" data-name="margin-left" class="vc_left" placeholder="-" data-attribute="margin" value="' . $position[3] . '">
                            </div>
                            </div>
                            </div>';
                        	$html .= '</div>';
                        	$html .= '<div class="input-group col-lg-3" style="margin-left: 50px;">';
                        	$html .= '<select name="' . $params['name'] . '_bck_type" id="' . $params['name'] . '_bck_type">';
                        	$html .= '<option value="">--Type de BackGround*--</option><option value="bck"';

                        	if ($bck_type == 'bck') {
                            	$html .= ' selected="selected"';
                        	}

                        	$html .= '>Background color</option><option value="gradient"';

                        	if ($bck_type == 'gradient') {
                            	$html .= ' selected="selected"';
                        	}

                        	$html .= '>Gradiant color</option><option value="border"';

                        	if ($bck_type == 'border') {
                            	$html .= ' selected="selected"';
                        	}

                        	$html .= '>Border</option></select>';
                        	$html .= '<script type="text/javascript">
                            $("#' . $params['name'] . '_bck_type").selectmenu({
                                change: function(event, ui) {
                                    $(".' . $params['name'] . '_bck_type").each(function( i ) {
                                            $(this).slideUp();
                                        })
                                        $("#' . $params['name'] . '_info").slideUp();
                                        $("#' . $params['name'] . '_"+ui.item.value).css("display", "flex");
                                    }
                            });
                        	</script>';
                        	$html .= '</div>';
                        	$html .= '<div class="col-lg-12 ' . $params['name'] . '_bck_type" id="' . $params['name'] . '_bck" style="margin-top:60px; ';

                        	if ($bck_type != 'bck') {
                            	$html .= 'display:none;';
                        	} else {
                            	$html .= 'display:flex;';
                        	}

                        	$html .= '"><label style="display:block; line-height:40px">Couleur</label><div class="col-lg-2">

                                <input data-hex="true" type="text" name="' . $params['name'] . '_bck" id="' . $params['name'] . '_0" value="' . $this->timberpress_rgb_to_hex($bck_color) . '" class="pm_colorpicker" />
                                </div>
                                ';
                        	$html .= '</div>';
							$html .= '<div class="col-lg-12 ' . $params['name'] . '_bck_type" id="' . $params['name'] . '_border" style="margin-top:60px; ';

                        	if ($bck_type != 'border') {
                            	$html .= 'display:none;';
                        	} else {
                            	$html .= 'display:flex;';
                        	}

                        	$html .= '"><label style="display:block; line-height:40px">Couleur</label><div class="col-lg-2">

                                <input data-hex="true" type="text" name="' . $params['name'] . '_border_color" value="' . $this->timberpress_rgb_to_hex($border_color) . '" class="pm_colorpicker" />
                                </div>
                                ';
							$html .= '<label style="display:block; line-height:40px; margin: 0 20px;">Taille</label>';
							$html .= '<select name="' . $params['name'] . '_border_size" id="' . $params['name'] . '_border_size">';
                        	for ($i = 1; $i < 40; $i++) {
                            	$html .= '<option value="' . $i . 'px"';
                            	if ($border_size == $i . 'px') {
                                	$html .= ' selected="selected"';
                            	}
                            	$html .= '>' . $i . ' px</option>';
                        	}
                        	$html .= '</select>';
							$html .= '<script type="text/javascript">
                             $("#' . $params['name'] . '_border_size").selectmenu({
                    			classes: {
                        			"ui-selectmenu-menu": "scrollable"
                    			}
                			});
                        	</script>';
							$html .= '<label style="display:block; line-height:40px; margin: 0 20px;">Border Radius</label><div id="' . $params['name'] . '_positions" class="form-group positions col-lg-4">
                            	<div class="vc_css-editor vc_row">
                            	<div class="vc_layout-onion vc_col-xs-7">
                            	<div class="vc_margin">
                            	<input type="text" name="' . $params['name'] . '_radius[]" data-name="margin-top" class="vc_top" placeholder="-" data-attribute="margin" value="' . $radius[0] . '">
                            	<input type="text" name="' . $params['name'] . '_radius[]" data-name="margin-right" class="vc_right" placeholder="-" data-attribute="margin" value="' . $radius[1] . '">
                            	<input type="text" name="' . $params['name'] . '_radius[]" data-name="margin-bottom" class="vc_bottom" placeholder="-" data-attribute="margin" value="' . $radius[2] . '">
                            	<input type="text" name="' . $params['name'] . '_radius[]" data-name="margin-left" class="vc_left" placeholder="-" data-attribute="margin" value="' . $radius[3] . '">
                            	</div>
                            	</div>
                            	</div>
                            	</div>';
                        	$html .= '</div>';
                        	$html .= '<div class="col-lg-12 ' . $params['name'] . '_bck_type" id="' . $params['name'] . '_gradient" style="margin-top:60px; ';

                        	if ($bck_type != 'gradient') {
                            	$html .= 'display:none;';
                        	} else {
                            	$html .= 'display:flex;';
                        	}

                        	$html .= '"><label style="display:block; line-height:40px">Gradient</label><div class="col-lg-2">

                                <input data-hex="true" type="text" name="' . $params['name'] . '_bck1" id="' . $params['name'] . '_0" value="' . $this->timberpress_rgb_to_hex($keys[0]) . '" class="pm_colorpicker" />
                                </div>&nbsp;
                                <div class="col-lg-2" id="' . $params['name'] . '_gradient">
                                <input data-hex="true" type="text" name="' . $params['name'] . '_bck2" id="' . $params['name'] . '_1" value="' . $key_1 . '" class="pm_colorpicker" placeholder="optionnel" /></div><label class="control-label" style="line-height: 40px; display:block">Profondeur</label>
                                <div class="col-lg-2" id="' . $params['name'] . '_gradient">
                                <input type="text" name="' . $params['name'] . '_gradient" id="' . $params['name'] . '_1" value="' . $key_2 . '" placeholder="optionnel" /></div>
                                <div class="col-lg-2" id="' . $params['name'] . '_gradient">
                                <select name="' . $params['name'] . '_type" id="' . $params['name'] . '_type">
                                <option value="">Choisir le tupe de propagation</option>
                                <option value="%"';

                        	if ($key_3 == '%') {
                            	$html .= ' selected="selected"';
                        	}

                        	$html .= '>Percent</option><option value="px"';

                        	if ($key_3 == 'px') {
                            	$html .= ' selected="selected"';
                        	}

                        	$html .= '>px</option></select></div>';
                        	$html .= '<script type="text/javascript">
                                $("' . $params['name'] . '_type").selectmenu();
                            </script>';
                        	$html .= '</div><label id="' . $params['name'] . '_info" class="col-lg-12" style="display:block; line-height:40px;">*Laisser vide pour désactiver</label></div>';
                        	$params['element_before'] = $html;
                        	break;
                    	case 'padding':
                    	case 'margin':
                    	case 'border_size':
						case 'border_radius':
                        	$positions = '0 0 0 0';

                        	if (!empty($this->fields_value[$params['name']])) {
                            	$keys = explode(' ', $this->fields_value[$params['name']]);
                        	} else {
                            	$keys = explode(' ', $params['default_val']);
                        	}

                        	$html = '<div id="' . $params['name'] . '_positions" class="form-group positions col-lg-6">
                            	<div class="vc_css-editor vc_row">
                            	<div class="vc_layout-onion vc_col-xs-7">
                            	<div class="vc_margin">
                            	<label>Position</label>
                            	<input type="text" name="' . $params['name'] . '[]" data-name="margin-top" class="vc_top" placeholder="-" data-attribute="margin" value="' . $keys[0] . '">
                            	<input type="text" name="' . $params['name'] . '[]" data-name="margin-right" class="vc_right" placeholder="-" data-attribute="margin" value="' . $keys[1] . '">
                            	<input type="text" name="' . $params['name'] . '[]" data-name="margin-bottom" class="vc_bottom" placeholder="-" data-attribute="margin" value="' . $keys[2] . '">
                            	<input type="text" name="' . $params['name'] . '[]" data-name="margin-left" class="vc_left" placeholder="-" data-attribute="margin" value="' . $keys[3] . '">
                            	</div>
                            	</div>
                            	</div>
                            	</div>';
                        	$params['4size'] = $html;
                        	break;
						case 'text_shadow':
						case 'box_shadow':
							if (!empty($this->fields_value[$params['name']])) {
                            	$keys = explode(' ', $this->fields_value[$params['name']]);
								$value = str_replace('rgba(', '', $keys[3]);
								$value = str_replace(')', '', $value);                        
                        	} else {
                            	$keys = explode(' ', $params['default_val']);
								$value = str_replace('rgba(', '', $keys[3]);
                            	$value = str_replace(')', '', $value);
                        	}
							$colors = explode(',', $value);
							$rvb = 'rvb(' . $colors[0] . ',' . $colors[1] . ',' . $colors[2] . ')';
                        	$hex = $this->timberpress_rgb_to_hex($rvb);
							$html = '<div id="' . $params['name'] . '_positions" class="form-group col-lg-12">
                            
                            <input type="text" name="' . $params['name'] . '[]" data-name="margin-top"  placeholder="-" data-attribute="margin" value="' . $keys[0] . '">
                            <input type="text" name="' . $params['name'] . '[]" data-name="margin-right"  placeholder="-" data-attribute="margin" value="' . $keys[1] . '">
                            <input type="text" name="' . $params['name'] . '[]" data-name="margin-bottom"  placeholder="-" data-attribute="margin" value="' . $keys[2] . '">
                            <input type="text" data-hex="true" class="pm_colorpicker" name="' . $params['name'] . '[]" value="' . $hex . '" />
                           
                            ';
							$html .= '<div class="pm_slider">
                        	<input type="hidden" id="' . $params['name'] . '" name="' . $params['name'] . '[]" value="' . $colors[3] . '" />
                            <div id="slider-' . $params['name'] . '"></div>&nbsp;&nbsp;&nbsp;&nbsp;
                            	<em id="slider-suffix-' . $params['name'] . '">' . $colors[3] . ' %</em>
                                <script type="text/javascript">
                                      $(function() {
                                          $("#slider-' . $params['name'] . '").slider({
                                              range: false,
                                              min: 0,
                                              max: 1,
                                              step: 0.01,
                                              value: $("#' . $params['name'] . '").val(),
                                              slide: function(event, ui) {
                                              	$("#' . $params['name'] . '").val(ui.value);
                                              	$("#slider-suffix-' . $params['name'] . '").html(ui.value + " %");
                                            	}
											});
                                            $("#slider-' . $params['name'] . '").slider("value", $("#' . $params['name'] . '").val());
                                       });
                                </script>
                                </div></div>';
							$params['text_shadow'] = $html;
							break;
                        case 'slider':
                            if (!empty($this->fields_value[$key])) {
                    	       $fields_value = $this->fields_value[$key];
                            } else {
                    	       $fields_value = 1;
                            }
                            $html = '<div class="pm_slider">
                        	<input type="hidden" id="' . $params['name'] . '" name="' . $params['name'] . '[]" value="' .$fields_value . '" />
                            <div id="slider-' . $params['name'] . '"></div>&nbsp;&nbsp;&nbsp;&nbsp;
                            	<em id="slider-suffix-' . $params['name'] . '">' . $fields_value . ' %</em>
                                <script type="text/javascript">
                                      $(function() {
                                          $("#slider-' . $params['name'] . '").slider({
                                              range: false,
                                              min: '.$params['min'].',
                                              max: '.$params['max'].',
                                              step:'.$params['step'].',
                                              value: $("#' . $params['name'] . '").val(),
                                              slide: function(event, ui) {
                                              	$("#' . $params['name'] . '").val(ui.value);
                                              	$("#slider-suffix-' . $params['name'] . '").html(ui.value + " %");
                                            	}
											});
                                            $("#slider-' . $params['name'] . '").slider("value", $("#' . $params['name'] . '").val());
                                       });
                                </script>
                                </div></div>';
							$params['slider'] = $html;
							break;
                    	case 'contener_position':
							$form_group_id = $params['form_group_id'];
                        	$positions = ['0', '0', '0', '0'];
                        	$options = ['absolute', 'fixed', 'relative', 'static', 'sticky'];

                        	if (!empty($this->fields_value[$params['name']])) {
                            	$fields_value = PhenyxTool::getInstance()->jsonDecode($this->fields_value[$params['name']]);
                            	$position = $fields_value->position;
                            	$positions = $fields_value->positions;
                        	} else {
                            	$position = 'relative';
                        	}

                        	$html = '<div class="form-group">';
                        	$html .= '<div class="input-group col-lg-6" style="display: flex">';
                        	$html .= '<select name="' . $params['name'] . '" id="' . $params['name'] . '_option">';

                        	foreach ($options as $option) {
                            	$html .= '<option value="' . $option . '"';
                            	if ($option == $position) {
                                	$html .= ' selected="selected"';
                            	}
                            	$html .= '>' . $option . '</option>';
                        	}

                        	$html .= '</select>';
                        	$html .= '<script type="text/javascript">
                            var fields_value = "' . $position . '";
                            if(fields_value == "absolute" || fields_value == "fixed") {
                                $("#' . $params['name'] . '_positions").slideDown();
								$("#' . $form_group_id . '").addClass("needPosition");
                            }
                            $("#' . $params['name'] . '_option").selectmenu({
                                change: function(event, ui) {
                                    if(ui.item.value == "absolute" || ui.item.value == "fixed") {
                                        $("#' . $params['name'] . '_positions").slideDown();
										$("#' . $form_group_id . '").addClass("needPosition");
                                    } else {
                                        $("#' . $params['name'] . '_positions").slideUp();
										$("#' . $form_group_id . '").removeClass("needPosition");
                                    }
                                }
                            });
                        	</script>';
                        	$html .= '</div>';
                        	$html .= '<div id="' . $params['name'] . '_positions" class="form-group positions col-lg-6" style="display:none">
                            <div class="vc_css-editor vc_row">
                            <div class="vc_layout-onion vc_col-xs-7">
                            <div class="vc_margin">
                            <label>Position</label>
                            <input type="text" name="' . $params['name'] . '_top" data-name="margin-top" class="vc_top" placeholder="-" data-attribute="margin" value="' . $positions[0] . '">
                            <input type="text" name="' . $params['name'] . '_right" data-name="margin-right" class="vc_right" placeholder="-" data-attribute="margin" value="' . $positions[1] . '">
                            <input type="text" name="' . $params['name'] . '_bottom" data-name="margin-bottom" class="vc_bottom" placeholder="-" data-attribute="margin" value="' . $positions[2] . '">
                            <input type="text" name="' . $params['name'] . '_left" data-name="margin-left" class="vc_left" placeholder="-" data-attribute="margin" value="' . $positions[3] . '">
                            </div>
                            </div>
                            </div>';
                        	$html .= '</div></div>';
                        	$params['contener_position'] = $html;
                        	break;
                    	case 'contener_border':
							$options = ['border', 'border-top', 'border-right', 'border-bottom', 'border-left', 'none'];
							$type = 'none';
							$styles = ['solid', 'dashed', 'double', 'groove', 'ridge', 'none'];
							$style = 'solid';
							$size = '1px';
							$color = '#ffffff';
							if (!empty($this->fields_value[$params['name']])) {
                            	$fields_value = PhenyxTool::getInstance()->jsonDecode($this->fields_value[$params['name']]);
								$type = $fields_value->type;
                                if(isset($fields_value->style)) {
                                    $style = $fields_value->style;
                                }   
                                if(isset($fields_value->size)) {
                                    $style = $fields_value->size;
                                }   
                                if(isset($fields_value->color)) {
                                    $style = $fields_value->color;
                                }  
                        	} else {
                            	$position = 'relative';
                        	}
							$html = '<div class="form-group">';
							$html .= '<div class="input-group col-lg-3" style="display: flex">';
                        	$html .= '<select name="' . $params['name'] . '" id="' . $params['name'] . '_type">';
                        	foreach ($options as $option) {
                            	$html .= '<option value="' . $option . '"';
                            	if ($option == $type) {
                                	$html .= ' selected="selected"';
                            	}
                            	$html .= '>' . $option . '</option>';
                        	}
                        	$html .= '</select>';
							$html .= '<script type="text/javascript">
                            var fields_value = "' . $type . '";
                            if(fields_value != "none") {
                                $("#' . $params['name'] . '_style").slideDown();
                            }
                            $("#' . $params['name'] . '_type").selectmenu({
								classes: {
                        			"ui-selectmenu-menu": "scrollable"
                    			},
                                change: function(event, ui) {
                                    if(ui.item.value != "none") {
                                        $("#' . $params['name'] . '_style").slideDown();
                                    } else {
                                        $("#' . $params['name'] . '_style").slideUp();
                                    }
                                }
                            });
                        	</script>';
							$html .= '</div>';
                        	$html .= '<div id="' . $params['name'] . '_style" class="form-group col-lg-9" style="display:none">';
							$html .= '<div class="form-group col-lg-3">';
							$html .= '<select name="' . $params['name'] . '_size" id="' . $params['name'] . '_border_size">';
                        	for ($i = 1; $i < 40; $i++) {
                            	$html .= '<option value="' . $i . 'px"';
                            	if ($size == $i . 'px') {
                                	$html .= ' selected="selected"';
                            	}
                            	$html .= '>' . $i . ' px</option>';
                        	}
                        	$html .= '</select>';
							$html .= '<script type="text/javascript">
                             $("#' . $params['name'] . '_border_size").selectmenu({
                    			classes: {
                        			"ui-selectmenu-menu": "scrollable"
                    			}
                			});
                        	</script></div>';
							$html .= '<div class="form-group col-lg-3">';
							$html .= '<select name="' . $params['name'] . '_border_type" id="' . $params['name'] . '_border_type">';
                        	foreach ($styles as $option) {
                            	$html .= '<option value="' . $option . '"';

                            	if ($option == $style) {
                                	$html .= ' selected="selected"';
                            	}

                            	$html .= '>' . $option . '</option>';

                        	}
                        	$html .= '</select>';
							$html .= '<script type="text/javascript">
                            	$("#' . $params['name'] . '_border_type").selectmenu({
                    				classes: {
                        				"ui-selectmenu-menu": "scrollable"
                    				}
                				});
                        	</script></div>';
							$html .= '<div class="form-group col-lg-3">';
							$html .= '<input data-hex="true" type="text" name="' . $params['name'] . '_color" id="' . $params['name'] . '_color" value="' . $color . '" class="pm_colorpicker" />';
							$html .= '</div></div></div>';
							$params['contener_border'] = $html;
                        	break;
                    	case 'background_size':
                        	$options = ['auto', 'contain', 'cover', 'custom'];
							$width = '';
                        	$height = '';

                        	if (!empty($this->fields_value[$params['name']])) {
								$keys = explode(' ', $this->fields_value[$params['name']]);

                            	if (isset($keys[1])) {
                                	$fields_value = 'custom';
                               	 	$width = $keys[0];
                                	$height = $keys[1];
                            	} else {
                                	$fields_value = $keys[0];
                            	}

                        	} else {
                            	$fields_value = 'contain';
                        	}

                        	$html = '<div class="form-group">';
                        	$html .= '<div class="input-group col-lg-3" style="display: flex">';
							
                        	$html .= '<select name="' . $params['name'] . '" id="' . $params['name'] . '_option">';

                        	foreach ($options as $option) {
                            	$html .= '<option value="' . $option . '"';
                            	if ($option == $fields_value) {
                                	$html .= ' selected="selected"';
                            	}
                            	$html .= '>' . $option . '</option>';
                        	}

                        	$html .= '</select>';
                        	$html .= '<script type="text/javascript">
                            var fields_value = "' . $fields_value . '";
                            if(fields_value != "custom") {
                                $("#' . $params['name'] . '_custom_size").slideUp();
                            }
                            $("#' . $params['name'] . '_option").selectmenu({
                                change: function(event, ui) {
                                    if(ui.item.value == "custom") {
                                        $("#' . $params['name'] . '_custom_size").slideDown();
                                    } else {
                                        $("#' . $params['name'] . '_custom_size").slideUp();
                                    }
                                }
                            });
                        	</script>';
                        	$html .= '</div>';
                        	$html .= '<div id="' . $params['name'] . '_custom_size" class="col-lg-6" style="display: flex"><div class="input-group col-lg-6">';
                        	$html .= '<input type="text" name="' . $params['name'] . '_width" placeholder="largeur" id="' . $params['name'] . '_width" value="' . $width . '"  />';
                        	$html .= '</div>';
                        	$html .= '<div class="input-group col-lg-6">';
                        	$html .= '<input type="text" name="' . $params['name'] . '_height" placeholder="hauteur" id="' . $params['name'] . '_height" value="' . $height . '"  />';
                        	$html .= '</div></div>';
                        	$html .= '</div>';
                        	$params['background_size'] = $html;
                        	break;
						case 'background_position':
                        	$options = ['left top', 'left center', 'left bottom', 'right top', 'right center', 'right bottom', 'center top', 'center center', 'center bottom', 'custom'];
                        	$width = '';
                        	$height = '';

                        	if (!empty($this->fields_value[$params['name']])) {
                            	$keys = explode(' ', $this->fields_value[$params['name']]);

                            	if (isset($keys[1])) {
                                	$fields_value = 'custom';
                                	$width = $keys[0];
                                	$height = $keys[1];
                            	} else {
                                	$fields_value = $keys[0].' '.$keys[1];
                            	}
                        	} else {
                            	$fields_value = 'center center';
                        	}
                        	$html = '<div class="form-group">';
                        	$html .= '<div class="input-group col-lg-3" style="display: flex">';
                        	$html .= '<select name="' . $params['name'] . '" id="' . $params['name'] . '_option">';

                        	foreach ($options as $option) {
								$html .= '<option value="' . $option . '"';
                            	if ($option == $fields_value) {
                                	$html .= ' selected="selected"';
                            	}
                            	$html .= '>' . $option . '</option>';
							}

                        	$html .= '</select>';
                        	$html .= '<script type="text/javascript">
                            var fields_value = "' . $fields_value . '";
                            if(fields_value != "custom") {
                                $("#' . $params['name'] . '_custom_position").slideUp();
                            }
                            $("#' . $params['name'] . '_option").selectmenu({
                                change: function(event, ui) {
                                    if(ui.item.value == "custom") {
                                        $("#' . $params['name'] . '_custom_position").slideDown();
                                    } else {
                                        $("#' . $params['name'] . '_custom_position").slideUp();
                                    }
                                }
                            });
                        	</script>';
                        	$html .= '</div>';
                        	$html .= '<div id="' . $params['name'] . '_custom_position" class="col-lg-6" style="display: flex"><div class="input-group col-lg-6">';
                        	$html .= '<input type="text" name="' . $params['name'] . '_width" placeholder="largeur" id="' . $params['name'] . '_width" value="' . $width . '"  />';
                        	$html .= '</div>';
                        	$html .= '<div class="input-group col-lg-6">';
                        	$html .= '<input type="text" name="' . $params['name'] . '_height" placeholder="hauteur" id="' . $params['name'] . '_height" value="' . $height . '"  />';
                        	$html .= '</div></div>';
                        	$html .= '</div>';
							$params['background_position'] = $html;
                        	break;
           				case 'theme_manager':
							$fields = false;					
							$xprt = $this->tpl_vars['xprt'];
							$html = '<div class="form-group">';
							$html .= '<div id="dragConteneur" class="col-lg-12">';
                        	$html .= '<div class="input-group col-lg-12">';
							if($params['templates']) {
								$radio = [];
								foreach($params['templates'] as $key => $values) {
									foreach($values as $value) {
										if($value['fields']) {
											$fields = true;
										}
										$radio[] = $value['key'];
										$html .= '<div class="radio_layout radio_layout-'.$value['class'].'">
										<span></span>
										<label>
										<input type="radio" name="'.$key.'" id="left" value="'.$value['key'].'"';
										if ($xprt[$key] == $value['key']) {
                               				$html .= ' checked="checked"';
                            			}
										$html .= '>'.$value['name'].'</label></div>';
									}
								}		
								if($fields) {								
                        			$html .= '<script type="text/javascript">
                           			$("input[type=radio][name='.$key.']").on("change", function() {
						   				if (this.value == "'.$radio[0].'") {
        									$("#snaptarget_'.$radio[0].'").slideDown();
											$("#snaptarget_'.$radio[1].'").slideUp();
    									} else  {
											$("#snaptarget_'.$radio[0].'").slideUp();
											$("#snaptarget_'.$radio[1].'").slideDown();
    									}
									});
                        			</script>';
                        			$html .= '</div>';
									foreach($params['templates'] as $key => $values) {
									foreach($values as $value) {
										$html .= '<div id="snaptarget_'.$value['key'].'" class="ui-widget-header '.$value['key'].'"';
										if($xprt[$key] != $value['key']) {
											$html .= ' style="display:none"';
										}
										$html .= '>';
										$fields = $value['box'];
										foreach($fields as $box) {
											$drag = isset($box['drag']) ? $box['drag'] : false;
											if($drag) {
												$class = "draggable";
											} else {
												$class = "no-draggable";
											}
											if($box['type'] == 'bootstrap_conteneur') {
												$html .= '<fieldset class="col-lg-12">';
												$html .= '<legend>' . $box['name'] . '</legend>';
												foreach($box['fields'] as $field) {
													$html .= '<div class="form-group">';
													$html .= '<label class="col-lg-5">' . $field['name'] . '</label><div class="col-lg-6"><input type="text" name="' . $params['name'] .'_'.$field['id'] . '" value="'.$xprt[$params['name'] .'_'.$field['id']].'"  /></div>';
													$html .= '</div>';
												}
												$html .= '</fieldset>';
											}
											if($box['type'] == 'block') {
												$html .= '<div class="snaptarget" style="background:'.$xprt['phenyxIndex_bck_color'].';">';
												$html .= '<div id="'.$box['id'].'"  class="'.$class.' ui-widget-content">';
												$html .= $box['name'].'<br>';
												$html .= $box['content'];
												if(isset($box['drag']) && $box['drag']) {
													$html .= '<div class="dragHandle"><i class="fa fa-arrows-alt fa-2x"></i></div>';
												}									
												$html .= '</div>';
												$html .= '</div>';
											}
										}
										$html .= '</div>';
									}
								}
								} else {
									$html .= '</div>';
								}
							}
							
							$html .= '</div>';
							$html .= '</div>';
							$params['theme_manager'] = $html;
                        	break;
                            
                    }
                    
                    $extras = $this->context->_hook->exec('extraHelperFormOptions', ['param'=> $params, 'fields_value' => $this->fields_value], null, true);
                    
                    if (is_array($extras)) {
                        
                        foreach ($extras as $plugin => $values) {
                            if (is_array($values)) {
                                foreach($values as $key => $value) {
                                    
                                    if($key == 'smarty' && is_array($value)) {
                                        foreach($value as $k => $v)  {
                                             $this->tpl->assign($k, $v);
                                             $params[$k] = $v;
                                        }
                                    } else {
                                        $params[$key] = $value;
                                    }
                                    
                                }
                            }
                        }
                    }
                }

            }

        }
        
        if (($this->_back_css_cache || $this->_back_js_cache) && is_writable(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {

            if ($this->_back_css_cache) {
                $this->form_extraCss = $this->context->media->admincccCss($this->form_extraCss);
            }

            if ($this->_back_js_cache) {
                $this->form_extraJs = $this->context->media->admincccJS($this->form_extraJs);
            }

        }
        
        
        $this->tpl->assign(
            [
                'title'                 => $this->title,
                'toolbar_btn'           => $this->toolbar_btn,
                'show_toolbar'          => $this->show_toolbar,
                'header_title'          => $this->header_title,
                'toolbar_scroll'        => $this->toolbar_scroll,
                'submit_action'         => $this->submit_action,
                'firstCall'             => $this->first_call,
                'current'               => $this->currentIndex,
                'link'                  => $this->context->_link,
                'table'                 => $this->table,
				'className'             => $this->className,
                'identifier'            => $this->identifier,
                'name_controller'       => $this->name_controller,
                'controller'            => $this->controllerName,
                'languages'             => $this->languages,
                'current_id_lang'       => $this->context->language->id,
                'defaultFormLanguage'   => $this->default_form_language,
                'allowEmployeeFormLang' => $this->allow_employee_form_lang,
                'form_id'               => $this->id,
                'tabs'                  => (isset($tabs)) ? $tabs : null,
                'topFields'             => $this->fields_form['input'],
                'steps'                 => $this->fields_form['steps'],
                'labels'                => ['next' => $this->l('Next'), 'previous' => $this->l('Previous'), 'finishAndStay' => $this->l('Save and Stay'), 'finish' => $this->l('Save')],
                'nbSteps'               => count($this->fields_form['steps']),
                'fields_value'          => $this->fields_value,
                'required_fields'       => $this->getFieldsRequired(),
                'vat_number'            => Plugin::isInstalled('vatnumber') && file_exists(_EPH_PLUGIN_DIR_ . 'vatnumber/ajax.php'),
                'plugin_dir'            => _PLUGIN_DIR_,
                'base_url'              => $this->context->company->getBaseURL(),
                'contains_states'       => (isset($this->fields_value['id_country']) && isset($this->fields_value['id_state'])) ? Country::containsStates($this->fields_value['id_country']) : null,
                'show_cancel_button'    => $this->show_cancel_button,
                'back_url'              => $this->back_url,
                'extraCss'              => $this->form_extraCss,
                'extraJs'               => $this->form_extraJs,
                'js_def'                => $this->js_def,
                'has_editor'            => $this->has_editor
            ]
        );

        $this->tpl->assign($this->tpl_vars);

        return $this->tpl->fetch();
    }

    public function timberpress_rgb_to_hex($color) {

        $pattern = "/(\d{1,3})\,?\s?(\d{1,3})\,?\s?(\d{1,3})/";

        // Only if it's RGB

        if (preg_match($pattern, $color, $matches)) {
            $r = $matches[1];
            $g = $matches[2];
            $b = $matches[3];

            $color = sprintf("#%02x%02x%02x", $r, $g, $b);
        }

        return $color;
    }

    /**
     * Return true if there are required fields
     *
     * @return bool
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getFieldsRequired() {

        foreach ($this->fields_form as $fieldset) {

            if (isset($fieldset['form']['input'])) {

                foreach ($fieldset['form']['input'] as $input) {

                    if (!empty($input['required']) && $input['type'] != 'radio') {
                        return true;
                    }

                }

            }

        }

        return false;
    }
    
    public function generateCategoryGridScript($category_tree, $radio = true) {

		$paragrid = new ParamGrid('AsssociatedCategory', 'AdminAssociatedCategory', $this->table, $this->identifier);
		$paragrid->heightModel = 500;
		$paragrid->showNumberCell = 0;
		$paragrid->showTitle = 1;
        $paragrid->title =  '\'' . $this->l('Parent Category') . '\'';
		$paragrid->selectionModelType = 'cell';
		$paragrid->needRequestModel = false;
		$paragrid->dataModel  = [
			'data' => $category_tree
		];
		$paragrid->colModel = $this->getCategoryTreeFields();
        
		$paragrid->check = 'function(evt, ui) {
			$("#id_parent").val(ui.rows[0].rowData.id_category);
        }';
        
		$paragrid->filterModel = [
            'on'     => true,
            'mode'   => '\'AND\'',
            'header' => true,
        ];
        $paragrid->treeModel = [
            'dataIndx'     => '\'name\'',
            'id'           => '\'id_category\'',
            'checkbox'     => 1,
			'filterShowChildren' => 1,
            'icons'        => 0,
           // 'iconCollapse' => ['\'fa fa-circle-minus toggeCategoryClose\'', '\'fa fa-circle-plus toggeCategoryOpen\''],
        ];
		if($radio) {
			$paragrid->treeModel['maxCheck'] = 1;
		}
		
        $paragrid->gridFunction = [
			'neutralFunction()'                  => ''
		];

        $option = $paragrid->generateParaGridOption();
		$script = $paragrid->generateParagridScript();
		return '<script type="text/javascript">' . PHP_EOL . $script . PHP_EOL . '</script>';
    }
	
	public function getCategoryTreeFields() {

        return PhenyxTool::getInstance()->jsonEncode([

			[
                
                'dataIndx' => 'pq_tree_cb',
                'editable' => true,
                'hidden'   => true,
            ],
            [
                'title'    => $this->l('ID'),
                'dataIndx' => 'id_category',
                'dataType' => 'integer',
                'hidden'   => true,
            ],

            [
                'title'      => $this->l('Name'),
                'width'      => 300,
                'dataIndx'   => 'name',
                'align'      => 'left',
                'valign'     => 'center',
                'dataType'   => 'string',
            ],
           
        ]);

    }

    public function generateTabScript($controller) {

        return '<script type="text/javascript">' . PHP_EOL . '
                    $(document).ready(function(){' . PHP_EOL . '
                        $( "#content_' . $controller . '").tabs({' . PHP_EOL . '
                            show: { effect: "blind", duration: 800 },' . PHP_EOL . '
                        });' . PHP_EOL . '
                    });' . PHP_EOL . '
                </script>' . PHP_EOL;
    }

    

}
