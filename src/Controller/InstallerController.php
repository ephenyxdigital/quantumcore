<?php

class InstallerController extends PhenyxController {

   public $php_self = 'installer';
    
   public $ssl = false;

    /**
     * Controller constructor.
     *
     * @global bool $useSSL SSL connection flag
     *
     * @since 1.9.1.0
     *
     * @version 1.8.1.0 Initial version
     */
    public function __construct() {

        $this->controller_type = 'front';


        $this->controller_name = get_class($this);

        if (strpos($this->controller_name, 'Controller')) {
            $this->controller_name = substr($this->controller_name, 0, -10);
        }

        parent::__construct();

        if ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') && $this->context->phenyxConfig->get('EPH_SSL_ENABLED_EVERYWHERE')) {
            $this->ssl = true;
        }

        if (isset($useSSL)) {
            $this->ssl = $useSSL;
        } else {
            $useSSL = $this->ssl;
        }
    }
  
    public function checkAccess() {

        return true;
    }

    public function viewAccess() {

        return true;
    }

    public function postProcess() {

        try {

            if ($this->ajax) {
                // from ajax-tab.php
                $action = Tools::getValue('action');
                // no need to use displayConf() here

                if (!empty($action) && method_exists($this, 'ajaxProcess' . Tools::toCamelCase($action))) {
                    $return = $this->{'ajaxProcess' . Tools::toCamelCase($action)}

                    ();
                    return $return;
                }

            }

        } catch (PhenyxException $e) {
            $this->errors[] = $e->getMessage();
        };

        return false;
    }

    protected function l($string, $class = null, $addslashes = false, $htmlentities = true) {

        if ($class === null) {
            $class = substr(get_class($this), 0, -10);
        } else

        if ((substr($class, -10)) == 'controller') {
            /* classname has changed, from AdminXXX to AdminXXXController, so we remove 10 characters and we keep same keys */
            $class = substr($class, 0, -10);
        }

        return Translate::getInstallerTranslation($string, $class, $addslashes, $htmlentities);
    }


    public function ajaxProcessSetLanguage() {

        $idLang = Tools::getValue('id_lang');
        $cookieIdLang = $this->context->cookie->id_lang;
        $configurationIdLang = $this->context->phenyxConfig->get('EPH_LANG_DEFAULT');

        $this->context->cookie->id_lang = $idLang;
        $language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject((int) $idLang)));

        if (Validate::isLoadedObject($language) && $language->active) {
            $this->_language = $this->context->language = $language;
        }

        if (Validate::isUnsignedId($this->_user->id)) {
            $user = new User($this->_user->id);

            if ($user->is_admin) {
                $user = new Employee($user->id);
            }

            $user->id_lang = $idLang;
            $user->update();
            $this->_user = $this->context->user = $user;
        }

        die(true);
    }

    /**
     * Initializes common front page content: header, footer and side columns.
     *
     * @since 1.9.1.0
     *
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function initContent() {

        $this->process();

        if ($this->usePhenyxMenuTheme()) {

            $this->usePhenyxMenu = true;
            $this->outputMenuContent();
        }

        $font = [];
        $font_types = ['bodyfont', 'headingfont', 'menufont', 'additionalfont'];

        foreach ($font_types as $font_type) {

            $font[] = $this->context->phenyxConfig->get($font_type . '_family');
        }

        $font = array_unique($font);

        $hookHeader = $this->context->_hook->exec('displayHeader');

        $faviconTemplate = !empty($this->context->phenyxConfig->get('EPH_SOURCE_FAVICON_CODE')) ? preg_replace('/\<br(\s*)?\/?\>/i', "\n", $this->context->phenyxConfig->get('EPH_SOURCE_FAVICON_CODE')) : null;

        if (!empty($faviconTemplate)) {
            $dom = new DOMDocument();
            $dom->loadHTML($faviconTemplate);
            $links = [];

            foreach ($dom->getElementsByTagName('link') as $elem) {
                $links[] = $elem;
            }

            foreach ($dom->getElementsByTagName('meta') as $elem) {
                $links[] = $elem;
            }

            $faviconHtml = '';

            foreach ($links as $link) {

                foreach ($link->attributes as $attribute) {
                    /** @var DOMElement $link */

                    if ($favicon = Tools::parseFaviconSizeTag(urldecode($attribute->value))) {
                        $attribute->value = $this->context->media->getMediaPath(_EPH_IMG_DIR_ . "favicon/favicon_{$this->context->company->id}_{$favicon['width']}_{$favicon['height']}.{$favicon['type']}");
                    }

                }

                $faviconHtml .= $dom->saveHTML($link);
            }

            if ($faviconHtml) {
                $hookHeader .= $faviconHtml;
            }

            $hookHeader .= '<meta name="msapplication-config" content="' . $this->context->media->getMediaPath(_EPH_IMG_DIR_ . "favicon/browserconfig_{$this->context->company->id}.xml") . '">';
            $hookHeader .= '<link rel="manifest" href="' . $this->context->media->getMediaPath(_EPH_IMG_DIR_ . "favicon/manifest_{$this->context->company->id}.json") . '">';
        }

        if (isset($this->php_self)) {
            // append some seo fields, canonical, hrefLang, rel prev/next
            $hookHeader .= $this->getSeoFields();
        }

        // To be removed: append extra css and metas to the header hook
        $extraCode = $this->context->phenyxConfig->getMultiple([Configuration::CUSTOMCODE_METAS, Configuration::CUSTOMCODE_CSS]);
        $extraCss = $extraCode[Configuration::CUSTOMCODE_CSS] ? '<style>' . $extraCode[Configuration::CUSTOMCODE_CSS] . '</style>' : '';
        $hookHeader .= $extraCode[Configuration::CUSTOMCODE_METAS] . $extraCss;

        $xprt = [];

        $expertFields = Tools::jsonDecode($this->context->phenyxConfig->get('EPH_EXPERT_THEME_FIELDS'), true);

        if (is_array($expertFields) && count($expertFields)) {

            foreach ($expertFields as $key => $value) {
                $xprt[$key] = $value;
            }

        }

        $this->context->smarty->assign(
            [
                'xprt'              => $xprt,
                'is_admin'          => $this->context->cookie->is_admin ? $this->context->cookie->is_admin : 0,
                'HOOK_HEADER'       => $hookHeader,
                'HOOK_TOP'          => $this->context->_hook->exec('displayTop'),
                'HOOK_LEFT_COLUMN'  => ($this->display_column_left ? $this->context->_hook->exec('displayLeftColumn') : ''),
                'HOOK_RIGHT_COLUMN' => ($this->display_column_right ? $this->context->_hook->exec('displayRightColumn') : ''),
                'usePhenyxMenu'     => $this->usePhenyxMenu,
                'menuvars'          => $this->menuVars,
                'showSlider'        => $this->context->phenyxConfig->get('EPH_HOME_SLIDER_ACTIVE'),
                'showVideo'         => $this->context->phenyxConfig->get('EPH_HOME_VIDEO_ACTIVE'),
                'videoLink'         => $this->context->phenyxConfig->get('EPH_HOME_VIDEO_LINK'),
                'showParallax'      => $this->context->phenyxConfig->get('EPH_HOME_PARALLAX_ACTIVE'),
                'imgParallax'       => $this->context->phenyxConfig->get('EPH_HOME_PARALLAX_FILE'),
                'baseUrl'           => $this->context->_link->getBaseLink(),
                'oggPic'            => $this->context->phenyxConfig->get('EPH_OGGPIC'),
                'ajax_mode'         => $this->context->phenyxConfig->get('EPH_FRONT_AJAX') ? 1 : 0,
                'fonts'             => $font,
                'load_time'         => round(microtime(true) - TIME_START, 3),
            ]
        );

        $this->context->_hook->exec('action' . ucfirst($this->php_self) . 'InitContent', []);

    }

    /**
     * Called before compiling common page sections (header, footer, columns).
     * Good place to modify smarty variables.
     *
     * @see     FrontController::initContent()
     * @since 1.9.1.0
     *
     * @version 1.8.1.0 Initial version
     */
    public function process() {}

    protected function usePhenyxMenuTheme() {

        return $this->context->phenyxConfig->get('EPH_USE_PHENYXMENU');
    }

    public function getSeoFields() {

        $content = '';
        $languages = Language::getLanguages();
        $defaultLang = $this->context->phenyxConfig->get('EPH_LANG_DEFAULT');

        switch ($this->php_self) {

        case 'cms':
            $idCms = Tools::getValue('id_cms');

            $canonical = $this->context->_link->getCMSLink((int) $idCms);
            $hreflang = $this->getHrefLang('cms', (int) $idCms, $languages, $defaultLang);

            break;
        default:
            $canonical = $this->context->_link->getPageLink($this->php_self);
            $hreflang = $this->getHrefLang($this->php_self, 0, $languages, $defaultLang);
            break;

        }

        // build new content
        $content .= '<link rel="canonical" href="' . $canonical . '">' . "\n";

        if (is_array($hreflang) && !empty($hreflang)) {

            foreach ($hreflang as $lang) {
                $content .= "$lang\n";
            }

        }

        return $content;
    }

    public function getHrefLang($entity, $idItem, $languages, $idLangDefault) {

        $links = [];

        foreach ($languages as $lang) {

            switch ($entity) {

            case 'cms':
                $lnk = $this->context->_link->getCMSLink((int) $idItem, null, null, $lang['id_lang']);
                break;
            default:
                $lnk = $this->context->_link->getPageLink($entity, null, $lang['id_lang']);
                break;
            }

            // append page number

            if ($p = Tools::getValue('p')) {
                $lnk .= "?p=$p";
            }

            $links[] = '<link rel="alternate" href="' . $lnk . '" hreflang="' . $lang['language_code'] . '">';

            if ($lang['id_lang'] == $idLangDefault) {
                $links[] = '<link rel="alternate" href="' . $lnk . '" hreflang="x-default">';
            }

        }

        return $links;
    }

    public function displayHeader($display = true) {

        Tools::displayAsDeprecated();

        $this->initHeader();
        $hookHeader = $this->context->_hook->exec('displayHeader');

        if (($this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE') || $this->context->phenyxConfig->get('EPH_JS_THEME_CACHE')) && is_writable(_EPH_THEME_DIR_ . 'cache')) {
            // CSS compressor management

            if ($this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE')) {
                $this->css_files = $this->context->media->cccCss($this->css_files);
            }

            //JS compressor management

            if ($this->context->phenyxConfig->get('EPH_JS_THEME_CACHE')) {
                $this->js_files = $this->context->media->cccJs($this->js_files);
            }

        }

        // Call hook before assign of css_files and js_files in order to include correctly all css and javascript files
        $this->context->smarty->assign(
            [
                'HOOK_HEADER'       => $hookHeader,
                'HOOK_TOP'          => $this->context->_hook->exec('displayTop'),
                'HOOK_LEFT_COLUMN'  => ($this->display_column_left ? $this->context->_hook->exec('displayLeftColumn') : ''),
                'HOOK_RIGHT_COLUMN' => ($this->display_column_right ? $this->context->_hook->exec('displayRightColumn', ['cart' => $this->context->cart]) : ''),
                'HOOK_FOOTER'       => $this->context->_hook->exec('displayFooter'),
            ]
        );

        $this->context->smarty->assign(
            [
                'css_files' => $this->css_files,
                'js_files'  => ($this->getLayout() && (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER')) ? [] : $this->js_files,
            ]
        );

        $this->display_header = $display;
        $this->smartyOutputContent(_EPH_THEME_DIR_ . 'header.tpl');
    }

    public function displayAjaxHeader($js_def) {

        $this->initHeader();
        $hookHeader = $this->context->_hook->exec('displayHeader');

        if (($this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE') || $this->context->phenyxConfig->get('EPH_JS_THEME_CACHE')) && is_writable(_EPH_THEME_DIR_ . 'cache')) {
            // CSS compressor management

            if ($this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE')) {
                $this->css_files = $this->context->media->cccCss($this->css_files);
            }

            //JS compressor management

            if ($this->context->phenyxConfig->get('EPH_JS_THEME_CACHE')) {
                $this->js_files = $this->context->media->cccJs($this->js_files);
            }

        }

        // Call hook before assign of css_files and js_files in order to include correctly all css and javascript files
        $this->context->smarty->assign(
            [
                'HOOK_HEADER' => $hookHeader,
            ]
        );

        $this->context->smarty->assign(
            [
                'css_files' => $this->css_files,
                'js_files'  => ($this->getLayout() && (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER')) ? [] : $this->js_files,
                'js_def'    => $js_def,
            ]
        );

        $this->ajax_head = $this->context->smarty->fetch(_EPH_THEME_DIR_ . 'head.tpl');
    }

    public function displayAjaxFooter() {

        $this->context->smarty->assign(
            [
                'HOOK_FOOTER' => $this->context->_hook->exec('displayFooter'),
                'js_footers'  => ($this->getLayout() && (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER')) ? [] : $this->js_footers,
            ]
        );
        return $this->context->smarty->fetch(_EPH_THEME_DIR_ . 'footer_ajax.tpl');
    }

    public function initHeader() {

        // Added powered by for builtwith.com
        header('Powered-By: ephenyx');
        // Hooks are voluntary out the initialize array (need those variables already assigned)

        if ($this->_user->isLogged() && $this->_user->is_admin) {
            $this->customer_is_admin = true;
            $this->context->smarty->assign(
                [

                    'default_tab_link' => $this->context->_link->getAdminLink('admindashboard'),

                ]
            );
        }

        $this->context->smarty->assign(
            [
                'time'                  => time(),
                'img_update_time'       => $this->context->phenyxConfig->get('EPH_IMG_UPDATE_TIME'),
                'static_token'          => Tools::getToken(false),
                'token'                 => Tools::getToken(),
                'priceDisplayPrecision' => _EPH_PRICE_DISPLAY_PRECISION_,
                'content_only'          => (int) Tools::getValue('content_only'),
                'customer_is_admin'     => $this->customer_is_admin,
                'head_script'           => $this->context->phenyxConfig->get('EPH_HEAD_SCRIPT'),

            ]
        );

        $this->context->smarty->assign($this->initLogoAndFavicon());
    }

    public function initLogoAndFavicon() {

        $mobileDevice = $this->isMobileDevice();

        if ($mobileDevice && $this->context->phenyxConfig->get('EPH_LOGO_MOBILE')) {
            $logo = $this->context->_link->getMediaLink(_EPH_IMG_ . $this->context->phenyxConfig->get('EPH_LOGO_MOBILE') . '?' . $this->context->phenyxConfig->get('EPH_IMG_UPDATE_TIME'));
        } else {
            $logo = $this->context->_link->getMediaLink(_EPH_IMG_ . $this->context->phenyxConfig->get('EPH_LOGO'));
        }

        return [
            'favicon_url'       => _EPH_IMG_ . $this->context->phenyxConfig->get('EPH_FAVICON'),
            'logo_image_width'  => ($mobileDevice == false ? $this->context->phenyxConfig->get('SHOP_LOGO_WIDTH') : $this->context->phenyxConfig->get('SHOP_LOGO_MOBILE_WIDTH')),
            'logo_image_height' => ($mobileDevice == false ? $this->context->phenyxConfig->get('SHOP_LOGO_HEIGHT') : $this->context->phenyxConfig->get('SHOP_LOGO_MOBILE_HEIGHT')),
            'logo_url'          => $logo,
        ];
    }

    public function displayAjax() {

        if (($this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE') || $this->context->phenyxConfig->get('EPH_JS_THEME_CACHE')) && is_writable(_EPH_THEME_DIR_ . 'cache')) {

            if ($this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE')) {
                $this->css_files = $this->context->media->cccCss($this->css_files);
            }

            if ($this->context->phenyxConfig->get('EPH_JS_THEME_CACHE')) {
                $this->js_files = $this->context->media->cccJs($this->js_files);
            }

        }

        $this->context->smarty->assign(
            [
                'css_files'      => $this->css_files,
                'js_files'       => ($this->getLayout() && (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER')) ? [] : $this->js_files,
                'js_defer'       => (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER'),
                'js_footers'     => ($this->getLayout() && (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER')) ? [] : $this->js_footers,
                'display_header' => true,
                'display_footer' => true,

            ]
        );

        $layout = $this->getLayout();

        if ($layout) {

            if ($this->template) {

                $template = $this->context->smarty->fetch($this->template);
            }

            $this->context->smarty->assign('template', $template);
            $this->ajaxOutputContent($layout);
        }

    }

    public function getLayout() {

        $entity = $this->php_self;
        $idItem = (int) Tools::getValue('id_' . $entity);

        $layoutDir = $this->getThemeDir();
        $layoutOverrideDir = $this->getOverrideThemeDir();
        $extralayoutDir = $this->context->_hook->exec('getextralayoutDir', [], null, true);

        if (is_array($extralayoutDir) && count($extralayoutDir)) {

            foreach ($extralayoutDir as $plugin => $dir) {
                $layoutDir = $dir;
            }

        }

        $layout = false;

        if ($entity) {

            if ($idItem > 0 && file_exists($layoutOverrideDir . 'layout-' . $entity . '-' . $idItem . '.tpl')) {
                $layout = $layoutOverrideDir . 'layout-' . $entity . '-' . $idItem . '.tpl';
            } else
            if (file_exists($layoutDir . 'layout-' . $entity . '.tpl')) {
                $layout = $layoutDir . 'layout-' . $entity . '.tpl';
            } else

            if (file_exists($layoutOverrideDir . 'layout-' . $entity . '.tpl')) {
                $layout = $layoutOverrideDir . 'layout-' . $entity . '.tpl';
            }

        }

        if (!$layout && file_exists($layoutDir . 'layout.tpl')) {
            $layout = $layoutDir . 'layout.tpl';
        }

        return $layout;
    }

    protected function getThemeDir() {

        return _EPH_THEME_DIR_;
    }

    protected function getOverrideThemeDir() {

        return _EPH_THEME_OVERRIDE_DIR_;
    }

    protected function smartyOutputContent($content) {

        $html = '';
        $js_def = $this->context->media->getJsDef();

        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);

        if (is_array($content)) {

            foreach ($content as $tpl) {
                $html .= $this->context->smarty->fetch($tpl);
            }

        } else {
            $html = $this->context->smarty->fetch($content);
        }

        $html = trim($html);
        //fwrite($file,$html.PHP_EOL.PHP_EOL);

        if (!empty($html) && $this->getLayout()) {
            $javasFooter = '';
            $domAvailable = extension_loaded('dom') ? true : false;
            $defer = (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER');

            if ($defer && $domAvailable) {
                $html = $this->context->media->deferInlineScripts($html);
            }

            if (count($this->js_footers)) {
                $this->js_files = array_diff($this->js_files, $this->js_footers);
            }

            $html = trim(str_replace(['</body>', '</html>'], '', $html)) . "\n";

            $this->context->smarty->assign([
                'js_def'    => $js_def,
                'js_files'  => $defer ? array_unique($this->js_files) : [],
                'js_inline' => ($defer && $domAvailable) ? $this->context->media->getInlineScript() : [],
            ]);

            $javascript = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl');

            if (count($this->js_footers)) {
                $this->context->smarty->assign([
                    'js_def'     => null,
                    'js_files'   => [],
                    'js_inline'  => [],
                    'js_footers' => $defer ? array_unique($this->js_footers) : [],
                ]);

                $javasFooter = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl') . PHP_EOL . '<div id="pdf_container"></div>';
            } else {
                $javascript = $javascript . PHP_EOL . '<div id="pdf_container"></div>';
            }

            //fwrite($file,$javascript.PHP_EOL);

            if ($defer) {
                $templ = $html . $javascript . $javasFooter;
            } else {
                $templ = preg_replace('/(?<!\$)' . $jsTag . '/', $javascript . $javasFooter, $html);
            }

            $templ .= ((!Tools::getIsset($this->ajax) || !$this->ajax) ? '</body></html>' : '');

        } else {
            $templ = $html;
        }

        if ($this->context->cache_enable) {
            $temp = $templ === null ? null : Tools::jsonEncode($templ);
            $this->context->cache_api->putData('pageFrontCache_' . $this->php_self, $temp, 864000);
        }

        if ($this->ajax) {
            return $templ;
        }

        echo $templ;
    }

    protected function ajaxOutputContent($content) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);

        if (is_array($content)) {

            foreach ($content as $tpl) {
                $html .= $this->context->smarty->fetch($tpl);
            }

        } else {
            $html = $this->context->smarty->fetch($content);
        }

        $html = trim($html);

        $domAvailable = extension_loaded('dom') ? true : false;
        $defer = (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER');
        $this->ajax_head = null;

        $html = trim(str_replace(['</body>', '</html>'], '', $html)) . "\n";
        $deferTagOutput = $this->context->media->deferTagOutput('head', $html);

        if (!is_null($deferTagOutput)) {
            $this->ajax_head = str_replace(['<head>', '</head>'], '', $this->context->media->deferTagOutput('head', $html));
        }

        $page = $this->context->media->deferIdOutput('page', $html);
        $this->context->smarty->assign(
            [
                $jsTag      => $this->context->media->getJsDef(),
                'js_files'  => $defer ? array_unique($this->js_files) : [],
                'js_inline' => [],
            ]
        );
        $javascript = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl');

        if ($defer) {
            $templ = $page . $javascript;
            $return = [
                'historyState' => $this->historyState,
                'page_title'   => $this->page_title,
                'ajax_head'    => $this->ajax_head,
                'html'         => $templ,
            ];

        } else {
            $templ = preg_replace('/(?<!\$)' . $jsTag . '/', $javascript, $page);
            $return = [
                'historyState' => $this->historyState,
                'page_title'   => $this->page_title,
                'ajax_head'    => $this->ajax_head,
                'html'         => $templ,
            ];
        }

        die(Tools::jsonEncode($return));

    }

    public function displayFooter($display = true) {

        Tools::displayAsDeprecated();
        $this->smartyOutputContent(_EPH_THEME_DIR_ . 'footer.tpl');
    }

    public function initCursedPage() {

        $this->displayMaintenancePage();
    }

    protected function displayMaintenancePage() {

        if ($this->maintenance == true || !(int) $this->context->phenyxConfig->get('EPH_SHOP_ENABLE')) {
            $this->maintenance = true;
            $allowed = false;

            if (!empty($this->context->phenyxConfig->get('EPH_MAINTENANCE_IP'))) {
                $allowed = in_array(Tools::getRemoteAddr(), explode(',', $this->context->phenyxConfig->get('EPH_MAINTENANCE_IP')));
            }

            if (!$allowed) {
                header('HTTP/1.1 503 temporarily overloaded');
                $this->addCSS($this->context->theme->css_theme . 'maintenance.css', 'all');
                $this->setMedia();
                $this->context->smarty->assign($this->initLogoAndFavicon());
                $maintenance_text = $this->context->smarty->fetch('string:' . $this->context->phenyxConfig->get('EPH_MAINTENANCE_TEXT', (int) $this->context->language->id));
                $this->context->smarty->assign(
                    [
                        'HOOK_HEADER'      => $this->context->_hook->exec('displayHeader'),
                        'HOOK_MAINTENANCE' => $this->context->_hook->exec('displayMaintenance', []),
                        'maintenance_text' => $maintenance_text,
                        'css_files'        => $this->css_files,
                        'js_files'         => $this->js_files,
                        'is_admin'         => $this->context->cookie->is_admin ? $this->context->cookie->is_admin : 0,
                        'link'             => $this->context->_link,
                    ]
                );

                // If the controller is a plugin, then getTemplatePath will try to find the template in the plugins, so we need to instanciate a real frontcontroller
                $frontController = preg_match('/PluginFrontController$/', get_class($this)) ? new FrontController() : $this;
                $this->smartyOutputContent($frontController->getTemplatePath($this->getThemeDir() . 'maintenance.tpl'));
                exit;
            }

        }

    }

    public function getTemplatePath($template) {

        return $template;
    }

    public function display() {

        Tools::safePostVars();

        if ($this->cachable) {

            if ($this->context->cache_enable) {
                $cache = $this->context->cache_api;

                if (is_object($this->context->cache_api)) {
                    $value = $this->context->cache_api->getData('pageFrontCache_' . $this->php_self);
                    $temp = empty($value) ? null : Tools::jsonDecode($value, true);

                    if (!empty($temp)) {

                        if ($this->ajax) {
                            return $temp;
                        }

                        echo $templ;
                    }

                }

            }

        }

        // assign css_files and js_files at the very last time

        if (($this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE') || $this->context->phenyxConfig->get('EPH_JS_THEME_CACHE')) && is_writable(_EPH_THEME_DIR_ . 'cache')) {
            // CSS compressor management

            if ($this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE')) {
                $this->css_files = $this->context->media->cccCss($this->css_files);
            }

            //JS compressor management

            if ($this->context->phenyxConfig->get('EPH_JS_THEME_CACHE')) {
                $this->js_files = $this->context->media->cccJs($this->js_files);
            }

        }

        $this->context->smarty->assign(
            [
                'css_files'      => $this->css_files,
                'js_files'       => ($this->getLayout() && (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER')) ? [] : $this->js_files,
                'js_defer'       => (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER'),
                'js_footers'     => $this->js_footers,
                'errors'         => $this->errors,
                'display_header' => $this->display_header,
                'display_footer' => $this->display_footer,
                'img_formats'    => ['webp' => 'image/webp', 'jpg' => 'image/jpeg'],

            ]
        );

        if (_EPH_DEBUG_PROFILING_) {

            $this->context->smarty->assign(
                [
                    'profiling_mode'   => true,
                    'profiling_report' => $this->displayProfiling(),
                ]
            );
        }

        $layout = $this->getLayout();

        if ($layout) {

            if ($this->template) {
                $template = $this->context->smarty->fetch($this->template);
                $this->context->smarty->assign('template', $template);
                $this->smartyOutputContent($layout);
            }

        } else {
            Tools::displayAsDeprecated('layout.tpl is missing in your theme directory');

            if ($this->display_header) {
                $this->smartyOutputContent(_EPH_THEME_DIR_ . 'header.tpl');
            }

            if ($this->template) {
                $this->smartyOutputContent($this->template);
            } else {
                // For retrocompatibility with 1.4 controller
                $this->displayContent();
            }

            if ($this->display_footer) {
                $this->smartyOutputContent(_EPH_THEME_DIR_ . 'footer.tpl');
            }

        }

        return true;
    }

    public function displayContent() {}

    public function setAjaxMedia() {

        $this->push_js_files = [];
        $this->context->_hook->exec('action' . $this->controller_name . 'SetAjaxMedia');
    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);

        $this->addCSS($this->context->theme->css_theme . 'root.css', 'all');
        $this->addCSS($this->context->theme->css_theme . 'grid_ephenyxshop.css', 'all'); // retro compat themes 1.5.0.1
        $this->addCSS($this->context->theme->css_theme . 'global.css', 'all');
        $this->addCSS(_EPH_CSS_DIR_ . 'fontawesome/css/all.css');

        $this->addjQueryPlugin('sweetalert');
        $this->addJS(_EPH_JS_DIR_ . 'tools.js');
        $this->addJS(_EPH_JS_DIR_ . 'imageuploadify.min.js');
        $this->addJS(_EPH_JS_DIR_ . 'pdfuploadify.min.js');
        $this->addJS($this->context->theme->js_theme . 'global.js');
        $this->addJS(_EPH_JS_DIR_ . 'composer/composer_front.js');
        $this->addJS(_EPH_JS_DIR_ . 'ace/ace.js');

        // @since 1.0.4
        $this->context->media->addJsDef([
            'useLazyLoad'   => $this->context->phenyxConfig->get('EPH_LAZY_LOAD') ? 1 : 0,
            'useWebp'       => ($this->context->phenyxConfig->get('EPH_USE_WEBP') && function_exists('imagewebp')) ? 1 : 0,
            'AjaxMemberId'  => $this->context->user->id ? $this->context->user->id : null,
            'AjaxLinkIndex' => $this->context->_link->getPageLink('index', true),
            'ajaxCmsLink'   => $this->context->_link->getPageLink('cms', true),
            'ajaxFormLink'  => $this->context->_link->getPageLink('pfg', true),
            'AjaxAuthLink'  => $this->context->_link->getPageLink('authentication', true),
            'AjaxFrontLink' => $this->context->_link->getPageLink('front', true),
            'css_dir'       => $this->context->theme->css_theme,
            'js_dir'        => $this->context->theme->js_theme,
            'baseDir'       => Tools::getDomainSsl(true),
        ]);

        $this->context->media->addJsDefL('closeTag', $this->l('Back Home'));
        $this->context->media->addJsDefL('uploadPdf', $this->l('Upload replacement PDF'));

        if ($this->customer_is_admin) {
            $this->context->media->addJsDef(['customer_is_admin' => 1]);
        } else {
            $this->context->media->addJsDef(['customer_is_admin' => 0]);
        }

        if (@filemtime($this->getThemeDir() . 'js/autoload/')) {

            foreach (scandir($this->getThemeDir() . 'js/autoload/', 0) as $file) {

                if (preg_match('/^[^.].*\.js$/', $file)) {
                    $this->addJS($this->getThemeDir() . 'js/autoload/' . $file);
                }

            }

        }

        if (@filemtime($this->getThemeDir() . 'css/autoload/')) {

            foreach (scandir($this->getThemeDir() . 'css/autoload', 0) as $file) {

                if (preg_match('/^[^.].*\.css$/', $file)) {
                    $this->addCSS($this->getThemeDir() . 'css/autoload/' . $file);
                }

            }

        }

        $this->addjqueryPlugin('fancybox');

        $this->addCSS($this->context->theme->css_theme . 'layout.css', 'all');

        if ($this->usePhenyxMenuTheme()) {
            $this->setPhenyxMenuMedia();
        }

        $this->addCSS(_EPH_CSS_DIR_ . 'js_composer.css');
        $this->addCSS(_EPH_CSS_DIR_ . 'animate.min.css');

        if (_EPH_DEBUG_PROFILING_) {
            $this->addCSS(_EPH_ADMIN_THEME_DIR_ . '/blacktie/css/profilling.css');
            $this->addJS(_EPH_JS_DIR_ . 'profilling.js');
            $this->context->media->addJsDef(['profiling_title' => $this->l('Profilling report')]);
        }

        $this->context->_hook->exec('actionFrontControllerSetMedia', []);

        if (isset($this->php_self)) {
            $this->context->_hook->exec('action' . ucfirst($this->php_self) . 'SetMedia', []);
        }

        $this->assignExtramedia();
        return true;
    }

    public function ajaxProcessGenerateHtmlLogo() {

        $this->context->smarty->assign(
            [
                'base_dir_ssl' => 'https://' . Tools::getDomainSsl() . __EPH_BASE_URI__,
                'shop_name'    => $this->context->phenyxConfig->get('EPH_SHOP_NAME'),
            ]
        );
        $this->context->smarty->assign($this->initLogoAndFavicon());

        $return = [
            'html' => $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'logo.tpl'),
        ];

        die(Tools::jsonEncode($return));

    }

    public function assignExtramedia() {}

    public function setPhenyxMenuMedia() {

        $advtmIsSticky = ($this->context->phenyxConfig->get('EPHTM_MENU_CONT_POSITION') == 'sticky');

        $this->addCSS(_SHOP_ROOT_DIR_ . '/css/ephtopmenu_product.css', 'all');

        if ($advtmIsSticky) {
            $this->addJS(_EPH_JS_DIR_ . 'jquery.sticky.js');
        }

        $this->addJqueryPlugin("autocomplete");

        $this->addCSS(_EPH_CSS_DIR_ . 'material-design-iconic-font.min.css', 'all');

        $this->context->media->addJsDef([
            'ephtm_isToggleMode'   => $this->context->phenyxConfig->get('EPHTM_RESP_TOGGLE_ENABLED') ? (bool) $this->context->phenyxConfig->get('EPHTM_RESP_TOGGLE_ENABLED') : 0,
            'ephtm_stickyOnMobile' => 0,
        ]);

    }

    public function addJS($jsUri, $checkPath = true) {

        return $this->addMedia($jsUri, null, null, false, $checkPath);
    }

    public function addMedia($mediaUri, $cssMediaType = null, $offset = null, $remove = false, $checkPath = true) {

        if (!is_array($mediaUri)) {

            if ($cssMediaType) {
                $mediaUri = [$mediaUri => $cssMediaType];
            } else {
                $mediaUri = [$mediaUri];
            }

        }

        $listUri = [];

        foreach ($mediaUri as $file => $media) {

            if (!Validate::isAbsoluteUrl($media)) {
                $different = 0;
                $differentCss = 0;
                $type = 'css';

                if (!$cssMediaType) {
                    $type = 'js';
                    $file = $media;
                }

                if (str_contains($file, __EPH_BASE_URI__ . 'plugins/')) {

                    $overridePath = str_replace(__EPH_BASE_URI__ . 'includes/plugins/', _EPH_THEME_DIR_ . $type . '/plugins/', $file, $different);

                    if (str_contains($overridePath, $type . '/' . basename($file))) {

                        $overridePathCss = str_replace($type . '/' . basename($file), basename($file), $overridePath, $differentCss);

                    }

                    if ($different && @filemtime($overridePath)) {
                        $file = str_replace(__EPH_BASE_URI__ . 'includes/plugins/', _THEME_DIR_ . $type . '/plugins/', $file, $different);
                    } else

                    if ($differentCss && isset($overridePathCss) && @filemtime($overridePathCss)) {
                        $file = $overridePathCss;
                    }

                    if ($cssMediaType) {
                        $listUri[$file] = $media;
                    } else {
                        $listUri[] = $file;
                    }

                } else {
                    $listUri[$file] = $media;
                }

            } else {
                $listUri[$file] = $media;
            }

        }

        if ($remove) {

            if ($cssMediaType) {
                parent::removeCSS($listUri, $cssMediaType);

                return true;
            }

            parent::removeJS($listUri);

            return true;
        }

        if ($cssMediaType) {
            parent::addCSS($listUri, $cssMediaType, $offset, $checkPath);

            return true;
        }

        parent::addJS($listUri, $checkPath);

        return true;
    }

    public function addCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true) {

        return $this->addMedia($cssUri, $cssMediaType, $offset = null, false, $checkPath);
    }

    public function initFooter() {

        $hookFooter = $this->context->_hook->exec('displayFooter');

        if (($this->context->phenyxConfig->get('EPH_JS_BACKOFFICE_CACHE')) && is_writable(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {

            $this->js_footers = $this->context->media->admincccJS($this->js_footers);

        }

        $this->context->smarty->assign(
            [
                'HOOK_FOOTER' => $hookFooter,
                'js_footers'  => ($this->getLayout() && (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER')) ? [] : $this->js_footers,
            ]
        );

        if ($this->context->language->is_rtl) {
            $this->addCSS($this->context->theme->css_theme . 'rtl.css');
            $this->addCSS($this->context->theme->css_theme . $this->context->language->iso_code . '.css');
        }

    }

    public function compileJsDefController($curJsDefs, $js_defs) {

        $jsDef = [];
        $javascript = '';

        if (is_array($curJsDefs) && count($curJsDefs)) {

            foreach ($curJsDefs as $key => $value) {

                if (isset($js_defs[$key])) {
                    continue;
                } else {
                    $js_defs[$key] = str_replace("'", '', $value);
                }

            }

        }

        if (count($js_defs)) {

            foreach ($js_defs as $key => $js_def) {
                $jsDef[$key] = $js_def;
            }

            $this->context->smarty->assign('js_def', $jsDef);
            $javascript = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'jsdef.tpl');
        }

        return $javascript;
    }

    public function combineJs($curJs) {

        $return = '';
        $jsFile = [];

        if (is_array($curJs) && count($curJs)) {

            foreach ($curJs as $key => $js_file) {

                if (in_array($js_file, $this->js_files)) {
                    continue;

                } else {
                    $this->js_files[] = $js_file;
                }

            }

        }

        return $this->js_files;

    }

    public function combineCss($curCss) {

        $return = '';
        $cssFile = [];

        if (is_array($this->css_files) && is_array($curCss)) {
            $cssFiles = [];

            foreach ($curCss as $css_file) {

                if (isset($this->css_files[$css_file])) {
                    continue;
                } else {
                    $this->css_files[$css_file] = 'all';
                }

            }

        }

        return $this->css_files;

    }

    public function init() {

        /*
                                             * Globals are DEPRECATED as of version 1.5.0.1
                                             * Use the Context object to access objects instead.
                                             * Example: $this->context->cart
        */
        global $useSSL, $cookie, $smarty, $iso, $defaultCountry, $protocolLink, $protocolContent, $link, $cssFiles, $jsFiles;

        if (static::$initialized) {
            return;
        }

        static::$initialized = true;

        parent::init();

        // If current URL use SSL, set it true (used a lot for plugin redirect)

        if (Tools::usingSecureMode()) {
            $useSSL = true;
        }

        // For compatibility with globals, DEPRECATED as of version 1.5.0.1
        $cssFiles = $this->css_files;
        $jsFiles = $this->js_files;

        $this->sslRedirection();

        // If account created with the 2 steps register process, remove 'account_created' from cookie

        if (isset($this->context->cookie->account_created)) {
            $this->context->smarty->assign('account_created', 1);
            unset($this->context->cookie->account_created);
        }

        ob_start();

        // Init cookie language
        // @TODO This method must be moved into switchLanguage
        Tools::setCookieLanguage($this->context->cookie);

        $protocolLink = ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') || Tools::usingSecureMode()) ? 'https://' : 'http://';
        $useSSL = ((isset($this->ssl) && $this->ssl && $this->context->phenyxConfig->get('EPH_SSL_ENABLED')) || Tools::usingSecureMode()) ? true : false;
        $protocolContent = ($useSSL) ? 'https://' : 'http://';
        $link = new Link($protocolLink, $protocolContent);
        $this->context->_link = $link;

        if ($this->auth && !$this->_user->isLogged($this->guestAllowed)) {
            Tools::redirect('index.php');
        }

        /* Theme is missing */

        if (!is_dir(_EPH_THEME_DIR_)) {
            throw new PhenyxException((sprintf($this->l('Current theme unavailable "%s". Please check your theme directory name and permissions.'), basename(rtrim(_EPH_THEME_DIR_, '/\\')))));
        }

        if ($this->context->phenyxConfig->get('EPH_GEOLOCATION_ENABLED')) {

            if (($newDefault = $this->geolocationManagement($this->context->country)) && Validate::isLoadedObject($newDefault)) {
                $this->context->country = $newDefault;
            }

        } else

        if ($this->context->phenyxConfig->get('EPH_DETECT_COUNTRY')) {

            $hasCountry = isset($this->context->cookie->iso_code_country) && $this->context->cookie->iso_code_country;

            if (!$hasCountry) {
                $idCountry = $hasCountry && !Validate::isLanguageIsoCode($this->context->cookie->iso_code_country) ?
                (int) Country::getByIso(strtoupper($this->context->cookie->iso_code_country)) : (int) Tools::getCountry();

                $country = Tools::jsonDecode(Tools::jsonEncode(Country::buildObject($idCountry, (int) $this->context->cookie->id_lang)));

                if (validate::isLoadedObject($country) && $this->context->country->id !== $country->id) {
                    $this->context->country = $country;
                    $this->context->cookie->iso_code_country = strtoupper($country->iso_code);
                }

            }

        }

        if (isset($_GET['logout']) || ($this->_user->logged && User::isBanned($this->_user->id))) {
            $this->_user->logout();
            $this->context->employee->logout();

            Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
        } else

        if (isset($_GET['mylogout'])) {
            $this->_user->mylogout();
            Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
        }

        /* get page name to display it in body id */

        // Are we in a payment plugin
        $pluginName = '';

        if (Validate::isPluginName(Tools::getValue('plugin'))) {
            $pluginName = Tools::getValue('plugin');
        }

        if (!empty($this->page_name)) {
            $pageName = $this->page_name;
        } else

        if (!empty($this->php_self)) {
            $pageName = $this->php_self;
        } else

        if (preg_match('#^' . preg_quote($this->context->company->physical_uri, '#') . 'plugins/([a-zA-Z0-9_-]+?)/(.*)$#', $_SERVER['REQUEST_URI'], $m)) {
            $pageName = 'plugin-' . $m[1] . '-' . str_replace(['.php', '/'], ['', '-'], $m[2]);
        } else {
            $pageName = Performer::getInstance()->getController();
            $pageName = (preg_match('/^[0-9]/', $pageName) ? 'page_' . $pageName : $pageName);
        }

        $this->context->smarty->assign(Meta::getMetaTags($this->context->language->id, $pageName));
        $this->context->smarty->assign('request_uri', Tools::safeOutput(urldecode($_SERVER['REQUEST_URI'])));

        /* Breadcrumb */
        $navigationPipe = ($this->context->phenyxConfig->get('EPH_NAVIGATION_PIPE') ? $this->context->phenyxConfig->get('EPH_NAVIGATION_PIPE') : '>');
        $this->context->smarty->assign('navigationPipe', $navigationPipe);

        // Automatically redirect to the canonical URL if needed

        if (!empty($this->php_self) && !Tools::getValue('ajax')) {
            $this->canonicalRedirection($this->context->_link->getPageLink($this->php_self, $this->ssl, $this->context->language->id));
        }

        $languages = Language::getLanguages(true);
        $metaLanguage = [];

        foreach ($languages as $lang) {
            $metaLanguage[] = $lang['iso_code'];
        }

        $this->context->media->addJsDef([
            'page_name' => $pageName,
            'is_mobile' => $this->isMobileDevice(),
        ]);
        $this->context->cookie->page_name = $pageName;

        $this->context->smarty->assign(
            [
                // Useful for layout.tpl
                'mobile_device'     => $this->context->getMobileDevice(),
                'is_mobile'         => $this->isMobileDevice() ? 1 : 0,
                'link'              => $link,
                'cookie'            => $this->context->cookie,
                'page_name'         => $pageName,
                'hide_left_column'  => !$this->display_column_left,
                'hide_right_column' => !$this->display_column_right,
                'base_dir'          => _EPH_BASE_URL_ . __EPH_BASE_URI__,
                'base_dir_ssl'      => $protocolLink . Tools::getDomainSsl() . __EPH_BASE_URI__,
                'force_ssl'         => $this->context->phenyxConfig->get('EPH_SSL_ENABLED') && $this->context->phenyxConfig->get('EPH_SSL_ENABLED_EVERYWHERE'),
                'content_dir'       => $protocolContent . Tools::getHttpHost() . __EPH_BASE_URI__,
                'base_uri'          => $protocolContent . Tools::getHttpHost() . __EPH_BASE_URI__ . (!$this->context->phenyxConfig->get('EPH_REWRITING_SETTINGS') ? 'index.php' : ''),
                'tpl_dir'           => _EPH_THEME_DIR_,
                'tpl_uri'           => _THEME_DIR_,
                'plugins_dir'       => _PLUGIN_DIR_,
                'mail_dir'          => _MAIL_DIR_,
                'lang_iso'          => $this->context->language->iso_code,
                'lang_id'           => (int) $this->context->language->id,
                'isRtl'             => $this->context->language->is_rtl,
                'language_code'     => $this->context->language->language_code ? $this->context->language->language_code : $this->context->language->iso_code,
                'come_from'         => Tools::getHttpHost(true, true) . Tools::htmlentitiesUTF8(str_replace(['\'', '\\'], '', urldecode($_SERVER['REQUEST_URI']))),
                'languages'         => $languages,
                'meta_language'     => implode(',', $metaLanguage),
                'is_logged'         => (bool) $this->_user->isLogged(),
                'add_prod_display'  => (int) $this->context->phenyxConfig->get('EPH_ATTRIBUTE_CATEGORY_DISPLAY'),
                'shop_name'         => $this->context->phenyxConfig->get('EPH_SHOP_NAME'),
                'shop_phone'        => $this->context->phenyxConfig->get('EPH_SHOP_PHONE'),
                'high_dpi'          => (bool) $this->context->phenyxConfig->get('EPH_HIGHT_DPI'),
                'lazy_load'         => (bool) $this->context->phenyxConfig->get('EPH_LAZY_LOAD'),
                'webp'              => (bool) $this->context->phenyxConfig->get('EPH_USE_WEBP') && function_exists('imagewebp'),
            ]
        );

        // Deprecated
        $this->context->smarty->assign(
            [

                'logged'       => $this->_user->isLogged(),
                'customerName' => ($this->_user->logged ? $this->context->cookie->user_firstname . ' ' . $this->context->cookie->user_lastname : false),
            ]
        );

        $assignArray = [
            'img_ps_dir'   => _EPH_IMG_,
            'img_lang_dir' => _THEME_LANG_DIR_,
            'img_dir'      => $this->context->theme->img_theme,
            'css_dir'      => $this->context->theme->css_theme,
            'js_dir'       => $this->context->theme->js_theme,
        ];

        foreach ($assignArray as $assignKey => $assignValue) {

            if (substr($assignValue, 0, 1) == '/' || $protocolContent == 'https://') {
                $this->context->smarty->assign($assignKey, $protocolContent . Tools::getMediaServer($assignValue) . $assignValue);
            } else {
                $this->context->smarty->assign($assignKey, $assignValue);
            }

        }

        static::$cookie = $this->context->cookie;
        static::$smarty = $this->context->smarty;
        static::$link = $link;

        $this->displayMaintenancePage();

        $this->context->_hook->exec('actionInitFrontController');

        if ($this->restrictedCountry) {
            $this->displayRestrictedCountryPage();
        }

        $this->iso = $iso;

    }

    public function isMobileDevice() {

        if (isset($_SERVER["HTTP_USER_AGENT"])) {
            return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
        }

    }

    public function outputMenuContent() {

        $menus = TopMenu::getMenus($this->context->cookie->id_lang, true, false, true);
        $advtmThemeCompatibility = (bool) $this->context->phenyxConfig->get('EPHTM_THEME_COMPATIBILITY_MODE') && ((bool) $this->context->phenyxConfig->get('EPHTM_MENU_CONT_HOOK') == 'top');
        $advtmResponsiveMode = ((bool) $this->context->phenyxConfig->get('EPHTM_RESPONSIVE_MODE') && (int) $this->context->phenyxConfig->get('EPHTM_RESPONSIVE_THRESHOLD') > 0);
        $advtmResponsiveToggleText = ($this->context->phenyxConfig->get('EPHTM_RESP_TOGGLE_TEXT', $this->context->cookie->id_lang) !== false && $this->context->phenyxConfig->get('EPHTM_RESP_TOGGLE_TEXT', $this->context->cookie->id_lang) != '' ? $this->context->phenyxConfig->get('EPHTM_RESP_TOGGLE_TEXT', $this->context->cookie->id_lang) : $this->l('Menu'));
        $advtmResponsiveContainerClasses = $this->context->phenyxConfig->get('EPHTM_RESP_CONT_CLASSES');
        $advtmContainerClasses = $this->context->phenyxConfig->get('EPHTM_CONT_CLASSES');
        $advtmInnerClasses = $this->context->phenyxConfig->get('EPHTM_INNER_CLASSES');
        $advtmIsSticky = ($this->context->phenyxConfig->get('EPHTM_MENU_CONT_POSITION') == 'sticky');
        $advtmOpenMethod = (int) $this->context->phenyxConfig->get('EPHTM_SUBMENU_OPEN_METHOD');

        if ($advtmOpenMethod == 2) {
            $advtmInnerClasses .= ' phtm_open_on_click';
        } else {
            $advtmInnerClasses .= ' phtm_open_on_hover';
        }

        $advtmInnerClasses = trim($advtmInnerClasses);
        $customerGroups = TopMenu::getCustomerGroups();

        $this->menuVars = [
            'advtmIsSticky'                   => $advtmIsSticky,
            'advtmOpenMethod'                 => $advtmOpenMethod,
            'advtmInnerClasses'               => $advtmInnerClasses,
            'advtmContainerClasses'           => $advtmContainerClasses,
            'advtmResponsiveContainerClasses' => $advtmResponsiveContainerClasses,
            'advtmResponsiveToggleText'       => $advtmResponsiveToggleText,
            'advtmResponsiveMode'             => $advtmResponsiveMode,
            'advtmThemeCompatibility'         => $advtmThemeCompatibility,
            'phtm_menus'                      => $menus,
            'customerGroups'                  => $customerGroups,
        ];

    }

    protected function sslRedirection() {

        // If we call a SSL controller without SSL or a non SSL controller with SSL, we redirect with the right protocol

        if ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') && (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'POST') && $this->ssl != Tools::usingSecureMode()) {
            $this->context->cookie->disallowWriting();
            header('HTTP/1.1 301 Moved Permanently');
            header('Cache-Control: no-cache');

            if ($this->ssl) {
                header('Location: ' . Tools::getDomainSsl(true) . $_SERVER['REQUEST_URI']);
            } else {
                header('Location: ' . Tools::getDomain(true) . $_SERVER['REQUEST_URI']);
            }

            exit();
        }

    }

    protected function geolocationManagement($defaultCountry) {

        if (!in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
            /* Check if Maxmind Database exists */

            if (@filemtime(_EPH_GEOIP_DIR_ . _EPH_GEOIP_CITY_FILE_)) {

                if (!isset($this->context->cookie->iso_code_country) || (isset($this->context->cookie->iso_code_country) && !in_array(strtoupper($this->context->cookie->iso_code_country), explode(';', $this->context->phenyxConfig->get('EPH_ALLOWED_COUNTRIES'))))) {
                    $gi = geoip_open(realpath(_EPH_GEOIP_DIR_ . _EPH_GEOIP_CITY_FILE_), GEOIP_STANDARD);
                    $record = geoip_record_by_addr($gi, Tools::getRemoteAddr());

                    if (is_object($record)) {

                        if (!in_array(strtoupper($record->country_code), explode(';', $this->context->phenyxConfig->get('EPH_ALLOWED_COUNTRIES'))) && !FrontController::isInWhitelistForGeolocation()) {

                            if ($this->context->phenyxConfig->get('EPH_GEOLOCATION_BEHAVIOR') == _EPH_GEOLOCATION_NO_CATALOG_) {
                                $this->restrictedCountry = true;
                            } else

                            if ($this->context->phenyxConfig->get('EPH_GEOLOCATION_BEHAVIOR') == _EPH_GEOLOCATION_NO_ORDER_) {
                                $this->context->smarty->assign(
                                    [
                                        'restricted_country_mode' => true,
                                        'geolocation_country'     => $record->country_name,
                                    ]
                                );
                            }

                        } else {
                            $hasBeenSet = !isset($this->context->cookie->iso_code_country);
                            $this->context->cookie->iso_code_country = strtoupper($record->country_code);
                        }

                    }

                }

                if (isset($this->context->cookie->iso_code_country) && $this->context->cookie->iso_code_country && !Validate::isLanguageIsoCode($this->context->cookie->iso_code_country)) {
                    $this->context->cookie->iso_code_country = Country::getIsoById($this->context->phenyxConfig->get('EPH_COUNTRY_DEFAULT'));
                }

                if (isset($this->context->cookie->iso_code_country) && ($idCountry = (int) Country::getByIso(strtoupper($this->context->cookie->iso_code_country)))) {
                    /* Update defaultCountry */

                    if ($defaultCountry->iso_code != $this->context->cookie->iso_code_country) {
                        $defaultCountry = new Country($idCountry);
                    }

                    if (isset($hasBeenSet) && $hasBeenSet) {
                        $this->context->cookie->id_currency = (int) ($defaultCountry->id_currency ? (int) $defaultCountry->id_currency : (int) $this->context->phenyxConfig->get('EPH_CURRENCY_DEFAULT'));
                    }

                    return $defaultCountry;
                } else

                if ($this->context->phenyxConfig->get('EPH_GEOLOCATION_NA_BEHAVIOR') == _EPH_GEOLOCATION_NO_CATALOG_ && !FrontController::isInWhitelistForGeolocation()) {
                    $this->restrictedCountry = true;
                } else

                if ($this->context->phenyxConfig->get('EPH_GEOLOCATION_NA_BEHAVIOR') == _EPH_GEOLOCATION_NO_ORDER_ && !FrontController::isInWhitelistForGeolocation()) {
                    $this->context->smarty->assign(
                        [
                            'restricted_country_mode' => true,
                            'geolocation_country'     => isset($record) && isset($record->country_name) && $record->country_name ? $record->country_name : 'Undefined',
                        ]
                    );
                }

            }

        }

        return false;
    }

    protected static function isInWhitelistForGeolocation() {

        static $allowed = null;

        if ($allowed !== null) {
            return $allowed;
        }

        $allowed = false;
        $userIp = Tools::getRemoteAddr();
        $ips = [];

        // retrocompatibility
        $ipsOld = explode(';', $this->context->phenyxConfig->get('EPH_GEOLOCATION_WHITELIST'));

        if (is_array($ipsOld) && count($ipsOld)) {

            foreach ($ipsOld as $ip) {
                $ips = array_merge($ips, explode("\n", $ip));
            }

        }

        $ips = array_map('trim', $ips);

        if (is_array($ips) && count($ips)) {

            foreach ($ips as $ip) {

                if (!empty($ip) && preg_match('/^' . $ip . '.*/', $userIp)) {
                    $allowed = true;
                }

            }

        }

        return $allowed;
    }

    protected function canonicalRedirection($canonicalUrl = '') {

        if (!$canonicalUrl || !$this->context->phenyxConfig->get('EPH_CANONICAL_REDIRECT') || strtoupper($_SERVER['REQUEST_METHOD']) != 'GET' || Tools::getValue('live_edit')) {
            return;
        }

        $matchUrl = rawurldecode(Tools::getCurrentUrlProtocolPrefix() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

        if (Tools::usingSecureMode()) {
            // Do not redirect to the same page on HTTP

            if (substr_replace($canonicalUrl, 'https', 0, 4) === $matchUrl) {
                return;
            }

        }

        if (!preg_match('/^' . Tools::pRegexp(rawurldecode($canonicalUrl), '/') . '([&?].*)?$/', $matchUrl)) {
            $params = [];
            $urlDetails = parse_url($canonicalUrl);

            if (!empty($urlDetails['query'])) {
                parse_str($urlDetails['query'], $query);

                foreach ($query as $key => $value) {
                    $params[Tools::safeOutput($key)] = Tools::safeOutput($value);
                }

            }

            $excludedKey = ['isolang', 'id_lang', 'controller', 'fc', 'admin', 'id_product', 'id_category', 'id_manufacturer', 'id_supplier', 'id_cms', 'id_pfg'];

            foreach ($_GET as $key => $value) {

                if (!in_array($key, $excludedKey) && Validate::isUrl($key) && Validate::isUrl($value)) {
                    $params[Tools::safeOutput($key)] = Tools::safeOutput($value);
                }

            }

            $strParams = http_build_query($params, '', '&');

            if (!empty($strParams)) {
                $finalUrl = preg_replace('/^([^?]*)?.*$/', '$1', $canonicalUrl) . '?' . $strParams;
            } else {
                $finalUrl = preg_replace('/^([^?]*)?.*$/', '$1', $canonicalUrl);
            }

            // Don't send any cookie
            $this->context->cookie->disallowWriting();

            if (defined('_EPH_MODE_DEV_') && _EPH_MODE_DEV_ && $_SERVER['REQUEST_URI'] != __EPH_BASE_URI__) {
                die('[Debug] This page has moved<br />Please use the following URL instead: <a href="' . $finalUrl . '">' . $finalUrl . '</a>');
            }

            $redirectType = $this->context->phenyxConfig->get('EPH_CANONICAL_REDIRECT') == 2 ? '301' : '302';
            header('HTTP/1.0 ' . $redirectType . ' Moved');
            header('Cache-Control: no-cache');
            Tools::redirectLink($finalUrl);
        }

    }

    protected function displayRestrictedCountryPage() {

        header('HTTP/1.1 503 temporarily overloaded');
        $this->context->smarty->assign(
            [
                'shop_name'   => $this->context->company->name,
                'favicon_url' => _EPH_IMG_ . $this->context->phenyxConfig->get('EPH_FAVICON'),
                'logo_url'    => $this->context->_link->getMediaLink(_EPH_IMG_ . $this->context->phenyxConfig->get('EPH_LOGO')),
            ]
        );
        $this->smartyOutputContent($this->getTemplatePath($this->getThemeDir() . 'restricted-country.tpl'));
        exit;
    }

    public function isTokenValid() {

        if (!$this->context->phenyxConfig->get('EPH_TOKEN_ENABLE')) {
            return true;
        }

        return strcasecmp(Tools::getToken(false), Tools::getValue('token')) == 0;
    }

    public function setTemplate($defaultTemplate) {

        $template = $this->getOverrideTemplate();

        if ($template) {
            parent::setTemplate($template);

        } else {
            parent::setTemplate($defaultTemplate);
        }

    }

    public function setMobileTemplate($template) {

        // Needed for site map

        $this->context->smarty->assign(
            [
                'EPH_SHOP_NAME' => $this->context->phenyxConfig->get('EPH_SHOP_NAME'),
            ]
        );

        $template = $this->getTemplatePath($template);

        $assign = [];
        $assign['tpl_file'] = basename($template, '.tpl');

        if (isset($this->php_self)) {
            $assign['controller_name'] = $this->php_self;
        }

        $this->context->smarty->assign($assign);
        $this->template = $template;
    }

    public function getOverrideTemplate() {

        return $this->context->_hook->exec('DisplayOverrideTemplate', ['controller' => $this]);
    }

    protected function getLogo() {

        $logo = '';
        $context = Context::getContext();
        $idCompany = (int) $context->company->id;

        if ($this->context->phenyxConfig->get('EPH_LOGO_INVOICE', null, null, $idCompany) != false && file_exists(_EPH_IMG_DIR_ . $this->context->phenyxConfig->get('EPH_LOGO_INVOICE', null, null, $idCompany))) {
            $logo = _EPH_IMG_DIR_ . $this->context->phenyxConfig->get('EPH_LOGO_INVOICE', null, null, $idCompany);
        } else

        if ($this->context->phenyxConfig->get('EPH_LOGO', null, null, $idCompany) != false && file_exists(_EPH_IMG_DIR_ . $this->context->phenyxConfig->get('EPH_LOGO', null, null, $idCompany))) {
            $logo = _EPH_IMG_DIR_ . $this->context->phenyxConfig->get('EPH_LOGO', null, null, $idCompany);
        }

        return $logo;
    }

    public function regenerateHeader() {

        $this->context->smarty->assign('page_name', $this->page_name);
        $this->context->smarty->assign('path', $this->ajax_path);

        return $this->context->smarty->fetch(_EPH_THEME_DIR_ . 'upper-columns-container.tpl');
    }

    protected function redirect() {

        Tools::redirectLink($this->redirect_after);
    }

    public function ajaxProcessGetFrontLinkController() {

        $controller = Tools::getValue('souceController');
        $link = $this->context->_link->getPageLink($controller, true);
        $return = [
            'link' => $link,
        ];

        die(Tools::jsonEncode($return));

    }

    public function ajaxProcessDeleteObject() {

        $idObject = Tools::getValue('idObject');
        $this->className = Tools::getValue('targetClass');

        $this->object = new $this->className($idObject);

        $this->object->delete();

        $result = [
            'success' => true,
            'message' => 'La suppression s‘est déroulée avec succès.',
        ];

        die(Tools::jsonEncode($result));
    }

}
