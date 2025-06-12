<?php

class TopMenuColumn extends PhenyxObjectModel {

    public $id;

    public $id_topmenu_columns_wrap;
    public $id_topmenu;
    public $id_cms;
    public $id_pfg;
    public $id_specific_page;
    public $custom_hook;
    public $generated;
    public $name;
    public $link;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $type;
    public $have_image;
    public $image_hash;
    public $privacy;
    public $chosen_groups;
    public $image_legend;
    public $custom_class;
    public $jquery_link;
    public $img_value_over;
    public $value_over;
    public $value_under;
    public $id_topmenu_depend;
    public $target;
    public $is_column = true;
    public $position = 0;

    public $link_output_value;

    public $backName;

    public $elements = [];

    public $outPutName;

    public static $definition = [
        'table'     => 'topmenu_columns',
        'primary'   => 'id_topmenu_column',
        'multishop' => false,
        'multilang' => true,
        'fields'    => [
            'id_topmenu_columns_wrap' => ['type' => self::TYPE_INT, 'required' => true],
            'id_topmenu'              => ['type' => self::TYPE_INT, 'required' => true],
            'id_cms'                  => ['type' => self::TYPE_INT],
            'id_pfg'                  => ['type' => self::TYPE_INT],
            'id_specific_page'        => ['type' => self::TYPE_INT],
            'custom_hook'             => ['type' => self::TYPE_STRING],
            'id_topmenu_depend'       => ['type' => self::TYPE_INT],
            'position'                => ['type' => self::TYPE_INT],
            'privacy'                 => ['type' => self::TYPE_INT],
            'chosen_groups'           => ['type' => self::TYPE_STRING],
            'active'                  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'active_desktop'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_mobile'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'target'                  => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'type'                    => ['type' => self::TYPE_INT, 'required' => true],
            'custom_class'            => ['type' => self::TYPE_STRING, 'size' => 255],
            'jquery_link'             => ['type' => self::TYPE_STRING, 'required' => false, 'size' => 128],
            'have_image'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'image_hash'              => ['type' => self::TYPE_STRING],

            'generated'               => ['type' => self::TYPE_BOOL, 'lang' => true],
            'name'                    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'size' => 255],
            'link'                    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => 255],
            'img_value_over'          => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
            'value_over'              => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
            'value_under'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
            'image_legend'            => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName'],
        ],
    ];

    public function __construct($id_topmenu_column = null, $id_lang = null, $full = true) {

        if (is_null($id_lang) && $full) {
            $id_lang = Context::getContext()->language->id;
        }

        parent::__construct($id_topmenu_column, $id_lang);

        if ($this->id) {
            $this->chosen_groups = Tools::jsonDecode($this->chosen_groups);

        }

        if ($this->id && $full) {

            $this->backName = $this->getBackOutputNameValue();
            $this->link_output_value = $this->getFrontOutputValue();
            $this->elements = $this->getElements();
            $this->outPutName = $this->getOutpuName();
        }

    }

    public static function buildObject($id, $id_lang = null, $className = null, $full = true) {

        $objectData = parent::buildObject($id, $id_lang, $className);
        $objectData['chosen_groups'] = Tools::jsonDecode($objectData['chosen_groups']);

        if ($full) {

            $objectData['backName'] = self::getStaticBackOutputNameValue($objectData);
            $objectData['link_output_value'] = self::getStaticFrontOutputValue($objectData);
            $objectData['columnsWrap'] = self::getStaticElements($objectData);
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
                $pfg = new PFGModel($this->id_pfg, $this->context->cookie->id_lang);
                $name = $pfg->title;
            }

            break;

        case 3:

            if (!empty($this->name)) {
                $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $name = $this->l('No label');
            }

            break;

        case 8:

            if (!empty($this->name)) {
                $name = htmlentities($this->name, ENT_COMPAT, 'UTF-8');
            } else {
                $name = $this->l('No label');
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

        $hookname = $this->context->_hook->exec('displayTopMenuColumnOutPutName', ['type' => $this->type, 'menu' => $this], null, true);

        if (is_array($hookname)) {
            $hookname = array_shift($hookname);

            if (!empty($hookname)) {
                $name = $hookname;
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

                $return .= '<img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" />';
            }

            break;

        case 2:

            if (!empty($this->name)) {
                $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');

            } else {
                $pfg = new PGFModel($this->id_pfg, $this->context->cookie->id_lang);
                $return .= $pfg->title;
            }

            $name = $return;

            if ($this->have_image) {

                if (!empty($this->image_legend)) {
                    $name = $this->image_legend;
                }

                $return .= '<img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" />';
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

                $return .= '<img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $name . '" title="' . $name . '" />';
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

                $return .= '<img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" />';
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

                $return .= '<img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" />';
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

                $return .= '<img src="' . $this->image_hash . '" style="margin:0 10px;height:50px;" alt="' . $legend . '" title="' . $legend . '" />';
            }

            break;
        }

        $hookname = $this->context->_hook->exec('displayTopMenuColumnBackOutputNameValue', ['type' => $this->type, 'menu' => $this], null, true);

        if (is_array($hookname)) {
            $hookname = array_shift($hookname);

            if (!empty($hookname)) {
                $return = $hookname;
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
                $return .= '<a href="javascript:void()" rel="nofollow"  onClick="openAjaxCms(' . (int) $cms->id . ')" title="' . $name . '"  class="a-niveau1" data-type="cms" data-id="' . (int) $cms->id . '">';
            } else {
                $return .= '<a href="' . $this->context->_link->getCMSLink($cms) . '" title="' . $name . '"  class="a-niveau1" data-type="cms" data-id="' . (int) $cms->id . '">';
            }

            if ($this->have_image) {
                $legend = $name;

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                }

                if (!empty($this->image_hover)) {
                    $icone_overlay = 'data-overlay="' . $this->image_hover . '"';
                    $return .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $return .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $return .= '<img src="' . $this->image_hash . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            $return .= nl2br($name);

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

            if ($this->have_image) {
                $legend = $name;

                if (!empty($this->image_legend)) {
                    $legend = $this->image_legend;
                }

                if (!empty($this->image_hover)) {
                    $icone_overlay = 'data-overlay="' . $this->image_hover . '"';
                    $return .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $return .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $return .= '<img src="' . $this->image_hash . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                }

            }

            $return .= nl2br($name);

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
                    $icone .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $icone .= '<img src="' . $this->image_hash . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
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
                    $icone .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $icone .= '<img src="' . $this->image_hash . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
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
                    $icone .= '<img src="' . $this->image_hash . '" ' . $icone_overlay . ' id="icone_' . (int) $this->id . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
                    $icone .= '<img id="image_over_' . (int) $this->id . '"/>';
                } else {
                    $icone .= '<img src="' . $this->image_hash . '" style="max-width:200px;"  img-fluid"  alt="' . $legend . '" title="' . $legend . '" />';
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

        $return .= '<a href="' . $linkSettings['url'] . '" title="' . $name . '" ' . ($this->target ? 'target="' . htmlentities($this->target, ENT_COMPAT, 'UTF-8') . '"' : '') . ' ' . (!empty($data_type['type']) ? ' data-type="' . $data_type['type'] . '"' : '') . (isset($data_type['id']) && $data_type['id'] ? ' data-id="' . $data_type['id'] . '"' : '') . '>';

        if ($icone) {
            $return .= $icone;
        }

        $return .= nl2br($name);

        if (!empty($this->custom_class) && !empty($this->img_value_over)) {
            $return .= '<div class="' . $this->custom_class . '"><div class="balise_h3">' . $this->img_value_over . '</div></div>';
        }

        $return .= '</a>';

        if (!empty($this->custom_class) && !empty($this->img_value_over)) {
            $return .= '</div>';
        }

        $hookname = $this->context->_hook->exec('displayTopMenuColumnFrontOutputValue', ['type' => $this->type, 'menu' => $this], null, true);

        if (is_array($hookname)) {

            foreach ($hookname as $plugin => $value) {
                $return = $value;
            }

        }

        return $return;
    }

    public function getElements() {

        $element = [];

        $elements = new PhenyxCollection('TopMenuElements', $this->context->language->id);

        if (!$this->request_admin) {
            $elements->where('active', '=', 1);
        }

        $elements->where('id_topmenu_column', '=', $this->id);
        $elements->orderBy('position');

        foreach ($elements as $wrap) {
            $element[] = new TopMenuElements($wrap->id, $this->context->language->id);
        }

        return $element;

    }

    public static function getStaticOutpuName($objectData) {

        $context = Context::getContext();
        $name = null;

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
                $name = $context->translations->getClassTranslation('No label', 'TopMenuColumn');
            }

            break;

        case 8:

            if (!empty($objectData['name'])) {
                $name = htmlentities($objectData['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name = $context->translations->getClassTranslation('No label', 'TopMenuColumn');
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

        $hookname = $context->_hook->exec('displayTopMenuColumnOutPutName', ['type' => $objectData['type'], 'menu' => Tools::jsonDecode(Tools::jsonEncode($objectData))], null, true);

        if (is_array($hookname)) {

            foreach ($hookname as $plugin => $value) {

                if (!empty($value)) {
                    $name = $value;
                }

            }

        }

        return $name;
    }

    public static function getStaticBackOutputNameValue($objectData) {

        $context = Context::getContext();
        $return = '';

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
                $return .= $context->translations->getClassTranslation('Custom Link', 'TopMenuColumn');
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
                $return .= $context->translations->getClassTranslation('Ajax Link', 'TopMenuColumn');
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

        $hookname = $context->_hook->exec('displayTopMenuColumnBackOutputNameValue', ['type' => $objectData['type'], 'menu' => Tools::jsonDecode(Tools::jsonEncode($objectData))], null, true);

        if (is_array($hookname)) {
            $hookname = array_shift($hookname);

            if (!empty($hookname)) {
                $return = $hookname;
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
                $return .= '<a href="javascript:void()" rel="nofollow"  onClick="openAjaxCms(' . (int) $cms->id . ')" title="' . $name . '"  class="a-niveau1" data-type="cms" data-id="' . (int) $cms->id . '">';
            } else {
                $return .= '<a href="' . $context->_link->getCMSLink($cms) . '" title="' . $name . '"  class="a-niveau1" data-type="cms" data-id="' . (int) $cms->id . '">';
            }

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
                    $name = $context->translations->getClassTranslation('Custom Link', 'TopMenuColumn');
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

        $return .= '<a href="' . $linkSettings['url'] . '" title="' . $name . '" ' . ($objectData['target'] ? 'target="' . htmlentities($objectData['target'], ENT_COMPAT, 'UTF-8') . '"' : '') . ' ' . (!empty($data_type['type']) ? ' data-type="' . $data_type['type'] . '"' : '') . (isset($data_type['id']) && $data_type['id'] ? ' data-id="' . $data_type['id'] . '"' : '') . '>';

        if ($icone) {
            $return .= $icone;
        }

        $return .= nl2br($name);

        if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
            $return .= '<div class="' . $objectData['custom_class'] . '">' . $objectData['img_value_over'] . '</div>';
        }

        $return .= '</a>';

        if (!empty($objectData['custom_class']) && !empty($objectData['img_value_over'])) {
            $return .= '</div>';
        }

        $hookname = $context->_hook->exec('displayTopMenuColumnFrontOutputValue', ['type' => $objectData['type'], 'menu' => Tools::jsonDecode(Tools::jsonEncode($objectData))], null, true);

        if (is_array($hookname)) {

            foreach ($hookname as $plugin => $value) {

                if (!empty($value)) {
                    $return = $value;
                }

            }

        }

        return $return;
    }

    public static function getStaticElements($objectData) {

        $context = Context::getContext();
        $element = [];

        $elements = new PhenyxCollection('TopMenuElements', $context->language->id);

        if (!PhenyxObjectModel::$admin_request) {
            $elements->where('active', '=', 1);
        }

        $elements->where('id_topmenu_column', '=', $objectData['id']);
        $elements->orderBy('position');

        foreach ($elements as $wrap) {
            $element[] = new TopMenuElements($wrap->id, $context->language->id);
        }

        return $element;

    }

    public function add($autodate = true, $nullValues = false) {

        $result = parent::add($autodate, $nullValues);

        if ($result) {
            $this->context->_session->destroy();
        }

        return $result;
    }

    public function update($nullValues = false) {

        $result = parent::update($nullValues);

        if ($result) {
            $this->context->_session->destroy();
            $this->backName = $this->getBackOutputNameValue();
            $this->link_output_value = $this->getFrontOutputValue();
            $this->elements = $this->getElements();
            $this->outPutName = $this->getOutpuName();
        }

        return $result;
    }

    public function delete() {

        $elements = new PhenyxCollection('TopMenuElements');
        $elements->where('id_topmenu_column', '=', $this->id);

        foreach ($elements as $element) {
            $element->delete();
        }

        $this->context->_session->destroy();
        return parent::delete();
    }

    public static function getIdColumnCmsCategoryDepend($id_menu, $id_cms_category) {

        return Db::getInstance()->getValue(
            (new DbQuery())
                ->select('`id_topmenu_column`')
                ->from('topmenu_columns')
                ->where('`id_topmenu_depend` = ' . (int) $id_menu)
                ->where('`id_cms_category` = ' . (int) $id_cms_category)
        );

    }

    public static function getIdMenuByIdColumn($id_topmenu_column) {

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_topmenu`')
                ->from('topmenu_columns')
                ->where('`id_topmenu_column` = ' . (int) $id_topmenu_column)
        );
    }

    public static function columnHaveDepend($id_topmenu_column) {

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_topmenu_column`')
                ->from('topmenu_columns')
                ->where('`id_topmenu_depend` = ' . (int) $id_topmenu_column)
        );

    }

    public static function getMenuColums($id_topmenu_columns_wrap, $id_lang, $active = true, $groupRestrict = false) {

        $sql_groups_join = '';
        $sql_groups_where = '';

        $query = new DbQuery();
        $query->select('atmc.*, atmcl.*, cl.link_rewrite, cl.meta_title');
        $query->from('topmenu_columns', 'atmc');
        $query->leftJoin('topmenu_columns_lang', 'atmcl', 'atmc.`id_topmenu_column` = atmcl.`id_topmenu_column` AND atmcl.`id_lang` = ' . (int) $id_lang);
        $query->leftJoin('cms', 'c', 'c.id_cms = atmc.`id_cms`');
        $query->leftJoin('cms_lang', 'cl', 'c.id_cms = cl.id_cms AND cl.id_lang = ' . (int) $id_lang);
        $query->where('atmc.`id_topmenu_columns_wrap` = ' . (int) $id_topmenu_columns_wrap);

        if ($active) {
            $query->where('atmc.`active` = 1 AND (atmc.`active_desktop` = 1 || atmc.`active_mobile` = 1) AND ((atmc.`id_cms` = 0)  OR c.id_cms IS NOT NULL)');
        }

        $query->where('atmc.`type` != 8');
        $query->groupBy('atmc.`id_topmenu_column`');
        $query->orderBy('atmc.`position`');

        $columns = Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($query);

        foreach ($columns as &$column) {
            $column['outPutName'] = TopMenu::getAdminOutputNameValue($column, true, 'column');
        }

        return $columns;
    }

    public static function getMenuColumsByIdMenu($id_menu, $id_lang, $active = true, $groupRestrict = false) {

        $query = new DbQuery();
        $query->select('atmc.*, atmcl.*, cl.link_rewrite, cl.meta_title');
        $query->from('topmenu_columns', 'atmc');
        $query->leftJoin('topmenu_columns_lang', 'atmcl', 'atmc.`id_topmenu_column` = atmcl.`id_topmenu_column` AND atmcl.`id_lang` = ' . (int) $id_lang);
        $query->leftJoin('cms', 'c', 'c.`id_cms` = atmc.`id_cms`');
        $query->leftJoin('cms_lang', 'cl', 'c.id_cms = cl.id_cms AND cl.id_lang = ' . (int) $id_lang);
        $query->where('atmc.`id_topmenu` = ' . (int) $id_menu);

        if ($active) {
            $query->where('atmc.`active` = 1 AND (atmc.`active_desktop` = 1 || atmc.`active_mobile` = 1) AND ((atmc.`id_cms` = 0)  OR c.id_cms IS NOT NULL)');
        }

        $query->where('atmc.`type` != 8');
        $query->groupBy('atmc.`id_topmenu_column`');
        $query->orderBy('atmc.`position`');

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($query);
    }

    public static function getMenusColums($menus, $id_lang, $groupRestrict = false) {

        $columns = [];

        foreach ($menus as $columnsWrap) {

            foreach ($columnsWrap as $columnWrap) {
                $columnInfos = self::getMenuColums($columnWrap['id_topmenu_columns_wrap'], $id_lang, true, $groupRestrict);

                $columns[$columnWrap['id_topmenu_columns_wrap']] = $columnInfos;
            }

        }

        return $columns;
    }

    public static function getColumnIds($ids_wrap) {

        if (!is_array($ids_wrap)) {
            $ids_wrap = [(int) $ids_wrap];
        }

        $result = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_topmenu_column`')
                ->from('topmenu_columns')
                ->where('`id_topmenu_columns_wrap` IN(' . implode(',', array_map('intval', $ids_wrap)) . ')')
        );

        $columns = [];

        foreach ($result as $row) {
            $columns[] = $row['id_topmenu_column'];
        }

        return $columns;
    }

    public static function getnbColumninWrap($idColumnWrap) {

        return Db::getInstance()->getValue(
            (new DbQuery())
                ->select('SELECT COUNT(id_topmenu_column)')
                ->from('topmenu_columns')
                ->where('`id_topmenu_columns_wrap` = ' . $idColumnWrap)
        );
    }

    public static function getColumnsFromIdCms($idCms) {

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_topmenu_column`')
                ->from('topmenu_columns')
                ->where('`active` = 1 AND `type` = 1 AND `id_cms` = ' . (int) $idCms)
        );

    }

    public static function disableById($idColumn) {

        return Db::getInstance()->update('topmenu_columns', [
            'active' => 0,
        ], 'id_topmenu_column = ' . (int) $idColumn);
    }

}
