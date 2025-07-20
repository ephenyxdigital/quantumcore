<?php

/**
 * Class HelperViewCore
 *
 * @since 1.8.1.0
 */
class HelperViewCore extends Helper {

    public $id;
    public $toolbar = true;
    public $table;
    public $token;
    
    public $view_extraCss = [];

    public $view_extraJs = [];

    /** @var string|null If not null, a title will be added on that list */
    public $title = null;
    
    public $_back_css_cache;
    
    public $_back_js_cache;
    
    public $phenyxConfig;

    /**
     * HelperViewCore constructor.
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function __construct() {
        $this->phenyxConfig = Configuration::getInstance();
        $this->_back_css_cache = $this->phenyxConfig->get('EPH_CSS_BACKOFFICE_CACHE');
        $this->_back_js_cache = $this->phenyxConfig->get('EPH_JS_BACKOFFICE_CACHE');
        $this->base_folder = 'helpers/view/';
        $this->base_tpl = 'view.tpl';
        parent::__construct();
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws SmartyException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function generateView() {

        $this->tpl = $this->createTemplate($this->base_tpl);

        if (($this->_back_css_cache || $this->context->phenyxConfig->get('EPH_JS_BACKOFFICE_CACHE')) && is_writable(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {

            if ($this->_back_css_cache) {
                $this->view_extraCss = $this->context->media->admincccCss($this->view_extraCss);
            }

            if ($this->context->phenyxConfig->get('EPH_JS_BACKOFFICE_CACHE')) {
                $this->view_extraJs = $this->context->media->admincccJS($this->view_extraJs);
            }

        }

        $this->tpl->assign(
            [
                'title'          => $this->title,
                'current'        => $this->currentIndex,
                'token'          => $this->token,
                'table'          => $this->table,
                'show_toolbar'   => $this->show_toolbar,
                'toolbar_scroll' => $this->toolbar_scroll,
                'toolbar_btn'    => $this->toolbar_btn,
                'link'           => $this->context->_link,
                'extraCss'              => $this->view_extraCss,
                'extraJs'               => $this->view_extraJs,
                'defaultFormLanguage'   => $this->default_form_language,
            ]
        );

        return parent::generate();
    }
}
