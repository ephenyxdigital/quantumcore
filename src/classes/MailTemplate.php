<?php

/**
 * @since 1.9.1.0
 */
class MailTemplate extends PhenyxObjectModel {

    public $require_context = false;
    
    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'mail_template',
        'primary' => 'id_mail_template',
        'multilang' => true,
        'fields'  => [
            'template' => ['type' => self::TYPE_STRING, 'required' => true],                   
            'plugin'         => ['type' => self::TYPE_STRING, 'validate' => 'isTabName', 'size' => 64],
            
            'generated' => ['type' => self::TYPE_BOOL, 'lang' => true],
            'target'   => ['type' => self::TYPE_STRING, 'lang' => true, 'required' => true],     
            'name'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
        ],
    ];
    public $template;
    public $target;
    public $plugin;
    
    public $generated;
    public $name;
    // @codingStandardsIgnoreEnd
    public $content;
    
    public $template_path;

    /**
     * GenderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        if ($this->id) {
            $this->content = $this->getTemplateContent();
            $this->getTemplatePath();
        }

    }
    
    public static function buildObject($id, $id_lang = null, $className = null) {

        $objectData = parent::buildObject($id, $id_lang, $className);
        $objectData['content'] = self::getStaticTemplateContent($objectData);
        $objectData['template_path'] = self::getStaticTemplatePath($objectData);

        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }
    
    public function getTemplatePath() {
        
        
        if (is_null($this->plugin) && file_exists(_EPH_MAIL_DIR_ . $this->template)) {
            
            $this->template_path = _EPH_MAIL_DIR_ . $this->template;
        }  else if (file_exists(_EPH_PLUGIN_DIR_ .$this->plugin.'/views/mails/'. $this->template)) {
            $this->template_path = _EPH_PLUGIN_DIR_ .$this->plugin.'/views/mails/'. $this->template;
        }
        
    }
    
    public static function getStaticTemplatePath($mailtemplate) {
        
        
        if (is_null($mailtemplate['plugin']) && file_exists(_EPH_MAIL_DIR_ . $mailtemplate['template'])) {
            
            $template_path = _EPH_MAIL_DIR_ . $mailtemplate['template'];
        }  else if (file_exists(_EPH_PLUGIN_DIR_ .$mailtemplate['plugin'].'/views/mails/'. $mailtemplate['template'])) {
            $template_path = _EPH_PLUGIN_DIR_ .$mailtemplate['plugin'].'/views/mails/'. $mailtemplate['template'];
        }
        
        return $template_path;
        
    }

    public function getTemplateContent() {

        $content = '';

        
        if (is_null($this->plugin) && file_exists(_EPH_MAIL_DIR_ . $this->template)) {
            $tpl = str_replace('.tpl', '', $this->template);

            $content = file_get_contents(_EPH_MAIL_DIR_ . $this->template);
            $content = Tool::parseEmailContent($content, $tpl);
        } else if (file_exists(_EPH_PLUGIN_DIR_ .$this->plugin.'/views/mails/'. $this->template)) {
            $tpl = str_replace('.tpl', '', $this->template);
            $content = file_get_contents(_EPH_PLUGIN_DIR_ .$this->plugin.'/views/mails/'. $this->template);
            $content = Tool::parseEmailContent($content, $tpl);
        }

        return $content;

    }
    
    public static function getStaticTemplateContent($mailtemplate) {

        $_tools = PhenyxTool::getInstance();
        $content = '';
        
        if (empty($mailtemplate['plugin']) && file_exists(_EPH_MAIL_DIR_ . $mailtemplate['template'])) {
            $tpl = str_replace('.tpl', '', $mailtemplate['template']);

            $content = file_get_contents(_EPH_MAIL_DIR_ . $mailtemplate['template']);
            $content = $_tools->parseEmailContent($content, $tpl);
        } else if (file_exists(_EPH_PLUGIN_DIR_ .$mailtemplate['plugin'].'/views/mails/'. $mailtemplate['template'])) {
            $tpl = str_replace('.tpl', '', $mailtemplate['template']);
            $content = file_get_contents(_EPH_PLUGIN_DIR_ .$mailtemplate['plugin'].'/views/mails/'. $mailtemplate['template']);
            $content = $_tools->parseEmailContent($content, $tpl);
        }

        return $content;

    }

    public static function getObjectByTemplateName($template) {

        return Db::getInstance()->getValue(
            (new DbQuery())
                ->select('`id_mail_template`')
                ->from('mail_template')
                ->where('`template` LIKE  \'' . $template . '\'')
        );
    }

    
    public static function getMalPlugins() {
        
        $plugins = [];
        $request = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('DISTINCT(`plugin`)')
                ->from('mail_template')
        );
        foreach($request as $plugin) {
            if(!empty($plugin['plugin'])) {
                $plugins[$plugin['plugin']] = $plugin['plugin'];
            }
            
        }
        
        return $plugins;
    }
}
