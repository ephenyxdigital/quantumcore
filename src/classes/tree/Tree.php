<?php

/**
 * Class TreeCore
 *
 * @since 1.9.1.0
 */
class Tree {

    const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/tree';
    const DEFAULT_TEMPLATE = 'tree.tpl';
    const DEFAULT_HEADER_TEMPLATE = 'tree_header.tpl';
    const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder.tpl';
    const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item.tpl';

    // @codingStandardsIgnoreStart
    protected $_attributes;
    private $_context;
    protected $_data;
    protected $_data_search;
    protected $_headerTemplate;
    protected $_id_tree;
    private $_id;
    protected $_node_folder_template;
    protected $_node_item_template;
    protected $_template;

    /** @var string */
    private $_template_directory;
    private $_title;
    private $_no_js;

    /** @var TreeToolbar|ITreeToolbar */
    private $_toolbar;
    // @codingStandardsIgnoreEnd

    /**
     * TreeCore constructor.
     *
     * @param int   $id
     * @param mixed $data
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function __construct($id, $data = null) {

        $this->setId($id);

        if (isset($data)) {
            $this->setData($data);
        }

    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function __toString() {

        return $this->render();
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function setActions($value) {

        if (!isset($this->_toolbar)) {
            $this->setToolbar(new TreeToolbarCore());
        }

        $this->getToolbar()->setTemplateDirectory($this->getTemplateDirectory())->setActions($value);

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function getActions() {

        if (!isset($this->_toolbar)) {
            $this->setToolbar(new TreeToolbarCore());
        }

        return $this->getToolbar()->setTemplateDirectory($this->getTemplateDirectory())->getActions();
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setAttribute($name, $value) {

        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }

        $this->_attributes[$name] = $value;

        return $this;
    }

    /**
     * @param $name
     *
     * @return null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getAttribute($name) {

        return $this->hasAttribute($name) ? $this->_attributes[$name] : null;
    }

    /**
     * @param $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setAttributes($value) {

        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PhenyxException('Data value must be an traversable array');
        }

        $this->_attributes = $value;

        return $this;
    }

    /**
     * @param $idTree
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setIdTree($idTree) {

        $this->_id_tree = $idTree;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getIdTree() {

        return $this->_id_tree;
    }

    /**
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getAttributes() {

        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }

        return $this->_attributes;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setContext($value) {

        $this->_context = $value;

        return $this;
    }

    /**
     * @return Context
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getContext() {

        if (!isset($this->_context)) {
            $this->_context = Context::getContext();
        }

        return $this->_context;
    }

    /**
     * @param $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setDataSearch($value) {

        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PhenyxException('Data value must be an traversable array');
        }

        $this->_data_search = $value;

        return $this;
    }

    /**
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getDataSearch() {

        if (!isset($this->_data_search)) {
            $this->_data_search = [];
        }

        return $this->_data_search;
    }

    /**
     * @param $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setData($value) {

        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PhenyxException('Data value must be an traversable array');
        }

        $this->_data = $value;

        return $this;
    }

    /**
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     */
    public function getData() {

        if (!isset($this->_data)) {
            $this->_data = [];
        }

        return $this->_data;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setHeaderTemplate($value) {

        $this->_headerTemplate = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getHeaderTemplate() {

        if (!isset($this->_headerTemplate)) {
            $this->setHeaderTemplate(static::DEFAULT_HEADER_TEMPLATE);
        }

        return $this->_headerTemplate;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setId($value) {

        $this->_id = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getId() {

        return $this->_id;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setNodeFolderTemplate($value) {

        $this->_node_folder_template = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getNodeFolderTemplate() {

        if (!isset($this->_node_folder_template)) {
            $this->setNodeFolderTemplate(static::DEFAULT_NODE_FOLDER_TEMPLATE);
        }

        return $this->_node_folder_template;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setNodeItemTemplate($value) {

        $this->_node_item_template = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getNodeItemTemplate() {

        if (!isset($this->_node_item_template)) {
            $this->setNodeItemTemplate(static::DEFAULT_NODE_ITEM_TEMPLATE);
        }

        return $this->_node_item_template;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setTemplate($value) {

        $this->_template = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
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
     * @return Tree
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setTemplateDirectory($value) {

        $this->_template_directory = $this->_normalizeDirectory($value);

        return $this;
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTemplateDirectory() {

        if (!isset($this->_template_directory)) {
            $this->_template_directory = $this->_normalizeDirectory(
                static::DEFAULT_TEMPLATE_DIRECTORY
            );
        }

        return $this->_template_directory;
    }

    /**
     * @param $template
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTemplateFile($template) {

        if (preg_match_all('/((?:^|[A-Z])[a-z]+)/', get_class($this->getContext()->controller), $matches) !== false) {
            $controllerName = strtolower($matches[0][1]);
        }

        if ($this->getContext()->controller instanceof PluginAdminController && isset($controllerName) && file_exists(
            $this->getContext()->controller->getTemplatePath() . $controllerName . DIRECTORY_SEPARATOR . $this->getTemplateDirectory() . $template
        )
        ) {
            return $this->getContext()->controller->getTemplatePath() . $controllerName . DIRECTORY_SEPARATOR . $this->getTemplateDirectory() . $template;
        } else

        if ($this->getContext()->controller instanceof PluginAdminController && file_exists(
            $this->getContext()->controller->getTemplatePath() . $this->getTemplateDirectory() . $template
        )
        ) {
            return $this->getContext()->controller->getTemplatePath() . $this->getTemplateDirectory() . $template;
        } else

        if ($this->getContext()->controller instanceof AdminController && isset($controllerName)
            && file_exists(
                _EPH_SHOP_ADMIN_DIR_ . $this->getTemplateDirectory() . $template
            )
        ) {

            return _EPH_SHOP_ADMIN_DIR_ . $this->getTemplateDirectory() . $template;
        } else

        if ($this->getContext()->controller instanceof AdminController && isset($controllerName)
            && file_exists(
                _EPH_PLUGIN_DIR_ . 'ph_ecommerce' . '/views/templates/' . $this->getTemplateDirectory() . $template
            )
        ) {

            return _EPH_PLUGIN_DIR_ . 'ph_ecommerce' . '/views/templates/' . $this->getTemplateDirectory() . $template;
        } else {
            return $this->getTemplateDirectory() . $template;
        }

    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setNoJS($value) {

        $this->_no_js = $value;

        return $this;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setTitle($value) {

        $this->_title = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTitle() {

        return $this->_title;
    }

    /**
     * @param $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setToolbar($value) {

        if (!is_object($value)) {
            throw new PhenyxException('Toolbar must be a class object');
        }

        $reflection = new ReflectionClass($value);

        if (!$reflection->implementsInterface('ITreeToolbarCore')) {
            throw new PhenyxException('Toolbar class must implements ITreeToolbarCore interface');
        }

        $this->_toolbar = $value;

        return $this;
    }

    /**
     * @return ITreeToolbar|TreeToolbar
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getToolbar() {

        if (isset($this->_toolbar)) {

            if ($this->getDataSearch()) {
                $this->_toolbar->setData($this->getDataSearch());
            } else {
                $this->_toolbar->setData($this->getData());
            }

        }

        return $this->_toolbar;
    }

    /**
     * @param $action
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function addAction($action) {

        if (!isset($this->_toolbar)) {
            $this->setToolbar(new TreeToolbarCore());
        }

        $this->getToolbar()->setTemplateDirectory($this->getTemplateDirectory())->addAction($action);

        return $this;
    }

    /**
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function removeActions() {

        if (!isset($this->_toolbar)) {
            $this->setToolbar(new TreeToolbarCore());
        }

        $this->getToolbar()->setTemplateDirectory($this->getTemplateDirectory())->removeActions();

        return $this;
    }

    /**
     * @param null $data
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function render($data = null) {

        $jsPath = _ESHOP_JS_DIR_ . 'tree.js?v=' . _EPH_VERSION_;

        if ($this->getContext()->controller->ajax) {

            if (!$this->_no_js) {
                $html = '<script type="text/javascript">$(function(){ $.ajax({url: "' . $jsPath . '",cache:true,dataType: "script"})});</script>';
            }

        } else {
            $this->getContext()->controller->addJs($jsPath);
        }

        //Create Tree Template
        $template = $this->getContext()->smarty->createTemplate(
            $this->getTemplateFile($this->getTemplate()),
            $this->getContext()->smarty
        );

        //Assign Tree nodes
        $template->assign($this->getAttributes())->assign(
            [
                'id'      => $this->getId(),
                'nodes'   => $this->renderNodes($data),
                'id_tree' => $this->getIdTree(),
            ]
        );

        return (isset($html) ? $html : '') . $template->fetch();
    }

    /**
     * @param null $data
     *
     * @return string
     * @throws PhenyxException
     *
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
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
                        'children' => $this->renderNodes($item['children']),
                        'node'     => $item,
                    ]
                )->fetch();
            } else {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeItemTemplate()),
                    $this->getContext()->smarty
                )->assign(
                    [
                        'node' => $item,
                    ]
                )->fetch();
            }

        }

        return $html;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function renderToolbar() {

        return $this->getToolbar()->render();
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function useInput() {

        return isset($this->_input_type);
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function useToolbar() {

        return isset($this->_toolbar);
    }

    /**
     * @param $directory
     *
     * @return string
     *
     * @deprecated 2.0.0
     */
    protected function _normalizeDirectory($directory) {

        $last = $directory[strlen($directory) - 1];

        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;

            return $directory;
        }

        $directory .= DIRECTORY_SEPARATOR;

        return $directory;
    }

}
