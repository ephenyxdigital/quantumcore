<?php

use Mpdf\Mpdf;

class PdfPrinter extends Mpdf {

    protected $context;

    public $tpl_folder;

    public $_smarty;

    public function __construct(array $config = [], $container = null) {

        parent::__construct($config, $container);
        $this->context = Context::getContext();

        $this->_smarty = $this->context->smarty;

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();

        }

        if (!isset($this->context->company)) {
            $this->context->company = Company::initialize();

        }

        if (!isset($this->context->language)) {
            $this->context->language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
        }

        if (!isset($this->context->translations)) {

            $this->context->translations = new Translate($this->context->language->iso_code, $this->context->company);
        }

    }

    public function createTemplate($tplName) {

        $extraTplPaths = $this->context->_hook->exec('actionCreatePdfTemplate', ['tplName' => $tplName], null, true);

        if (is_array($extraTplPaths)) {

            foreach ($extraTplPaths as $plugin => $template) {

                if (!is_null($template) && file_exists($template)) {
                    $tplName = $template;
                }

            }

        }

        $path_parts = pathinfo($tplName);
        $tpl = '';

        if (!is_null($this->tpl_folder) && file_exists($this->context->theme->path . $this->tpl_folder . '/pdf/' . $path_parts['filename'] . '.tpl')) {
            $tpl = $this->context->theme->path . $this->tpl_folder . '/pdf/' . $path_parts['filename'] . '.tpl';

        } else

        if (file_exists($this->context->theme->path . 'pdf/' . $path_parts['filename'] . '.tpl')) {

            $tpl = $this->context->theme->path . 'pdf/' . $path_parts['filename'] . '.tpl';

        } else {

            $tpl = $tplName;

        }

        if (file_exists($tpl)) {
            return $this->_smarty->createTemplate($tpl, $this->_smarty);
        }

    }

}
