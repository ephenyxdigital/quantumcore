<?php

/**
 * Class TreeToolbarSearchCore
 *
 * @since 1.9.1.0
 */
class TreeToolbarSearch extends TreeToolbarButton implements ITreeToolbarButton {
    // @codingStandardsIgnoreStart
    protected $_template = 'tree_toolbar_search.tpl';
    // @codingStandardsIgnoreEnd

    /**
     * TreeToolbarSearchCore constructor.
     *
     * @param      $label
     * @param null $id
     * @param null $name
     * @param null $class
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($label, $id, $name = null, $class = null) {
        parent::__construct($label);

        $this->setId($id);
        $this->setName($name);
        $this->setClass($class);
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function render() {

        if ($this->hasAttribute('data_search')) {
            $this->setAttribute('typeahead_source',
                $this->_renderData($this->getAttribute('data_search')));
        } else
        if ($this->hasAttribute('data')) {
            $this->setAttribute('typeahead_source',
                $this->_renderData($this->getAttribute('data')));
        }

        $adminWebpath = str_ireplace(_SHOP_CORE_DIR_, '', _EPH_ROOT_DIR_);
        $adminWebpath = preg_replace('/^' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $adminWebpath);
        $boTheme = ((Validate::isLoadedObject($this->getContext()->employee)
            && $this->getContext()->employee->bo_theme) ? $this->getContext()->employee->bo_theme : 'default');

        if (!file_exists(_EPH_BO_ALL_THEMES_DIR_ . $boTheme . DIRECTORY_SEPARATOR . 'template')) {
            $boTheme = 'default';
        }

        if ($this->getContext()->controller->ajax) {
            $path = __EPH_BASE_URI__ . $adminWebpath . '/themes/' . $boTheme . '/js/vendor/typeahead.min.js?v=' . _EPH_VERSION_;
            $html = '<script type="text/javascript">$(function(){ $.ajax({url: "' . $path . '",cache:true,dataType: "script"})});</script>';
        } else {
            $this->getContext()->controller->addJs(__EPH_BASE_URI__ . $adminWebpath . '/themes/' . $boTheme . '/js/vendor/typeahead.min.js');
        }

        return (isset($html) ? $html : '') . parent::render();
    }

    /**
     * @param $data
     *
     * @return string
     * @throws PhenyxException
     *
     * @deprecated 2.0.0
     */
    protected function _renderData($data) {

        if (!is_array($data) && !$data instanceof Traversable) {
            throw new PhenyxException('Data value must be a traversable array');
        }

        $html = '';

        foreach ($data as $item) {
            $html .= json_encode($item) . ',';

            if (array_key_exists('children', $item) && !empty($item['children'])) {
                $html .= $this->_renderData($item['children']);
            }

        }

        return $html;
    }

}
