<?php
#[AllowDynamicProperties]
class TopMenu extends PhenyxObjectModel {

    protected static $instance;
    protected static $_forceCompile;
    protected static $_caching;
    protected static $_compileCheck;

    public $id;
    public $id_cms;
    public $id_pfg;
    public $id_specific_page;
    public $custom_hook;
    public $position;
    public $txt_color_menu_tab;
    public $txt_color_menu_tab_hover;
    public $fnd_color_menu_tab_over_boxshadow;
    public $fnd_menu_bgc_over_transition_duration;
    public $fnd_color_menu_tab;
    public $fnd_color_menu_tab_over;
    public $border_size_tab;
    public $border_color_tab;
    public $width_submenu;
    public $minheight_submenu;
    public $position_submenu;
    public $fnd_color_submenu;
    public $border_color_submenu;
    public $border_size_submenu;
    public $privacy;
    public $chosen_groups;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $target;
    public $type;
    public $have_image;
    public $image_hash;
    public $image_hover;
    public $custom_class;
    public $jquery_link;
    public $generated;
    public $name;
    public $value_over;
    public $value_under;
    public $link;
    public $image_legend;
    public $image_class;

    public $link_output_value;

    public $backName;

    public $columnsWrap = [];

    public $outPutName;

    public static $definition = [
        'table'     => 'topmenu',
        'primary'   => 'id_topmenu',
        'multilang' => true,
        'fields'    => [
            'type'                                  => ['type' => self::TYPE_INT],
            'id_cms'                                => ['type' => self::TYPE_INT],
            'id_pfg'                                => ['type' => self::TYPE_INT],
            'id_specific_page'                      => ['type' => self::TYPE_INT],
            'custom_hook'                           => ['type' => self::TYPE_STRING],
            'position'                              => ['type' => self::TYPE_INT],
            'txt_color_menu_tab'                    => ['type' => self::TYPE_STRING],
            'txt_color_menu_tab_hover'              => ['type' => self::TYPE_STRING],
            'fnd_color_menu_tab_over_boxshadow'     => ['type' => self::TYPE_STRING],
            'fnd_menu_bgc_over_transition_duration' => ['type' => self::TYPE_STRING],
            'fnd_color_menu_tab'                    => ['type' => self::TYPE_STRING],
            'fnd_color_menu_tab_over'               => ['type' => self::TYPE_STRING],
            'border_size_tab'                       => ['type' => self::TYPE_STRING],
            'border_color_tab'                      => ['type' => self::TYPE_STRING],
            'width_submenu'                         => ['type' => self::TYPE_STRING],
            'minheight_submenu'                     => ['type' => self::TYPE_STRING],
            'position_submenu'                      => ['type' => self::TYPE_STRING],
            'fnd_color_submenu'                     => ['type' => self::TYPE_STRING],
            'border_color_submenu'                  => ['type' => self::TYPE_STRING],
            'border_size_submenu'                   => ['type' => self::TYPE_STRING],
            'privacy'                               => ['type' => self::TYPE_STRING],
            'chosen_groups'                         => ['type' => self::TYPE_STRING],
            'active'                                => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_desktop'                        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_mobile'                         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'target'                                => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'have_image'                            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'image_hash'                            => ['type' => self::TYPE_STRING],
            'image_hover'                           => ['type' => self::TYPE_STRING],
            'custom_class'                          => ['type' => self::TYPE_STRING, 'required' => false, 'size' => 255],
            'jquery_link'                           => ['type' => self::TYPE_STRING, 'required' => false, 'size' => 128],

            'generated'                             => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'lang' => true],
            'name'                                  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => false, 'size' => 255],
            'link'                                  => ['type' => self::TYPE_HTML, 'lang' => true, 'required' => false, 'size' => 255],
            'value_over'                            => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isString', 'required' => false],
            'value_under'                           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => false],
            'image_legend'                          => ['type' => self::TYPE_STRING, 'lang' => true, 'required' => false, 'size' => 255],
            'image_class'                           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => false, 'size' => 255],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $full = true) {

        if (is_null($id_lang) && $full) {
            $id_lang = Context::getContext()->language->id;
        }

        parent::__construct($id, $id_lang);

        if ($this->id) {
            $this->chosen_groups = Tools::jsonDecode($this->chosen_groups);
        }

        if ($this->id && $full) {

            $this->backName = $this->getBackOutputNameValue();
            $this->link_output_value = $this->getFrontOutputValue();
            $this->columnsWrap = $this->getColumnsWrap();
            $this->outPutName = $this->getOutpuName();
        }

    }

    public static function buildObject($id, $id_lang = null, $className = null, $full = true) {

        $objectData = parent::buildObject($id, $id_lang, $className);
        $objectData['chosen_groups'] = Tools::jsonDecode($objectData['chosen_groups']);

        if ($full) {

            $objectData['backName'] = self::getStaticBackOutputNameValue($objectData);
            $objectData['link_output_value'] = self::getStaticFrontOutputValue($objectData);
            $objectData['columnsWrap'] = self::getStaticColumnsWrap($objectData);
            $objectData['outPutName'] = self::getStaticOutpuName($objectData);
        }

        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }

    public function getOutpuName() {

        $classVars = get_class_vars(get_class($this));
        $fields = $classVars['definition']['fields'];

        $name = '';

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                if (isset($this->{$field}) && is_array($this->{$field}) && count($this->{$field})) {
                    $this->{$field}

                    = $this->{$field}

                    [$this->context->language->id];
                }

            }

        }

        switch ($this->type) {

        case 1:

            if (!empty($this->name)) {
                $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');

            } else {
                $cms = new CMS($this->id_cms, $this->context->cookie->id_lang);
                $name = $cms->meta_title;
            }

            break;
        case 2:

            if (!empty($this->name)) {
                $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');

            } else {
                $pfg = new PGFModel($this->id_pfg, $this->context->cookie->id_lang);
                $name = $pfg->title;
            }

            break;

        case 3:

            if (!empty($this->name)) {
                $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $name = $this->l('Custom Link');
            }

            break;
        case 8:

            if (!empty($this->name)) {
                $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $name = $this->l('Image without label');
            }

            break;

        case 9:

            if (!empty($this->name)) {
                $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $page = new Meta($this->id_specific_page, (int) $this->context->cookie->id_lang);
                $name = $page->title;
            }

            break;
        case 12:

            if (!empty($this->name)) {
                $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {

                $name = $this->l('Ajax Link');
            }

            break;

        }

        $hookname = $this->context->_hook->exec('displayTopMenuOutPutName', ['type' => $this->type, 'menu' => $this], null, true);

        if (is_array($hookname) && count($hookname)) {

            foreach ($hookname as $plugin => $vars) {

                if (!empty($vars)) {
                    $name = $vars;
                }

            }

        }

        return $name;
    }

    public function getBackOutputNameValue() {

        $return = '';

        $_iso_lang = Language::getIsoById($this->context->cookie->id_lang);

        $classVars = get_class_vars(get_class($this));
        $fields = $classVars['definition']['fields'];

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                if (isset($this->{$field}) && is_array($this->{$field}) && count($this->{$field})) {
                    $this->{$field}

                    = $this->{$field}

                    [$this->context->language->id];
                }

            }

        }

        switch ($this->type) {

        case 1:

            if (!empty($this->name)) {
                $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');

            } else {
                $cms = new CMS($this->id_cms, $this->context->cookie->id_lang);
                $return .= $cms->meta_title;
            }

            $name = $return;

            if ($this->have_image) {

                if (!empty($this->image_legend)) {
                    $name = $this->image_legend;
                }

                $return .= '<span><img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" /></span>';
            }

            break;

        case 2:

            if (!empty($this->name)) {
                $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');

            } else {
                $pfg = new PFGModel($this->id_pfg, $this->context->cookie->id_lang);
                $return .= $pfg->meta_title;
            }

            $name = $return;

            if ($this->have_image) {

                if (!empty($this->image_legend)) {
                    $name = $this->image_legend;
                }

                $return .= '<span><img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" /></span>';
            }

            break;

        case 3:

            if (!empty($this->name)) {
                $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $this->l('Custom Link');
            }

            $name = $return;

            if ($this->have_image) {

                if (!empty($this->image_legend)) {
                    $name = $this->image_legend;
                }

                $return .= '<span><img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" /></span>';
            }

            break;

        case 8:

            if (!empty($this->name)) {
                $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $this->l('Image without label');
            }

            $name = $return;

            if ($this->have_image) {

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                } else {
                    $legend = $name;
                }

                $return .= '<span><img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" /></span>';
            }

            break;
        case 9:

            if (!empty($this->name)) {
                $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $page = new Meta($this->id_specific_page, (int) $this->context->cookie->id_lang);
                $return .= $page->title;
            }

            $name = $return;

            if ($this->have_image) {

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                } else {
                    $legend = $name;
                }

                $return .= '<span><img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" /></span>';
            }

            break;
        case 12:

            if (!empty($this->name)) {
                $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $this->l('Ajax Link');
            }

            $name = $return;

            if ($this->have_image) {

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                } else {
                    $legend = $name;
                }

                $return .= '<span><img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" /></span>';
            }

            break;
        }

        $hookname = $this->context->_hook->exec('displayTopMenuBackOutputNameValue', ['type' => $this->type, 'menu' => $this], null, true);

        if (is_array($hookname) && count($hookname)) {

            foreach ($hookname as $plugin => $vars) {

                if (!empty($vars)) {
                    $return = $vars;
                }

            }

        }

        return $return;
    }

    public function getFrontOutputValue() {

        $is_ajax = $this->context->phenyxConfig->get('EPH_FRONT_AJAX') ? 1 : 0;

        $link = $this->context->_link;
        $_iso_lang = Language::getIsoById($this->context->cookie->id_lang);
        $return = false;
        $name = false;
        $image_legend = false;
        $icone = false;
        $icone_overlay = false;
        $url = false;
        $linkNotClickable = false;

        $classVars = get_class_vars(get_class($this));
        $fields = $classVars['definition']['fields'];

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                if (isset($this->{$field}) && is_array($this->{$field}) && count($this->{$field})) {
                    $this->{$field}

                    = $this->{$field}

                    [$this->context->language->id];
                }

            }

        }

        switch ($this->type) {

        case 1:
            $use_ajax = 0;

            if ($is_ajax) {
                $use_ajax = $this->context->phenyxConfig->get('EPH_CMS_AJAX') ? 1 : 0;
            }

            $cms = new CMS($this->id_cms, $this->context->cookie->id_lang);

            if (!empty($this->name)) {
                $name .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');

            } else {

                $name .= $cms->meta_title;
            }

            if ($use_ajax) {
                $return .= '<a href="javascript:void()" rel="nofollow"  onClick="openAjaxCms(' . (int) $cms->id . ')" title="' . $name . '"  class="a-niveau1 ' . $this->custom_class . '" data-type="cms" data-id="' . (int) $cms->id . '">';
            } else {
                $return .= '<a href="' . $this->context->_link->getCMSLink($cms) . '" title="' . $name . '"  class="a-niveau1 ' . $this->custom_class . '" data-type="cms" data-id="' . (int) $cms->id . '">';
            }

            $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $this->id . ' ">';

            if ($this->have_image) {
                $legend = $name;

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                }

                if (!empty($this->image_hover)) {
                    $icone_overlay = 'data-overlay="' . $this->image_hover . '"';
                    $return .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $return .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $return .= '<img src="' . $this->image_hash . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            $return .= nl2br($name);
            $return .= '</span>';

            if (!empty($this->custom_class) && !empty($this->img_value_over)) {
                $return .= '<div class="' . $this->custom_class . '">' . $row['img_value_over'] . '</div>';
            }

            $return .= '</a>';

            if (!empty($this->custom_class) && !empty($this->img_value_over)) {
                $return .= '</div>';
            }

            return $return;
            break;

        case 2:

            $use_ajax = 0;

            if ($is_ajax) {
                $use_ajax = $this->context->phenyxConfig->get('EPH_PGF_AJAX') ? 1 : 0;
            }

            $pfg = new PFGModel($this->id_pfg, $this->context->cookie->id_lang);

            if (!empty($this->name)) {
                $name .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');

            } else {

                $name .= $pfg->title;
            }

            if ($use_ajax) {
                $return .= '<a href="javascript:void()" rel="nofollow"  onClick="openAjaxFormulaire(' . (int) $pfg->id . ')" title="' . $name . '"  class="a-niveau1" data-type="pfg" data-id="' . (int) $pfg->id . '">';
            } else {
                $return .= '<a href="' . $this->context->_link->getPFGLink($pfg) . '" title="' . $name . '"  class="a-niveau1" data-type="pfg" data-id="' . (int) $pfg->id . '">';
            }

            $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $this->id . '">';

            if ($this->have_image) {
                $legend = $name;

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                }

                if (!empty($this->image_hover)) {
                    $icone_overlay = 'data-overlay="' . $this->image_hover . '"';
                    $return .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $return .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $return .= '<img src="' . $this->image_hash . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            $return .= nl2br($name);
            $return .= '</span>';

            if (!empty($this->custom_class) && !empty($this->img_value_over)) {
                $return .= '<div class="' . $this->custom_class . '">' . $row['img_value_over'] . '</div>';
            }

            $return .= '</a>';

            if (!empty($this->custom_class) && !empty($this->img_value_over)) {
                $return .= '</div>';
            }

            return $return;
            break;

        case 3:

            if (!$this->have_image) {

                if (!empty($this->name)) {
                    $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');
                } else {
                    $name = $this->l('Custom Link');
                }

            }

            if ($this->have_image) {
                $legend = $name;

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                }

                if (!empty($this->image_hover)) {
                    $icone_overlay = 'data-overlay="' . $this->image_hover . '"';
                    $icone .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $icone .= '<img src="' . $this->image_hash . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            if (!empty($this->link)) {
                $url .= htmlentities($this->link, ENT_COMPAT, 'UTF-8');
            } else {
                $linkNotClickable = true;
            }

            break;

        case 8:

            $name = '';

            if ($this->have_image) {
                $legend = $name;

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                }

                if (!empty($this->image_hover)) {
                    $icone_overlay = 'data-overlay="' . $this->image_hover . '"';
                    $icone .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $icone .= '<img src="' . $this->image_hash . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            if (!empty($this->link)) {
                $url .= htmlentities($this->link, ENT_COMPAT, 'UTF-8');
            } else {
                $linkNotClickable = true;
            }

            break;

        case 9:
            $page = new Meta($this->id_specific_page, (int) $this->context->cookie->id_lang);

            if (!empty($this->name)) {
                $name .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {

                $name .= $page->title;
            }

            if ($this->have_image) {
                $legend = $name;

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                }

                if (!empty($this->image_hover)) {
                    $icone_overlay = 'data-overlay="' . $this->image_hover . '"';
                    $icone .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $icone .= '<img src="' . $this->image_hash . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            $data_type['type'] = 'page';
            $data_type['id'] = (int) $page->id;
            $url .= $link->getPageLink($page->page);

            break;
        case 11:

            return ['tpl' => './ephtopmenu_custom_hook.tpl'];
            break;

        case 12:

            if (!$this->have_image) {

                if (!empty($this->name)) {
                    $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');
                }

            }

            if ($this->have_image) {
                $legend = $name;

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                }

                if (!empty($this->image_hover)) {
                    $icone_overlay = 'data-overlay="' . $this->image_hover . '"';
                    $icone .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $icone .= '<img src="' . $this->image_hash . '" style="max-width:200px;" class="' . $this->image_class . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            if (!empty($this->jquery_link)) {
                $url = htmlentities($this->jquery_link, ENT_COMPAT, 'UTF-8');
                $return = '<a href="javascript:void(0)" onClick="' . $url . '"' . (!empty($name) ? ' title="' . $name . '"' : '') . ' class="a-niveau1 ' . (!empty($this->custom_class) ? $this->custom_class : '') . '">';
                $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $this->id . '">';

                if ($icone) {
                    $return .= $icone;
                }

                $return .= nl2br($name);

                $return .= '</span>';

                if (!empty($this->custom_class) && !empty($this->img_value_over)) {
                    $return .= '<div class="' . $this->custom_class . '">' . $this->img_value_over . '</div>';
                }

                $return .= '</a>';

                if (!empty($this->custom_class) && !empty($this->img_value_over)) {
                    $return .= '</div>';
                }

                return $return;

            } else {
                return false;
            }

            break;

        }

        $linkSettings = [
            'tag'           => 'a',
            'linkAttribute' => 'href',
            'url'           => $url,
        ];

        $return .= '<a href="' . $linkSettings['url'] . '" title="' . $name . '" ' . ($this->target ? 'target="' . htmlentities($this->target, ENT_COMPAT, 'UTF-8') . '"' : '') . ' class="a-niveau1 ' . (!empty($this->custom_class) ? $this->custom_class : '') . '" ' . (!empty($data_type['type']) ? ' data-type="' . $data_type['type'] . '"' : '') . (isset($data_type['id']) && $data_type['id'] ? ' data-id="' . $data_type['id'] . '"' : '') . '>';

        $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $this->id . '">';

        if ($icone) {
            $return .= $icone;
        }

        $return .= nl2br($name);

        $return .= '</span>';

        if (!empty($this->custom_class) && !empty($this->img_value_over)) {
            $return .= '<div class="' . $this->custom_class . '">' . $this->img_value_over . '</div>';
        }

        $return .= '</a>';

        if (!empty($this->custom_class) && !empty($this->img_value_over)) {
            $return .= '</div>';
        }

        $hookname = $this->context->_hook->exec('displayTopMenuFrontOutputValue', ['type' => $this->type, 'menu' => $this], null, true);

        if (is_array($hookname) && count($hookname)) {

            foreach ($hookname as $plugin => $vars) {

                if (!empty($vars)) {
                    $return = $vars;
                }

            }

        }

        return $return;
    }

    public function getColumnsWrap() {

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $value = $this->context->cache_api->getData('getColumnsWrap_' . $this->id, 864000);
            $temp = empty($value) ? null : Tools::jsonDecode($value);

            if (!empty($temp)) {
                return $temp;
            }

        }

        $columnWrap = [];

        $columnWraps = new PhenyxCollection('TopMenuColumnWrap', $this->context->language->id);
        $columnWraps->where('id_topmenu', '=', $this->id);

        if (!$this->request_admin) {
            $columnWraps->where('active', '=', 1);
        }

        $columnWraps->orderBy('position');

        foreach ($columnWraps as $wrap) {
            $columnWrap[] = new TopMenuColumnWrap($wrap->id);
        }

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $temp = $columnWrap === null ? null : Tools::jsonEncode($columnWrap);
            $this->context->cache_api->putData('getColumnsWrap_' . $this->id, $temp);
        }

        return $columnWrap;
    }

    public static function getStaticOutpuName($objectData) {

        $context = Context::getContext();
        $name = '';

        switch ($objectData['type']) {

        case 1:

            if (!empty($objectData['name'])) {
                $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');

            } else {
                $cms = new CMS($objectData['id_cms'], $context->cookie->id_lang);
                $name = $cms->meta_title;
            }

            break;
        case 2:

            if (!empty($objectData['name'])) {
                $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');

            } else {
                $pfg = new PGFModel($objectData['id_pfg'], $context->cookie->id_lang);
                $name = $pfg->title;
            }

            break;

        case 3:

            if (!empty($objectData['name'])) {
                $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name = $context->translations->getClassTranslation('Custom Link', 'TopMenu');
            }

            break;
        case 8:

            if (!empty($objectData['name'])) {
                $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name = $context->translations->getClassTranslation('Image without label', 'TopMenu');
            }

            break;

        case 9:

            if (!empty($objectData['name'])) {
                $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $page = new Meta($objectData['id_specific_page'], (int) $context->cookie->id_lang);
                $name = $page->title;
            }

            break;
        case 12:

            if (!empty($objectData['name'])) {
                $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {

                $name = $context->translations->getClassTranslation('Ajax Link', 'TopMenu');
            }

            break;

        }

        $hookname = $context->_hook->exec('displayTopMenuOutPutName', ['type' => $objectData['type'], 'menu' => Tools::jsonDecode(Tools::jsonEncode($objectData))], null, true);

        if (is_array($hookname) && count($hookname)) {

            foreach ($hookname as $plugin => $vars) {

                if (!empty($vars)) {
                    $name = $vars;
                }

            }

        }

        return $name;
    }

    public static function getStaticBackOutputNameValue($objectData) {

        $context = Context::getContext();
        $return = '';

        $_iso_lang = Language::getIsoById($context->cookie->id_lang);

        switch ($objectData['type']) {

        case 1:

            if (!empty($objectData['name'])) {
                $return .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');

            } else {
                $cms = new CMS($objectData['id_cms'], $context->cookie->id_lang);
                $return .= $cms->meta_title;
            }

            $name = $return;

            if ($objectData['have_image']) {

                if (!empty($objectData['image_legend'])) {
                    $name = $objectData['image_legend'];
                }

                $return .= '<span><img src="' . $objectData['image_hash'] . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" /></span>';
            }

            break;

        case 2:

            if (!empty($objectData['name'])) {
                $return .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');

            } else {
                $pfg = new PFGModel($objectData['id_pfg'], $context->cookie->id_lang);
                $return .= $pfg->meta_title;
            }

            $name = $return;

            if ($objectData['have_image']) {

                if (!empty($objectData['image_legend'])) {
                    $name = $objectData['image_legend'];
                }

                $return .= '<span><img src="' . $objectData['image_hash'] . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" /></span>';
            }

            break;

        case 3:

            if (!empty($objectData['name'])) {
                $return .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $context->translations->getClassTranslation('Custom Link', 'TopMenu');
            }

            $name = $return;

            if ($objectData['have_image']) {

                if (!empty($objectData['image_legend'])) {
                    $name = $objectData['image_legend'];
                }

                $return .= '<span><img src="' . $objectData['image_hash'] . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" /></span>';
            }

            break;

        case 8:

            if (!empty($objectData['name'])) {
                $return .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $context->translations->getClassTranslation('Image without label', 'TopMenu');
            }

            $name = $return;

            if ($objectData['have_image']) {

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                } else {
                    $legend = $name;
                }

                $return .= '<span><img src="' . $objectData['image_hash'] . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" /></span>';
            }

            break;
        case 9:

            if (!empty($objectData['name'])) {
                $return .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $page = new Meta($objectData['id_specific_page'], (int) $context->cookie->id_lang);
                $return .= $page->title;
            }

            $name = $return;

            if ($objectData['have_image']) {

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                } else {
                    $legend = $name;
                }

                $return .= '<span><img src="' . $objectData['image_hash'] . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" /></span>';
            }

            break;
        case 12:

            if (!empty($objectData['name'])) {
                $return .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $context->translations->getClassTranslation('Ajax Link', 'TopMenu');
            }

            $name = $return;

            if ($objectData['have_image']) {

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                } else {
                    $legend = $name;
                }

                $return .= '<span><img src="' . $objectData['image_hash'] . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" /></span>';
            }

            break;
        }

        $hookname = $context->_hook->exec('displayTopMenuBackOutputNameValue', ['type' => $objectData['type'], 'menu' => Tools::jsonDecode(Tools::jsonEncode($objectData))], null, true);

        if (is_array($hookname) && count($hookname)) {

            foreach ($hookname as $plugin => $vars) {

                if (!empty($vars)) {
                    $return = $vars;
                }

            }

        }

        return $return;
    }

    public static function getStaticFrontOutputValue($objectData) {

        $context = Context::getContext();
        $is_ajax = $context->phenyxConfig->get('EPH_FRONT_AJAX') ? 1 : 0;

        $link = $context->_link;
        $_iso_lang = Language::getIsoById($context->cookie->id_lang);
        $return = false;
        $name = false;
        $image_legend = false;
        $icone = false;
        $icone_overlay = false;
        $url = false;
        $linkNotClickable = false;

        switch ($objectData['type']) {

        case 1:
            $use_ajax = 0;

            if ($is_ajax) {
                $use_ajax = $context->phenyxConfig->get('EPH_CMS_AJAX') ? 1 : 0;
            }

            $cms = new CMS($objectData['id_cms'], $context->cookie->id_lang);

            if (!empty($objectData['name'])) {
                $name .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');

            } else {

                $name .= $cms->meta_title;
            }

            if ($use_ajax) {
                $return .= '<a href="javascript:void()" rel="nofollow"  onClick="openAjaxCms(' . (int) $cms->id . ')" title="' . $name . '"  class="a-niveau1 ' . $objectData['custom_class'] . '" data-type="cms" data-id="' . (int) $cms->id . '">';
            } else {
                $return .= '<a href="' . $context->_link->getCMSLink($cms) . '" title="' . $name . '"  class="a-niveau1 ' . $objectData['custom_class'] . '" data-type="cms" data-id="' . (int) $cms->id . '">';
            }

            $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $objectData['id'] . ' ">';

            if ($objectData['have_image']) {
                $legend = $name;

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                }

                if (!empty($objectData['image_hover'])) {
                    $icone_overlay = 'data-overlay="' . $objectData['image_hover'] . '"';
                    $return .= '<img src="' . $objectData['image_hash'] . '" ' . $icone_overlay . ' id="icone_' . (int) $objectData['id'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $return .= '<img id="image_over_' . (int) $objectData['id'] . '"/>';
                } else {
                    $return .= '<img src="' . $objectData['image_hash'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            $return .= nl2br($name);
            $return .= '</span>';

            if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
                $return .= '<div class="' . $objectData['custom_class'] . '">' . $objectData['img_value_over'] . '</div>';
            }

            $return .= '</a>';

            if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
                $return .= '</div>';
            }

            return $return;
            break;

        case 2:

            $use_ajax = 0;

            if ($is_ajax) {
                $use_ajax = $context->phenyxConfig->get('EPH_PGF_AJAX') ? 1 : 0;
            }

            $pfg = new PFGModel($objectData['id_pfg'], $context->cookie->id_lang);

            if (!empty($objectData['name'])) {
                $name .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');

            } else {

                $name .= $pfg->title;
            }

            if ($use_ajax) {
                $return .= '<a href="javascript:void()" rel="nofollow"  onClick="openAjaxFormulaire(' . (int) $pfg->id . ')" title="' . $name . '"  class="a-niveau1" data-type="pfg" data-id="' . (int) $pfg->id . '">';
            } else {
                $return .= '<a href="' . $context->_link->getPFGLink($pfg) . '" title="' . $name . '"  class="a-niveau1" data-type="pfg" data-id="' . (int) $pfg->id . '">';
            }

            $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $objectData['id'] . '">';

            if ($objectData['have_image']) {
                $legend = $name;

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                }

                if (!empty($objectData['image_hover'])) {
                    $icone_overlay = 'data-overlay="' . $objectData['image_hover'] . '"';
                    $return .= '<img src="' . $objectData['image_hash'] . '" ' . $icone_overlay . ' id="icone_' . (int) $objectData['id'] . '" style="max-width:200px;" class="' . $$objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $return .= '<img id="image_over_' . (int) $objectData['id'] . '"/>';
                } else {
                    $return .= '<img src="' . $objectData['image_hash'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            $return .= nl2br($name);
            $return .= '</span>';

            if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
                $return .= '<div class="' . $objectData['custom_class'] . '">' . $objectData['img_value_over'] . '</div>';
            }

            $return .= '</a>';

            if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
                $return .= '</div>';
            }

            return $return;
            break;

        case 3:

            if (!$objectData['have_image']) {

                if (!empty($objectData['name'])) {
                    $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
                } else {
                    $name = $context->translations->getClassTranslation('Custom Link', 'TopMenu');
                }

            }

            if ($objectData['have_image']) {
                $legend = $name;

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                }

                if (!empty($objectData['image_hover'])) {
                    $icone_overlay = 'data-overlay="' . $objectData['image_hover'] . '"';
                    $icone .= '<img src="' . $objectData['image_hash'] . '" ' . $icone_overlay . ' id="icone_' . (int) $objectData['id'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $objectData['id'] . '"/>';
                } else {
                    $icone .= '<img src="' . $objectData['image_hash'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            if (!empty($objectData['link'])) {
                $url .= htmlentities($objectData['link'], ENT_COMPAT, 'UTF-8');
            } else {
                $linkNotClickable = true;
            }

            break;

        case 8:

            $name = '';

            if ($objectData['have_image']) {
                $legend = $name;

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                }

                if (!empty($objectData['image_hover'])) {
                    $icone_overlay = 'data-overlay="' . $objectData['image_hover'] . '"';
                    $icone .= '<img src="' . $objectData['image_hash'] . '" ' . $icone_overlay . ' id="icone_' . (int) $objectData['id'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $objectData['id'] . '"/>';
                } else {
                    $icone .= '<img src="' . $objectData['image_hash'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            if (!empty($objectData['link'])) {
                $url .= htmlentities($objectData['link'], ENT_COMPAT, 'UTF-8');
            } else {
                $linkNotClickable = true;
            }

            break;

        case 9:
            $page = new Meta($objectData['id_specific_page'], (int) $context->cookie->id_lang);

            if (!empty($objectData['name'])) {
                $name .= htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {

                $name .= $page->title;
            }

            if ($objectData['have_image']) {
                $legend = $name;

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                }

                if (!empty($objectData['image_hover'])) {
                    $icone_overlay = 'data-overlay="' . $objectData['image_hover'] . '"';
                    $icone .= '<img src="' . $objectData['image_hash'] . '" ' . $icone_overlay . ' id="icone_' . (int) $objectData['id'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $objectData['id'] . '"/>';
                } else {
                    $icone .= '<img src="' . $objectData['image_hash'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            $data_type['type'] = 'page';
            $data_type['id'] = (int) $page->id;
            $url .= $link->getPageLink($page->page);

            break;
        case 11:

            return ['tpl' => './ephtopmenu_custom_hook.tpl'];
            break;

        case 12:

            if (!$objectData['have_image']) {

                if (!empty($objectData['name'])) {
                    $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
                }

            }

            if ($objectData['have_image']) {
                $legend = $name;

                if (!empty($objectData['image_legend'])) {
                    $legend = $objectData['image_legend'];
                }

                if (!empty($objectData['image_hover'])) {
                    $icone_overlay = 'data-overlay="' . $objectData['image_hover'] . '"';
                    $icone .= '<img src="' . $objectData['image_hash'] . '" ' . $icone_overlay . ' id="icone_' . (int) $objectData['id'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $objectData['id'] . '"/>';
                } else {
                    $icone .= '<img src="' . $objectData['image_hash'] . '" style="max-width:200px;" class="' . $objectData['image_class'] . ' img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            if (!empty($objectData['jquery_link'])) {
                $url = htmlentities($objectData['jquery_link'], ENT_COMPAT, 'UTF-8');
                $return = '<a href="javascript:void(0)" onClick="' . $url . '"' . (!empty($name) ? ' title="' . $name . '"' : '') . ' class="a-niveau1 ' . (!empty($objectData['custom_class']) ? $objectData['custom_class'] : '') . '">';
                $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $objectData['id'] . '">';

                if ($icone) {
                    $return .= $icone;
                }

                $return .= nl2br($name);

                $return .= '</span>';

                if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
                    $return .= '<div class="' . $objectData['custom_class'] . '">' . $objectData['img_value_over'] . '</div>';
                }

                $return .= '</a>';

                if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
                    $return .= '</div>';
                }

                return $return;

            } else {
                return false;
            }

            break;

        }

        $linkSettings = [
            'tag'           => 'a',
            'linkAttribute' => 'href',
            'url'           => $url,
        ];

        $return .= '<a href="' . $linkSettings['url'] . '" title="' . $name . '" ' . ($objectData['target'] ? 'target="' . htmlentities($objectData['target'], ENT_COMPAT, 'UTF-8') . '"' : '') . ' class="a-niveau1 ' . (!empty($objectData['custom_class']) ? $objectData['custom_class'] : '') . '" ' . (!empty($data_type['type']) ? ' data-type="' . $data_type['type'] . '"' : '') . (isset($data_type['id']) && $data_type['id'] ? ' data-id="' . $data_type['id'] . '"' : '') . '>';

        $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $objectData['id'] . '">';

        if ($icone) {
            $return .= $icone;
        }

        $return .= nl2br($name);

        $return .= '</span>';

        if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
            $return .= '<div class="' . $objectData['custom_class'] . '">' . $objectData['img_value_over'] . '</div>';
        }

        $return .= '</a>';

        if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
            $return .= '</div>';
        }

        $hookname = $context->_hook->exec('displayTopMenuFrontOutputValue', ['type' => $objectData['type'], 'menu' => Tools::jsonDecode(Tools::jsonEncode($objectData))], null, true);

        if (is_array($hookname) && count($hookname)) {

            foreach ($hookname as $plugin => $vars) {

                if (!empty($vars)) {
                    $return = $vars;
                }

            }

        }

        return $return;
    }

    public static function getStaticColumnsWrap($objectData) {

        $context = Context::getContext();

        if ($context->cache_enable && is_object($context->cache_api)) {
            $value = $context->cache_api->getData('getColumnsWrap_' . $objectData['id'], 864000);
            $temp = empty($value) ? null : Tools::jsonDecode($value);

            if (!empty($temp)) {
                return $temp;
            }

        }

        $columnWrap = [];

        $columnWraps = new PhenyxCollection('TopMenuColumnWrap', $context->language->id);
        $columnWraps->where('id_topmenu', '=', $objectData['id']);

        if (!PhenyxObjectModel::$admin_request) {
            $columnWraps->where('active', '=', 1);
        }

        $columnWraps->orderBy('position');

        foreach ($columnWraps as $wrap) {
            $columnWrap[] = TopMenuColumnWrap::buildObject($wrap->id, $context->language->id);
        }

        if ($context->cache_enable && is_object($context->cache_api)) {
            $temp = $columnWrap === null ? null : Tools::jsonEncode($columnWrap);
            $context->cache_api->putData('getColumnsWrap_' . $objectData['id'], $temp);
        }

        return $columnWrap;
    }

    public static function getInstance($id = null, $idLang = null) {

        if (!static::$instance) {
            static::$instance = new TopMenu($id, $idLang);
        }

        return static::$instance;
    }

    public function add($autodate = true, $nullValues = false) {

        $this->position = Topmenu::getHigherPosition() + 1;
        $result = parent::add($autodate, $nullValues);

        if ($result) {
            $this->context->_session->destroy();
        }

        return $result;
    }

    public static function getHigherPosition() {

        $position = DB::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('topmenu')
        );

        return (is_numeric($position)) ? $position : -1;
    }

    public function update($nullValues = false) {

        $result = parent::update($nullValues);

        if ($result) {
            $this->context->_session->destroy();
            $this->backName = $this->getBackOutputNameValue();
            $this->link_output_value = $this->getFrontOutputValue();
            $this->columnsWrap = $this->getColumnsWrap();
            $this->outPutName = $this->getOutpuName();
        }

        return $result;
    }

    public function delete() {

        $wraps = new PhenyxCollection('TopMenuColumnWrap');
        $wraps->where('id_topmenu', '=', $this->id);

        foreach ($wraps as $wrap) {
            $wrap->delete();
        }

        $this->context->_session->destroy();
        return parent::delete();
    }

    public static function menuHaveDepend($id_topmenu) {

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_topmenu_column`')
                ->from('topmenu_columns')
                ->where('`id_topmenu_depend` = ' . (int) $id_topmenu)
        );

    }

    public static function getMenusId() {

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_topmenu`')
                ->from('topmenu')
        );

    }

    public static function getMenusFromIdCms($idCms) {

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_topmenu`')
                ->from('topmenu')
                ->where('`active` = 1 AND `type` = 1 AND  `id_cms` = ' . (int) $idCms)
        );

    }

    public static function disableById($idMenu) {

        return Db::getInstance()->update('topmenu', [
            'active' => 0,
        ], 'id_topmenu = ' . (int) $idMenu);
    }

    public function getMenus($id_lang, $active = true, $groupRestrict = false) {

        $topMenus = $this->_session->get('getFrontMenus');

        if (empty($topMenus)) {
            $topMenus = [];
            $query = new DbQuery();
            $query->select('id_topmenu');
            $query->from('topmenu');

            if ($active) {
                $query->where('active = 1');
            }

            $query->orderBy('position');
            $menus = Db::getInstance()->executeS($query);

            foreach ($menus as $menu) {
                $topMenus[] = TopMenu::buildObject($menu['id_topmenu'], $id_lang);
            }

            $this->_session->set('getFrontMenus', $topMenus);
        }

        return $topMenus;

    }

    public function getAdminMenus() {

        $topMenus = $this->_session->get('getAdminMenus');

        if (empty($topMenus)) {
            $this->request_admin = true;
            PhenyxObjectModel::$admin_request = true;
            $topMenus = [];
            $menus = Db::getInstance()->executeS(
                (new DbQuery())
                    ->select('`id_topmenu`')
                    ->from('topmenu')
                    ->orderBy('position')
            );

            foreach ($menus as $menu) {
                $topMenus[] = TopMenu::buildObject($menu['id_topmenu'], $this->context->language->id);
            }

            $this->request_admin = false;
            PhenyxObjectModel::$admin_request = false;

            $this->_session->set('getAdminMenus', $topMenus);
        }

        return $topMenus;

    }

    public static function getCustomerGroups() {

        $groups = [];

        if (Group::isFeatureActive()) {

            if (Validate::isLoadedObject(Context::getContext()->user)) {
                $groups = FrontController::getCurrentCustomerGroups();
            } else {
                $groups = [(int) Context::getContext()->phenyxConfig->get('EPH_UNIDENTIFIED_GROUP')];
            }

        }

        sort($groups);
        return $groups;
    }

    public function clearMenuCache() {

        $this->context->smarty->clearCompiledTemplate(_THEMES_DIR_ . 'menu/ephtopmenu.tpl');
        return $this->context->smarty->clearCache(null, 'ADTM');
    }

    public static function addSqlAssociation($table, $alias, $identifier, $inner_join = true, $on = null, $companys = false) {

        return '';
    }

    public function getType($type) {

        if ($type == 1) {
            return $this->l('CMS');
        } else

        if ($type == 2) {
            return $this->l('Link');
        } else

        if ($type == 3) {
            return $this->l('Category');
        } else

        if ($type == 6) {
            return $this->l('Search');
        } else

        if ($type == 7) {
            return $this->l('Only image or icon');
        } else

        if ($type == 9) {
            return $this->l('Specific page');
        } else

        if ($type == 10) {
            return $this->l('Hook Cart');
        } else

        if ($type == 11) {
            return $this->l('Hook Search');
        } else

        if ($type == 12) {
            return $this->l('Custom Hook');
        } else

        if ($type == 13) {
            return $this->l('CMS category');
        }

    }

    public static function displayMenuForm() {

        $this->context = Context::getContext();
        $menus = TopMenu::getInstance()->getMenus($this->context->cookie->id_lang, false);

        if (is_array($menus) && count($menus)) {

            foreach ($menus as &$menu) {
                $menu['columnsWrap'] = TopMenuColumnWrap::getMenuColumnsWrap($menu['id_topmenu'], $this->context->cookie->id_lang, false);

                if (count($menu['columnsWrap'])) {

                    foreach ($menu['columnsWrap'] as &$columnWrap) {
                        $columnWrap['columns'] = TopMenuColumn::getMenuColums($columnWrap['id_topmenu_columns_wrap'], $this->context->cookie->id_lang, false);

                        if (count($columnWrap['columns'])) {

                            foreach ($columnWrap['columns'] as &$column) {
                                $column['columnElements'] = TopMenuElements::getMenuColumnElements($column['id_topmenu_column'], $this->context->cookie->id_lang, false);

                            }

                        }

                    }

                }

            }

        }

        $cms = CMS::listCms((int) $this->context->cookie->id_lang);
        $cmsNestedCategories = TopMenu::getNestedCmsCategories((int) $this->context->cookie->id_lang);

        $cmsCategories = [];

        foreach ($cmsNestedCategories as $cmsCategory) {
            $cmsCategory['level_depth'] = (int) $cmsCategory['level_depth'];
            $cmsCategories[] = $cmsCategory;
            TopMenu::getChildrenCmsCategories($cmsCategories, $cmsCategory, null);
        }

        $alreadyDefinedCurrentIdMenu = $this->context->smarty->getTemplateVars('current_id_topmenu');

        if (empty($alreadyDefinedCurrentIdMenu)) {
            $currentIdMenu = Tools::getValue('id_topmenu', false);
        } else {
            $currentIdMenu = $alreadyDefinedCurrentIdMenu;
        }

        $ObjEphenyxTopMenuClass = false;
        $ObjEphenyxTopMenuColumnWrapClass = false;
        $ObjEphenyxTopMenuColumnClass = false;
        $ObjEphenyxTopMenuElementsClass = false;

        if (!Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')) {

            if (Tools::getValue('editMenu') && Tools::getValue('id_topmenu')) {
                $ObjEphenyxTopMenuClass = new TopMenu(Tools::getValue('id_topmenu'));
            }

        }

        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')) {

            if (Tools::getValue('editColumnWrap') && Tools::getValue('id_topmenu_columns_wrap')) {
                $ObjEphenyxTopMenuColumnWrapClass = new TopMenuColumnWrap(Tools::getValue('id_topmenu_columns_wrap'));
            }

        }

        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editElement')) {

            if (Tools::getValue('editColumn') && Tools::getValue('id_topmenu_column')) {
                $ObjEphenyxTopMenuColumnClass = new TopMenuColumn(Tools::getValue('id_topmenu_column'));

            }

        }

        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn')) {

            if (Tools::getValue('editElement') && Tools::getValue('id_topmenu_element')) {
                $ObjEphenyxTopMenuElementsClass = new TopMenuElements(Tools::getValue('id_topmenu_element'));
            }

        }

        $vars = [
            'menus'                => $menus,
            'current_id_topmenu'   => $currentIdMenu,
            'displayTabElement'    => (!Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')),
            'displayColumnElement' => (!Tools::getValue('editMenu') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')),
            'displayGroupElement'  => (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editElement')),
            'displayItemElement'   => (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn')),
            'editMenu'             => (Tools::getValue('editMenu') && Tools::getValue('id_topmenu')),
            'editColumn'           => (Tools::getValue('editColumnWrap') && Tools::getValue('id_topmenu_columns_wrap')),
            'editGroup'            => (Tools::getValue('editColumn') && Tools::getValue('id_topmenu_column')),
            'editElement'          => (Tools::getValue('editElement') && Tools::getValue('id_topmenu_element')),
            'cms'                  => $cms,
            'cmsCategories'        => $cmsCategories,
            'manufacturer'         => $manufacturer,
            'linkTopMenu'          => $this->context->_link->getAdminLink('AdminTopMenu'),
            'ObjTopMenu'           => $ObjEphenyxTopMenuClass,
            'ObjTopMenuColumnWrap' => $ObjEphenyxTopMenuColumnWrapClass,
            'ObjTopMenuColumn'     => $ObjEphenyxTopMenuColumnClass,
            'ObjTopMenuElements'   => $ObjEphenyxTopMenuElementsClass,
        ];

        return self::fetchTemplate('tabs/display_form.tpl', $vars);
    }

    private static function getNestedCmsCategories($id_lang) {

        $nestedArray = [];
        $cmsCategories = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            'SELECT cc.*, ccl.*
            FROM `' . _DB_PREFIX_ . 'cms_category` cc
            LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` ccl ON cc.`id_cms_category` = ccl.`id_cms_category`
            WHERE ccl.`id_lang` = ' . (int) $id_lang . '
            AND cc.`id_parent` != 0
            ORDER BY cc.`level_depth` ASC, cc.`position` ASC'
        );
        $buff = [];

        foreach ($cmsCategories as $row) {
            $current = &$buff[$row['id_cms_category']];
            $current = $row;

            if (!$row['active']) {
                $current['name'] .= ' ' . '(disabled)';
            }

            if ((int) $row['id_parent'] == 1) {
                $nestedArray[$row['id_cms_category']] = &$current;
            } else {
                $buff[$row['id_parent']]['children'][$row['id_cms_category']] = &$current;
            }

        }

        return $nestedArray;
    }

    private static function getChildrenCmsCategories(&$cmsList, $cmsCategory, $levelDepth = false) {

        if (isset($cmsCategory['children']) && self::isFilledArray($cmsCategory['children'])) {

            foreach ($cmsCategory['children'] as $cmsInformation) {
                $cmsInformation['level_depth'] = (int) $cmsInformation['level_depth'];
                $cmsList[] = $cmsInformation;
                $this->getChildrenCmsCategories($cmsList, $cmsInformation, ($levelDepth !== false ? $levelDepth + 1 : $levelDepth));
            }

        }

    }

    public static function isFilledArray($array) {

        return $array && is_array($array) && count($array);
    }

    public static function fetchTemplate($tpl, $customVars = [], $configOptions = []) {

        //$data = $this->createTemplate('controllers/top_menu/' . $tpl);
        $context = Context::getContext();
        $admin_webpath = str_ireplace(_SHOP_CORE_DIR_, '', _EPH_ROOT_DIR_);
        $admin_webpath = preg_replace('/^' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $admin_webpath);

        $tpl = $context->smarty->createTemplate('controllers/top_menu/' . $tpl, $context->smarty);
        $tpl->assign(
            [
                'linkTopMenu'      => $context->_link->getAdminLink('AdminTopMenu'),
                'topMenu_img_dir'  => _EPH_MENU_DIR_,
                'menu_img_dir'     => __EPH_BASE_URI__ . $admin_webpath . '/themes/default/img/topmenu/',
                'current_iso_lang' => Language::getIsoById($context->cookie->id_lang),
                'current_id_lang'  => (int) $context->language->id,
                'default_language' => (int) $this->context->phenyxConfig->get('EPH_LANG_DEFAULT'),
                'languages'        => Language::getLanguages(false),
                'options'          => $configOptions,
            ]
        );

        if (is_array($customVars) && count($customVars)) {
            $tpl->assign($customVars);
        }

        return $tpl->fetch();

    }

}
