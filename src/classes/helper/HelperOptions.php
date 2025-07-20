<?php

/**
 * Use this helper to generate preferences forms, with values stored in the configuration table
 *
 * @since 2.1.0.0
 */
class HelperOptions extends Helper {

    /** @var bool $required */
    public $required = false;
    /** @var int $id */
    public $id;
    
    public $has_editor;

    public $isParagrid = false;

    /**
     * HelperOptionsCore constructor.
     *
     * @since 2.1.0.0
     */
    public function __construct() {

        $this->base_folder = 'helpers/options/';
        $this->base_tpl = 'options.tpl';
        parent::__construct();
    }

    /**
     * Generate a form for options
     *
     * @param array $optionList
     *
     * @return string html
     *
     * @throws Exception
     * @throws HTMLPurifier_Exception
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @throws SmartyException
     * @since 2.1.0.0
     */
     public function generateOptions($optionList) {
       
        if ($this->isParagrid) {
            $this->base_tpl = 'options-grid.tpl';
        }

        $this->tpl = $this->createTemplate($this->base_tpl);
        $tab = BackTab::getTab($this->context->language->id, $this->id);

        if (!isset($languages)) {
            $languages = Language::getLanguages(false);
        }

        foreach ($optionList as $category => &$categoryData) {

            if (!is_array($categoryData)) {
                continue;
            }

            if (!isset($categoryData['image'])) {
                $categoryData['image'] = '';
            }

            if (!isset($categoryData['fields'])) {
                $categoryData['fields'] = [];
            }

            $categoryData['hide_multishop_checkbox'] = true;

            if (isset($categoryData['tabs'])) {
                $tabs[$category] = $categoryData['tabs'];
                $tabs[$category]['misc'] = $this->la('Miscellaneous');
            }

            foreach ($categoryData['fields'] as $key => &$field) {
                
                // Set field value unless explicitly denied

                if (!isset($field['auto_value']) || $field['auto_value']) {
                    $field['value'] = $this->getOptionValue($key, $field);
                }

                // Check if var is invisible (can't edit it in current shop context), or disable (use default value for multishop)
                $isDisabled = $isInvisible = false;

                $field['is_disabled'] = $isDisabled;
                $field['is_invisible'] = $isInvisible;

                $field['required'] = isset($field['required']) ? $field['required'] : $this->required;

                if ($field['type'] === 'color') {
                    $this->context->controller->addJqueryPlugin('colorpicker');
                }

                if ($field['type'] === 'textarea' || $field['type'] === 'textareaLang') {
                    $this->context->controller->addJqueryPlugin('autosize');
                    $iso = file_exists(_SHOP_ROOT_DIR_ . '/js/tinymce/langs/' . str_replace('-', '_', $this->context->language->language_code) . '.js') ? str_replace('-', '_', $this->context->language->language_code) : 'en';
                    $this->tpl->assign(
                        [
                            'iso'      => $iso,
                            'path_css' => $this->context->theme->css_theme,
                            'ad'       => __EPH_BASE_URI__ . basename(_EPH_ROOT_DIR_),
                        ]
                    );

                }

                if ($field['type'] === 'code') {
                    $this->context->controller->addJS(_EPH_JS_DIR_ .'ace/ace.js');
                }

                if ($field['type'] == 'tags') {
                    $this->context->controller->addJqueryPlugin('tagify');
                }

                if ($field['type'] == 'file') {
                    $uploader = new HelperUploader();
                    $uploader->setId(isset($field['id']) ? $field['id'] : null);
                    $uploader->setName($field['name']);
                    $uploader->setUrl(isset($field['url']) ? $field['url'] : null);
                    $uploader->setMultiple(isset($field['multiple']) ? $field['multiple'] : false);
                    $uploader->setUseAjax(isset($field['ajax']) ? $field['ajax'] : false);
                    $uploader->setMaxFiles(isset($field['max_files']) ? $field['max_files'] : null);

                    if (isset($field['files']) && $field['files']) {
                        $uploader->setFiles($field['files']);
                    } else
                    if (isset($field['image']) && $field['image']) {
                        // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'       => HelperUploader::TYPE_IMAGE,
                                    'image'      => isset($field['image']) ? $field['image'] : null,
                                    'size'       => isset($field['size']) ? $field['size'] : null,
                                    'delete_url' => isset($field['delete_url']) ? $field['delete_url'] : null,
                                ],
                            ]
                        );
                    }

                    if (isset($field['file']) && $field['file']) {
                        // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'         => HelperUploader::TYPE_FILE,
                                    'size'         => isset($field['size']) ? $field['size'] : null,
                                    'delete_url'   => isset($field['delete_url']) ? $field['delete_url'] : null,
                                    'download_url' => isset($field['file']) ? $field['file'] : null,
                                ],
                            ]
                        );
                    }

                    if (isset($field['thumb']) && $field['thumb']) {
                        // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'  => HelperUploader::TYPE_IMAGE,
                                    'image' => isset($field['thumb']) ? '<img src="' . $field['thumb'] . '" alt="' . $field['title'] . '" title="' . $field['title'] . '" />' : null,
                                ],
                            ]
                        );
                    }

                    $uploader->setTitle(isset($field['title']) ? $field['title'] : null);
                    $field['file'] = $uploader->render();
                }
                
                if ($field['type'] == 'day_range') {
                    if (!empty($this->fields_value[$key])) {
                        $fields_values = explode(',', $this->fields_value[$key]);
                        $fields_value1 = $fields_values[0];
                        $fields_value2 = $fields_values[1];
                    } else {
                         $fields_value1 = 1;
                        $fields_value2 = 15;
                    }
                    
                    $select1 = '<select id="' . $key . '_start" name="' . $key . '_start">';
                    for($i = 1; $i <32; $i++) {
                        $select1 .= '<option value="'.$i.'"';
                        if($i == $fields_value1) {
                            $select1 .= ' selected="selected"';
                        }
                    }
                    $select1 .= '>'.$i.'</option></select>';
                    
                    $select2 = '<select id="' . $key . '_end" name="' . $key . '_end">';
                    for($i = 1; $i <32; $i++) {
                        $select2 .= '<option value="'.$i.'"';
                        if($i == $fields_value2) {
                            $select2 .= ' selected="selected"';
                        }
                    }
                    $select2 .= '>'.$i.'</option></select>';
                    
                    $html = '<div class="form-group" style="line-height:40px"><div class="col-lg-6">' . $select1 . '</div><div class="col-lg-6">' . $select2 . '</div></div>';

                    $field['day_range'] = $html;
                    
                }

                if ($field['type'] == 'select_font_size') {

                    $suffix = '';

                    if (!empty($this->fields_value[$key])) {
                        $suffix = substr($this->fields_value[$key], -2);
                        $fields_value = $this->fields_value[$key];
                    } else {
                        $fields_value = $field['default_val'];
                    }

                    if (isset($value[1])) {
                        $suffix = $value[1];
                    }

                    $select1 = '<select id="' . $key . '_type" name="' . $key . '"><option value=""';

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
                                $("#' . $key . '_type").selectmenu({
                                    change: function(event, ui) {
                                        $(".' . $key . '_font_size").each(function( i ) {
                                            $(this).slideUp();
                                        })
                                        $("#' . $key . '_"+ui.item.value).slideDown();
                                    }
                                });
                                </script>';
                    $select2 = '<select class="' . $key . '_font_size" name="' . $key . '_px" id="' . $key . '_px"';

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
                            $("' . $key . '_px").selectmenu({
                                    classes: {
                                        "ui-selectmenu-menu": "scrollable"
                                    }
                            });
                            </script>';
                    $select2 .= '<select class="' . $key . '_font_size" name="' . $key . '_vw" id="' . $key . '_vw"';

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
                            $("' . $key . '_vw").selectmenu({
                                    classes: {
                                        "ui-selectmenu-menu": "scrollable"
                                    }
                            });
                            </script>';
                    $select2 .= '<select class="' . $key . '_font_size" name="' . $key . '_em" id="' . $key . '_em"';

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
                            $("' . $key . '_em").selectmenu({
                                    classes: {
                                        "ui-selectmenu-menu": "scrollable"
                                    }
                            });
                            </script>';
                    $html = '<div class="form-group" style="line-height:40px">' . $select1 . '<div class="col-lg-2">' . $select2 . '</div></div>';

                    $field['select_font_size'] = $html;

                }

                if ($field['type'] == 'gradient') {

                    if (!empty($this->fields_value[$key])) {
                        $fields_value = $this->fields_value[$key];
                    } else {
                        $fields_value = 1;
                    }

                    $html = '<div class="pm_slider">
                            <input type="hidden" id="' . $key . '" name="' . $key . '[]" value="' . $fields_value . '" />
                            <div id="slider-' . $key . '"></div>&nbsp;&nbsp;&nbsp;&nbsp;
                                <em id="slider-suffix-' . $key . '">' . $fields_value . ' %</em>
                                <script type="text/javascript">
                                      $(function() {
                                          $("#slider-' . $key . '").slider({
                                              range: false,
                                              min: ' . $field['min'] . ',
                                              max: ' . $field['max'] . ',
                                              step:' . $field['step'] . ',
                                              value: $("#' . $key . '").val(),
                                              slide: function(event, ui) {
                                                $("#' . $key . '").val(ui.value);
                                                $("#slider-suffix-' . $key . '").html(ui.value + " %");
                                                }
                                            });
                                            $("#slider-' . $key . '").slider("value", $("#' . $key . '").val());
                                       });
                                </script>
                                </div></div>';
                    $field['gradient'] = $html;

                }

                if ($field['type'] == 'contener_border') {

                    $options = ['border', 'border-top', 'border-right', 'border-bottom', 'border-left', 'none'];
                    $type = 'none';
                    $styles = ['solid', 'dashed', 'double', 'groove', 'ridge', 'none'];
                    $style = 'solid';
                    $size = '1px';
                    $color = '#ffffff';

                    if (!empty($this->fields_value[$key])) {
                        $fields_value = Tools::jsonDecode($this->fields_value[$key]);
                        $type = $fields_value->type;

                        if (isset($fields_value->style)) {
                            $style = $fields_value->style;
                        }

                        if (isset($fields_value->size)) {
                            $style = $fields_value->size;
                        }

                        if (isset($fields_value->color)) {
                            $style = $fields_value->color;
                        }

                    } else {
                        $position = 'relative';
                    }

                    $html = '<div class="form-group">';
                    $html .= '<div class="input-group col-lg-3" style="display: flex">';
                    $html .= '<select name="' . $key . '" id="' . $key . '_type">';

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
                                $("#' . $key . '_style").slideDown();
                            }
                            $("#' . $key . '_type").selectmenu({
                                classes: {
                                    "ui-selectmenu-menu": "scrollable"
                                },
                                change: function(event, ui) {
                                    if(ui.item.value != "none") {
                                        $("#' . $key . '_style").slideDown();
                                    } else {
                                        $("#' . $key . '_style").slideUp();
                                    }
                                }
                            });
                            </script>';
                    $html .= '</div>';
                    $html .= '<div id="' . $key . '_style" class="form-group col-lg-9" style="display:none">';
                    $html .= '<div class="form-group col-lg-3">';
                    $html .= '<select name="' . $key . '_size" id="' . $key . '_border_size">';

                    for ($i = 1; $i < 40; $i++) {
                        $html .= '<option value="' . $i . 'px"';

                        if ($size == $i . 'px') {
                            $html .= ' selected="selected"';
                        }

                        $html .= '>' . $i . ' px</option>';
                    }

                    $html .= '</select>';
                    $html .= '<script type="text/javascript">
                             $("#' . $key . '_border_size").selectmenu({
                                classes: {
                                    "ui-selectmenu-menu": "scrollable"
                                }
                            });
                            </script></div>';
                    $html .= '<div class="form-group col-lg-3">';
                    $html .= '<select name="' . $key . '_border_type" id="' . $key . '_border_type">';

                    foreach ($styles as $option) {
                        $html .= '<option value="' . $option . '"';

                        if ($option == $style) {
                            $html .= ' selected="selected"';
                        }

                        $html .= '>' . $option . '</option>';

                    }

                    $html .= '</select>';
                    $html .= '<script type="text/javascript">
                                $("#' . $key . '_border_type").selectmenu({
                                    classes: {
                                        "ui-selectmenu-menu": "scrollable"
                                    }
                                });
                            </script></div>';
                    $html .= '<div class="form-group col-lg-3">';
                    $html .= '<input data-hex="true" type="text" name="' . $key . '_color" id="' . $key . '_color" value="' . $color . '" class="pm_colorpicker" />';
                    $html .= '</div></div></div>';
                    $field['contener_border'] = $html;
                }

                // Cast options values if specified

                if ($field['type'] == 'select' && isset($field['cast'])) {

                    foreach ($field['list'] as $optionKey => $option) {
                        $field['list'][$optionKey][$field['identifier']] = $field['cast']($option[$field['identifier']]);
                    }

                }

                if (isset($field['json']) && $field['json']) {
                    $field['value'] = explode(',', $this->context->phenyxConfig->get($key));

                }

                // Fill values for all languages for all lang fields

                if (substr($field['type'], -4) == 'Lang') {

                    foreach ($languages as $language) {
                        if ($field['type'] == 'textLang') {
                            $value = PhenyxTool::getInstance()->getValue($key . '_' . $language['id_lang'], $this->context->phenyxConfig->get($key, $language['id_lang'], false));
                        } else if ($field['type'] == 'textareaLang') {
                            $value = $this->context->phenyxConfig->get($key, $language['id_lang'], false);
                        } else if ($field['type'] == 'selectLang') {
                            $value = $this->context->phenyxConfig->get($key, $language['id_lang'], false);
                        }
                       

                        $field['languages'][$language['id_lang']] = isset($value) ? $value : '';

                        if (!is_array($field['value'])) {
                            $field['value'] = [];
                        }

                        $field['value'][$language['id_lang']] = $this->getOptionValue($key , $field, $language['id_lang']);
                    }

                }

                // pre-assign vars to the tpl
                // @todo move this

                if ($field['type'] == 'maintenance_ip') {
                    $field['script_ip'] = '
                        <script type="text/javascript">
                            function addRemoteAddr()
                            {
                                var length = $(\'input[name=EPH_MAINTENANCE_IP]\').attr(\'value\').length;
                                if (length > 0)
                                    $(\'input[name=EPH_MAINTENANCE_IP]\').attr(\'value\',$(\'input[name=EPH_MAINTENANCE_IP]\').attr(\'value\') +\',' . Tools::getRemoteAddr() . '\');
                                else
                                    $(\'input[name=EPH_MAINTENANCE_IP]\').attr(\'value\',\'' . Tools::getRemoteAddr() . '\');
                            }
                        </script>';
                    $field['link_remove_ip'] = '<button type="button" class="btn btn-default" onclick="addRemoteAddr();"><i class="fa-duotone fa-regular fa-plus-large"></i> ' . $this->l('Add my IP', 'Helper') . '</button>';
                }

                // Multishop default value
                $field['multishop_default'] = false;

                // Assign the modifications back to parent array
                $categoryData['fields'][$key] = $field;

                // Is at least one required field present?

                if (isset($field['required']) && $field['required']) {
                    $categoryData['required_fields'] = true;
                }

            }

            // Assign the modifications back to parent array
            //$optionList[$category] = $optionList;
        }
        
        $this->context = Context::getContext();

        $this->tpl->assign(
            [
                'title'               => $this->title,
                'form_included'       => $this->form_included,
                'toolbar_btn'         => $this->toolbar_btn,
                'show_toolbar'        => $this->show_toolbar,
                'toolbar_scroll'      => $this->toolbar_scroll,
                'current'             => $this->currentIndex,
                'table'               => $this->table,
                'token'               => $this->token,
                'tabs'                => (isset($tabs)) ? $tabs : null,
                'option_list'         => $optionList,
                'current_id_lang'     => $this->context->language->id,
                'languages'           => isset($languages) ? $languages : null,
                'currency_left_sign'  => $this->context->currency->currency_left_sign,
                'currency_right_sign' => $this->context->currency->currency_right_sign,
                'controller'          => $this->controller_name,
                'theme_path'          => _EPH_THEMES_DIR_,
                'has_editor'          => $this->has_editor
            ]
        );
        $this->tpl->assign($this->tpl_vars);
        return parent::generate();
    }

    /**
     * Type = image
     *
     * @since 2.1.0.0
     */
    public function displayOptionTypeImage($key, $field, $value) {

        echo '<table cellspacing="0" cellpadding="0">';
        echo '<tr>';

        $i = 0;

        foreach ($field['list'] as $theme) {
            echo '<td class="center" style="width: 180px; padding:0px 20px 20px 0px;">';
            echo '<input type="radio" name="' . $key . '" id="' . $key . '_' . $theme['name'] . '_on" style="vertical-align: text-bottom;" value="' . $theme['name'] . '"' . (_THEME_NAME_ == $theme['name'] ? 'checked="checked"' : '') . ' />';
            echo '<label class="t" for="' . $key . '_' . $theme['name'] . '_on"> ' . mb_strtolower($theme['name']) . '</label>';
            echo '<br />';
            echo '<label class="t" for="' . $key . '_' . $theme['name'] . '_on">';
            echo '<img src="../themes/' . $theme['name'] . '/preview.jpg" alt="' . mb_strtolower($theme['name']) . '">';
            echo '</label>';
            echo '</td>';

            if (isset($field['max']) && ($i + 1) % $field['max'] == 0) {
                echo '</tr><tr>';
            }

            $i++;
        }

        echo '</tr>';
        echo '</table>';
    }

    

    /**
     * Type = disabled
     *
     * @since 2.1.0.0
     */
    public function displayOptionTypeDisabled($key, $field, $value) {

        echo $field['disabled'];
    }
    
    public function displayOptionTypePrice($key, $field, $value) {

        echo $this->context->currency->getSign('left');
        $this->displayOptionTypeText($key, $field, $value);
        echo $this->context->currency->getSign('right') . ' ' . $this->la('(tax excl.)', 'Helper');
    }

    /**
     * @param string $key
     * @param array  $field
     *
     * @return string
     *
     * @throws HTMLPurifier_Exception
     * @throws PhenyxException
     * @since 2.1.0.0
     */
    public function getOptionValue($key, $field, $idLang= null) {

        $value = PhenyxTool::getInstance()->getValue($key, $this->context->phenyxConfig->get($key, $idLang, false));

        if (!Validate::isCleanHtml($value)) {
            $value = $this->context->phenyxConfig->get($key, $idLang, false);
        }

        if (isset($field['defaultValue']) && !$value) {
            $value = $field['defaultValue'];
        }

        return $this->context->_tools->purifyHTML($value);
    }

}
