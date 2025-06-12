<?php

class TopMenuElements extends PhenyxObjectModel {

    public $id_topmenu_column;
    public $id_cms;
    public $id_specific_page;

    public $privacy;
    public $chosen_groups;
    public $type;
    public $id_column_depend;
    public $position = 0;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $target;
    public $have_image;
    public $image_hash;

    public $generated;
    public $link;
    public $name;
    public $image_legend;
    public $image_class;

    public $link_output_value;

    public $backName;

    public $typeName;

    public static $definition = [
        'table'     => 'topmenu_elements',
        'primary'   => 'id_topmenu_elements',
        'multilang' => true,
        'fields'    => [
            'id_topmenu_column' => ['type' => self::TYPE_INT, 'required' => true],
            'type'              => ['type' => self::TYPE_INT, 'required' => true],
            'id_cms'            => ['type' => self::TYPE_INT],
            'id_specific_page'  => ['type' => self::TYPE_INT],
            'id_column_depend'  => ['type' => self::TYPE_INT],
            'position'          => ['type' => self::TYPE_INT],
            'privacy'           => ['type' => self::TYPE_INT],
            'chosen_groups'     => ['type' => self::TYPE_STRING],
            'target'            => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'active'            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'active_desktop'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_mobile'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'have_image'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'image_hash'        => ['type' => self::TYPE_STRING],
            'custom_class'      => ['type' => self::TYPE_STRING, 'size' => 255],

            'generated'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'lang' => true],
            'name'              => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'lang' => true, 'size' => 255],
            'link'              => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'lang' => true, 'size' => 255],
            'image_legend'      => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'lang' => true, 'size' => 255],
            'image_class'       => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'size' => 255],
        ],
    ];
    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);

        if ($this->id) {
            $this->backName = $this->getBackOutputNameValue();
            $this->chosen_groups = Tools::jsonDecode($this->chosen_groups);
            $this->link_output_value = $this->getFrontOutputValue();
            $this->typeName = $this->getType();
        }

    }

    public function getFrontOutputValue() {

        $context = Context::getContext();

        $link = $context->_link;
        $_iso_lang = Language::getIsoById($context->language->id_lang);
        $return = false;
        $name = false;
        $image_legend = false;
        $icone = false;
        $icone_overlay = false;
        $url = false;
        $linkNotClickable = false;

        switch ($this->type) {

        case 1:
            $cms = new CMS($this->id_cms, $context->language->id_lang);

            if (!empty($this->name[$context->language->id_lang])) {
                $name .= htmlentities($this->name[$context->language->id_lang], ENT_COMPAT, 'UTF-8');

            } else {

                $name .= $cms->meta_title;
            }

            $return .= '<a href="javascript:void(0)"  onClick="openAjaxCms(' . (int) $cms->id . ')" title="' . $name . '"  class="a-niveau1" data-type="cms" data-id="' . (int) $cms->id . '">';

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
                    $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');
                } else {
                    $return .= 'No label';
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
            $page = new Meta($this->id_specific_page, (int) $context->cookie->id_lang);

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

        }

        $linkSettings = [
            'tag'           => 'a',
            'linkAttribute' => 'href',
            'url'           => $url,
        ];

        $return .= '<a href="' . $linkSettings['url'] . '" title="' . $name . '" ' . ($this->target ? 'target="' . htmlentities($this->target, ENT_COMPAT, 'UTF-8') . '"' : '') . ' class="a-niveau1" ' . (!empty($data_type['type']) ? ' data-type="' . $data_type['type'] . '"' : '') . (isset($data_type['id']) && $data_type['id'] ? ' data-id="' . $data_type['id'] . '"' : '') . '>';

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
    }

    public function getBackOutputNameValue() {

        $return = '';
        $context = Context::getContext();
        $_iso_lang = Language::getIsoById($context->cookie->id_lang);
        $classVars = get_class_vars(get_class($this));
        $fields = $classVars['definition']['fields'];

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                if (isset($this->{$field}) && is_array($this->{$field}) && count($this->{$field})) {
                    $this->{$field}

                    = $this->{$field}

                    [$context->language->id];
                }

            }

        }

        switch ($this->type) {

        case 1:

            if (!empty($this->name)) {
                $return .= htmlentities($this->name, ENT_COMPAT, 'UTF-8');

            } else {
                $cms = new CMS($this->id_cms, $context->cookie->id_lang);
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
                $pfg = new PGFModel($this->id_pfg, $context->cookie->id_lang);
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
                $page = new Meta($this->id_specific_page, (int) $context->cookie->id_lang);
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

        $hookname = $this->context->_hook->exec('displayTopMenuElementBackOutputNameValue', ['type' => $this->type, 'menu' => $this], null, true);

        if (is_array($hookname)) {
            $hookname = array_shift($hookname);

            if (!empty($hookname)) {
                $return = $hookname;
            }

        }

        return $return;
    }

    public static function getMenuColumnElements($id_topmenu_column, $id_lang, $active = true, $groupRestrict = false) {

        $sql_groups_join = '';
        $sql_groups_where = '';
        $file = fopen("testgetMenuColumnElements.txt", "w");
        $query = new DbQuery();
        $query->select('ate.*, atel.*, cl.link_rewrite, cl.meta_title');
        $query->from('topmenu_elements', 'ate');
        $query->leftJoin('topmenu_elements_lang', 'atel', 'ate.`id_topmenu_elements` = atel.`id_topmenu_elements` AND atel.`id_lang` = ' . (int) $id_lang);
        $query->leftJoin('cms', 'c', 'c.`id_cms` = ate.`id_cms`');
        $query->leftJoin('cms_lang', 'cl', 'c.`id_cms` = cl.`id_cms` AND cl.`id_lang` = ' . (int) $id_lang);
        $query->where('ate.`id_topmenu_column` = ' . (int) $id_topmenu_column);

        if ($active) {
            $query->where('ate.`active` = 1 AND (ate.`active_desktop` = 1 || ate.`active_mobile` = 1) AND ((ate.`id_cms` = 0)  OR c.id_cms IS NOT NULL)');
        }

        $query->groupBy('ate.`id_topmenu_elements`');
        $query->orderBy('ate.`position`');

        fwrite($file, $query . PHP_EOL);

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($query);
    }

    public static function getMenuColumnsElements($menus, $id_lang, $active = true, $groupRestrict = false) {

        $elements = [];

        if (is_array($menus) && count($menus)) {

            foreach ($menus as $columns) {

                if (is_array($columns) && count($columns)) {

                    foreach ($columns as $column) {
                        $elements[$column['id_topmenu_column']] = self::getMenuColumnElements($column['id_topmenu_column'], $id_lang, $active, $groupRestrict);
                    }

                }

            }

        }

        return $elements;
    }

    public function getType() {

        if ($this->type == 1) {
            return $this->l('CMS');
        } else

        if ($this->type == 2) {
            return $this->l('Link');
        } else

        if ($this->type == 7) {
            return $this->l('Only image or icon');
        } else

        if ($this->type == 9) {
            return $this->l('Specific page');
        }

    }

    public static function getElementsFromIdCategory($idCategory) {

        $sql = 'SELECT atp.`id_topmenu_elements`
        FROM `' . _DB_PREFIX_ . 'topmenu_elements` atp
        WHERE atp.`active` = 1
        AND atp.`type` = 3
        AND atp.`id_category` = ' . (int) $idCategory;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function getElementsFromIdCms($idCms) {

        $sql = 'SELECT atp.`id_topmenu_elements`
        FROM `' . _DB_PREFIX_ . 'topmenu_elements` atp
        WHERE atp.`active` = 1
        AND atp.`type` = 1
        AND atp.`id_cms` = ' . (int) $idCms;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function disableById($idElement) {

        return Db::getInstance()->update('topmenu_elements', [
            'active' => 0,
        ], 'id_topmenu_elements = ' . (int) $idElement);
    }

    public static function getIdElementCategoryDepend($id_topmenu_column, $id_category) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue('SELECT `id_topmenu_elements`
                FROM `' . _DB_PREFIX_ . 'topmenu_elements`
                WHERE `id_column_depend` = ' . (int) $id_topmenu_column . ' AND `id_category` = ' . (int) $id_category);
    }

    public static function getIdElementCmsDepend($idColumn, $idCms) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue('SELECT `id_topmenu_elements`
                FROM `' . _DB_PREFIX_ . 'topmenu_elements`
                WHERE `id_column_depend` = ' . (int) $idColumn . ' AND `id_cms` = ' . (int) $idCms);
    }

}
