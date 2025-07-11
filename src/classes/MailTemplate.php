<?php

/**
 * @since 1.9.1.0
 */
class MailTemplate extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'mail_template',
        'primary' => 'id_mail_template',
        'fields'  => [
            'template' => ['type' => self::TYPE_STRING, 'required' => true],
            'target'   => ['type' => self::TYPE_STRING, 'required' => true],
            'name'     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 128],
        ],
    ];
    public $template;
    public $target;
    public $name;
    // @codingStandardsIgnoreEnd
    public $content;

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
    public function __construct($id = null, $idLang = null, $idShop = null) {

        parent::__construct($id, $idLang, $idShop);

        if ($this->id) {
            $this->content = $this->getTemplateContent();
        }

    }

    public function getTemplateContent() {

        $content = '';

        if (file_exists(_EPH_MAIL_DIR_ . $this->template)) {
            $tpl = str_replace('.tpl', '', $this->template);

            $content = file_get_contents(_EPH_MAIL_DIR_ . $this->template);
            $content = Tool::parseEmailContent($content, $tpl);
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

    public static function getObjectNameByTemplateName($template) {

        return Db::getInstance()->getValue(
            (new DbQuery())
                ->select('`name`')
                ->from('mail_template')
                ->where('`template` LIKE  \'' . $template . '\'')
        );
    }

}
