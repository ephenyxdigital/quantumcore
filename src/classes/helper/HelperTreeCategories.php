<?php

/**
 * Class HelperTreeCategoriesCore
 *
 * @since 1.8.1.0
 */
class HelperTreeCategories extends Tree {

    const DEFAULT_TEMPLATE = 'tree_categories.tpl';
    const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder_radio.tpl';
    const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item_radio.tpl';

    // @codingStandardsIgnoreStart
    protected $_disabled_categories;
    protected $_input_name;
    /** @var int $_lang */
    protected $_lang;
    protected $_root_category;
    protected $_selected_categories;
    protected $_full_tree = false;
    protected $_use_checkbox;
    protected $_use_search;
    protected $_children_only = false;
    // @codingStandardsIgnoreEnd

    /**
     * HelperTreeCategoriesCore constructor.
     *
     * @param int         $id
     * @param string|null $title
     * @param int|null    $rootCategory
     * @param int|null    $lang
     * @param bool        $useShopRestriction
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxException
     */
    public function __construct(
        $id,
        $title = null,
        $rootCategory = null,
        $lang = null
    ) {

        parent::__construct($id);

        if (isset($title)) {
            $this->setTitle($title);
        }

        if (isset($rootCategory)) {
            $this->setRootCategory($rootCategory);
        }

        $this->setLang($lang);
        
    }

    /**
     * @param array $categories
     * @param int   $idCategory
     *
     * @return array
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    protected function fillTree(&$categories, $idCategory) {

        $tree = [];

        foreach ($categories[$idCategory] as $category) {
            $tree[$category['id_category']] = $category;

            if (!empty($categories[$category['id_category']])) {
                $tree[$category['id_category']]['children'] = $this->fillTree($categories, $category['id_category']);
            } else
            if ($result = Category::hasChildren($category['id_category'], $this->getLang(), false)) {
                $tree[$category['id_category']]['children'] = [$result[0]['id_category'] => $result[0]];
            }

        }

        return $tree;
    }

    /**
     * @return mixed
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getData() {

        if (!isset($this->_data)) {
           
            $lang = $this->getLang();
            $rootCategory = (int) $this->getRootCategory();

            if ($this->_full_tree) {
                $this->setData(Category::getNestedCategories($rootCategory, $lang, false, null));
                $this->setDataSearch(Category::getAllCategoriesName($rootCategory, $lang, false, null));
            } else
            if ($this->_children_only) {

                if (empty($rootCategory)) {
                    $rootCategory = Category::getRootCategory()->id;
                }

                $categories[$rootCategory] = Category::getChildren($rootCategory, $lang, false);
                $children = $this->fillTree($categories, $rootCategory);
                $this->setData($children);
            } else {

                if (empty($rootCategory)) {
                    $rootCategory = Category::getRootCategory()->id;
                }

                $newSelectedCategories = [];
                $selectedCategories = $this->getSelectedCategories();
                $categories[$rootCategory] = Category::getChildren($rootCategory, $lang, false);

                foreach ($selectedCategories as $selectedCategory) {
                    $category = new Category($selectedCategory, $lang);
                    $newSelectedCategories[] = $selectedCategory;
                    $parents = $category->getParentsCategories($lang);

                    foreach ($parents as $value) {
                        $newSelectedCategories[] = $value['id_category'];
                    }

                }

                $newSelectedCategories = array_unique($newSelectedCategories);

                foreach ($newSelectedCategories as $selectedCategory) {
                    $currentCategory = Category::getChildren($selectedCategory, $lang, false);

                    if (!empty($currentCategory)) {
                        $categories[$selectedCategory] = $currentCategory;
                    }

                }

                $tree = Category::getCategoryInformations([$rootCategory], $lang);

                $children = $this->fillTree($categories, $rootCategory);

                if (!empty($children)) {
                    $tree[$rootCategory]['children'] = $children;
                }

                $this->setData($tree);
                $this->setDataSearch(Category::getAllCategoriesName($rootCategory, $lang, false, null));
            }

        }

        return $this->_data;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setChildrenOnly($value) {

        $this->_children_only = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setFullTree($value) {

        $this->_full_tree = $value;

        return $this;
    }

    /**
     * @return bool
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getFullTree() {

        return $this->_full_tree;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setDisabledCategories($value) {

        $this->_disabled_categories = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getDisabledCategories() {

        return $this->_disabled_categories;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setInputName($value) {

        $this->_input_name = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getInputName() {

        if (!isset($this->_input_name)) {
            $this->setInputName('categoryBox');
        }

        return $this->_input_name;
    }

    /**
     * @param int $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setLang($value) {

        $this->_lang = $value;

        return $this;
    }

    /**
     * @return int
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getLang() {

        if (!isset($this->_lang)) {
            $this->setLang($this->getContext()->employee->id_lang);
        }

        return $this->_lang;
    }

    /**
     * @return mixed
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getNodeFolderTemplate() {

        if (!isset($this->_node_folder_template)) {
            $this->setNodeFolderTemplate(static::DEFAULT_NODE_FOLDER_TEMPLATE);
        }

        return $this->_node_folder_template;
    }

    /**
     * @return mixed
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getNodeItemTemplate() {

        if (!isset($this->_node_item_template)) {
            $this->setNodeItemTemplate(static::DEFAULT_NODE_ITEM_TEMPLATE);
        }

        return $this->_node_item_template;
    }

    /**
     * @param int $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setRootCategory($value) {

        if (!Validate::isInt($value)) {
            throw new PhenyxException('Root category must be an integer value');
        }

        $this->_root_category = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getRootCategory() {

        return $this->_root_category;
    }

    /**
     * @param array $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setSelectedCategories($value) {

        if (!is_array($value)) {
            throw new PhenyxException('Selected categories value must be an array');
        }

        $this->_selected_categories = $value;

        return $this;
    }

    /**
     * @return array
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getSelectedCategories() {

        if (!isset($this->_selected_categories)) {
            $this->_selected_categories = [];
        }

        return $this->_selected_categories;
    }

    
    

    /**
     * @return mixed
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getTemplate() {

        if (!isset($this->_template)) {
            $this->setTemplate(static::DEFAULT_TEMPLATE);
        }

        return $this->_template;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setUseCheckBox($value) {

        $this->_use_checkbox = (bool) $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setUseSearch($value) {

        $this->_use_search = (bool) $value;

        return $this;
    }

    

    /**
     * @return bool
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function useCheckBox() {

        return (isset($this->_use_checkbox) && $this->_use_checkbox);
    }

    /**
     * @return bool
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function useSearch() {

        return (isset($this->_use_search) && $this->_use_search);
    }

    

    /**
     * @param null $data
     *
     * @return string
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function render($data = null) {

        if (!isset($data)) {
            $data = $this->getData();
        }

        if (isset($this->_disabled_categories)
            && !empty($this->_disabled_categories)
        ) {
            $this->_disableCategories($data, $this->getDisabledCategories());
        }

        if (isset($this->_selected_categories)
            && !empty($this->_selected_categories)
        ) {
            $this->_getSelectedChildNumbers($data, $this->getSelectedCategories());
        }

        //Default bootstrap style of search is push-right, so we add this button first
        // FIXME ^md
        //        if ($this->useSearch()) {
        //            $this->addAction(
        //                new TreeToolbarLink(
        //                    $this->getId().'-categories-search',
        //                    'Find a category:'
        //                )
        //            );
        //            $this->setAttribute('use_search', $this->useSearch());
        //        }

        $collapseAll = new TreeToolbarLink(
            'Collapse All',
            '#',
            '$(\'#' . $this->getId() . '\').tree(\'collapseAll\');$(\'#collapse-all-' . $this->getId() . '\').hide();$(\'#expand-all-' . $this->getId() . '\').show(); return false;',
            'icon-collapse-alt'
        );
        $collapseAll->setAttribute('id', 'collapse-all-' . $this->getId());
        $expandAll = new TreeToolbarLink(
            'Expand All',
            '#',
            '$(\'#' . $this->getId() . '\').tree(\'expandAll\');$(\'#collapse-all-' . $this->getId() . '\').show();$(\'#expand-all-' . $this->getId() . '\').hide(); return false;',
            'icon-expand-alt'
        );
        $expandAll->setAttribute('id', 'expand-all-' . $this->getId());
        $this->addAction($collapseAll);
        $this->addAction($expandAll);

        if ($this->useCheckBox()) {
            $checkAll = new TreeToolbarLink(
                'Check All',
                '#',
                'checkAllAssociatedCategories($(\'#' . $this->getId() . '\')); return false;',
                'icon-check-sign'
            );
            $checkAll->setAttribute('id', 'check-all-' . $this->getId());
            $uncheckAll = new TreeToolbarLink(
                'Uncheck All',
                '#',
                'uncheckAllAssociatedCategories($(\'#' . $this->getId() . '\')); return false;',
                'icon-check-empty'
            );
            $uncheckAll->setAttribute('id', 'uncheck-all-' . $this->getId());
            $this->addAction($checkAll);
            $this->addAction($uncheckAll);
            $this->setNodeFolderTemplate('tree_node_folder_checkbox.tpl');
            $this->setNodeItemTemplate('tree_node_item_checkbox.tpl');
            $this->setAttribute('use_checkbox', $this->useCheckBox());
        }

        $this->setAttribute('selected_categories', $this->getSelectedCategories());
        $this->getContext()->smarty->assign('root_category', $this->context->phenyxConfig->get('EPH_ROOT_CATEGORY'));
        $this->getContext()->smarty->assign('token', $this->context->_tools->getAdminTokenLite('AdminProducts'));

        return parent::render($data);
    }

    /**
     * @param null $data
     *
     * @return string
     * @throws PhenyxException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function renderNodes($data = null) {

        if (!isset($data)) {
            $data = $this->getData();
        }

        if (!is_array($data) && !$data instanceof Traversable) {
            throw new PhenyxException('Data value must be an traversable array');
        }

        $html = '';

        foreach ($data as $item) {

            if (array_key_exists('children', $item)
                && !empty($item['children'])
            ) {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeFolderTemplate()),
                    $this->getContext()->smarty
                )->assign(
                    [
                        'input_name' => $this->getInputName(),
                        'children'   => $this->renderNodes($item['children']),
                        'node'       => $item,
                    ]
                )->fetch();
            } else {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeItemTemplate()),
                    $this->getContext()->smarty
                )->assign(
                    [
                        'input_name' => $this->getInputName(),
                        'node'       => $item,
                    ]
                )->fetch();
            }

        }

        return $html;
    }

    /**
     * @param      $categories
     * @param null $disabledCategories
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    protected function _disableCategories(&$categories, $disabledCategories = null) {

        foreach ($categories as &$category) {

            if (!isset($disabledCategories) || in_array($category['id_category'], $disabledCategories)) {
                $category['disabled'] = true;

                if (array_key_exists('children', $category) && is_array($category['children'])) {
                    static::_disableCategories($category['children']);
                }

            } else
            if (array_key_exists('children', $category) && is_array($category['children'])) {
                static::_disableCategories($category['children'], $disabledCategories);
            }

        }

    }

    /**
     * @param      $categories
     * @param      $selected
     * @param null $parent
     *
     * @return int
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    protected function _getSelectedChildNumbers(&$categories, $selected, &$parent = null) {

        $selectedChilds = 0;

        foreach ($categories as $key => &$category) {

            if (isset($parent) && in_array($category['id_category'], $selected)) {
                $selectedChilds++;
            }

            if (isset($category['children']) && !empty($category['children'])) {
                $selectedChilds += $this->_getSelectedChildNumbers($category['children'], $selected, $category);
            }

        }

        if (!isset($parent['selected_childs'])) {
            $parent['selected_childs'] = 0;
        }

        $parent['selected_childs'] = $selectedChilds;

        return $selectedChilds;
    }

}
