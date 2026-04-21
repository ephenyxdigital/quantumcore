<?php

class ComposerTemplatesEditor  extends PhenyxObjectModel {

    protected static $instance;
	protected $option_name = 'wpb_js_templates';
    
    
	protected $default_templates = [];
	protected $_action_index = [];
    
    public $image_path;
    public $content;
    public $name;
    
    public static $definition = [
		'table'     => 'composer_template',
		'primary'   => 'id_composer_template',
		'multilang' => true,
		'fields'    => [
			'image_path'   => ['type' => self::TYPE_STRING],
			'content'      => ['type' => self::TYPE_STRING],
			/* Lang fields */
			'name'         => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
		],
	];
    
    public function __construct($id = null, $idLang = null) {

		parent::__construct($id, $idLang);
        $this->loadDefaultTemplates();

	}
    
    public static function buildObject($id, $id_lang = null, $className = null) {
        
        $objectData = parent::buildObject($id, $id_lang, $className);
        $objectData['default_templates'] = [];
        $templates = new PhenyxCollection('ComposerTemplatesEditor', Context::getContext()->language->id);
        foreach($templates as $template) {
            $objectData['default_templates'][] = $template;
        }
       
        return PhenyxTool::getInstance()->jsonDecode(PhenyxTool::getInstance()->jsonEncode($objectData));
    }    
    
    public static function getInstance($id = null, $idLang = null) {

        if (!ComposerTemplatesEditor::$instance) {
            ComposerTemplatesEditor::$instance = new ComposerTemplatesEditor($id, $idLang);
        }

        return ComposerTemplatesEditor::$instance;
    }
    
    public function loadDefaultTemplates() {

		if (!is_array($this->default_templates)) {

            $templates = new PhenyxCollection('ComposerTemplatesEditor', $this->context->language->id);
            foreach($templates as $template) {
                $this->default_templates[] = $template;
            }
        }

		return $this->default_templates;
	}


	/**
	 * Add ajax hooks.
	 */
	public function init() {

		$this->_action_index = [
			'wpb_save_template'            => [&$this, 'save'],
			'vc_backend_template'          => [&$this, 'load'],
			'wpb_load_template_shortcodes' => [&$this, 'loadTemplateShortcodes'],
			'vc_backend_default_template'  => [&$this, 'getBackendDefaultTemplate'],
			'wpb_delete_template'          => [&$this, 'delete'],
		];
		$action = PhenyxTool::getInstance()->getValue('action');
        
        $this->loadDefaultTemplates();

		Composer::$sds_action_hooks['vc_frontend_template'] = [&$this, 'renderFrontendTemplate'];
		Composer::$sds_action_hooks['vc_frontend_default_template'] = [&$this, 'renderFrontendDefaultTemplate'];

		if (isset($this->_action_index[$action])) {

			call_user_func($this->_action_index[$action]);
		}

	}

	function renderFrontendTemplate() {

		$this->template_id = vc_post_param('template_id');

		if (empty($this->template_id)) {
			die();
		}

		$option_name = 'wpb_js_templates';

		$saved_templates = PhenyxTool::getInstance()->jsonDecode($this->context->phenyxConfig->get($option_name), true);

		ephenyx_frontend_editor()->setTemplateContent($saved_templates[$this->template_id]['template']);
        $data = $this->context->smarty->createTemplate(_EPH_COMPOSER_DIR_ .  'editors/frontend_template.tpl');
        $data->assign(
				[
					'editor' => ephenyx_frontend_editor(),
				]
			);
        return $data->fetch();
		
		die();
	}

	public function load() {

		
		$template_id = ephenyx_manager()->vc_post_param('template_id');

		if (!isset($template_id) || $template_id == "") {
			echo 'Error: TPL-02';
			die();
		}

		$saved_templates = PhenyxTool::getInstance()->jsonDecode($this->context->phenyxConfig->get($this->option_name), true);

		$content = trim(urldecode($saved_templates[$template_id]['template']));
		echo $content;

		die();
	}

	public function loadInline() {

		echo $this->renderMenu();
		die();
	}

	public function loadTemplateShortcodes() {

		$editor = ephenyx_manager();
		$template_id = ephenyx_manager()->vc_post_param('template_id');

		if (!isset($template_id) || $template_id == "") {
			echo 'Error: TPL-02';
			die();
		}

		$saved_templates = PhenyxTool::getInstance()->jsonDecode($this->context->phenyxConfig->get($this->option_name), true);

		$content = trim(urldecode($saved_templates[$template_id]['template']));
		
		$pattern = $editor->get_shortcode_regex();
		$content = preg_replace_callback("/{$pattern}/s", 'vc_convert_shortcode', $content);
		echo $content;
		die();
	}

	
	public function addDefaultTemplates($data) {

		if (is_array($data) && !empty($data) && isset($data['name'], $data['content'])) {
			$this->default_templates[] = $data;

			return $this->default_templates;
		}

		return false;
	}

	
	

	
	public function getDefaultTemplate($template_index) {

		$this->loadDefaultTemplates();

		if (!isset($template_index) || $template_index == "" || !is_numeric($template_index) || !is_array($this->default_templates) || !isset($this->default_templates[$template_index])) {
			return false;
		}

		return $this->default_templates[$template_index];
	}

	public function getBackendDefaultTemplate($return = false) {

        $template_index = PhenyxTool::getInstance()->getValue('template_name');
       
        $template = new ComposerTemplatesEditor($template_index);
        
		if (!$template->content) {
			echo 'Error: TPL-02';
			die();
		}

		if ($return) {
			return trim($template->content);
		} else {
			echo trim($template->content);
			die();
		}

	}

	public function delete() {

		$template_id = PhenyxTool::getInstance()->getValue('template_id');

		if (!isset($template_id) || $template_id == "") {
			echo 'Error: TPL-03';
			die();
		}

		$saved_templates = PhenyxTool::getInstance()->jsonDecode($this->context->phenyxConfig->get($this->option_name), true);
        
		unset($saved_templates[$template_id]);

		if (count($saved_templates) > 0) {
			$this->context->phenyxConfig->updateValue($this->option_name, PhenyxTool::getInstance()->jsonEncode($saved_templates));
		} else {

			$this->context->phenyxConfig->deleteByName($this->option_name);
		}

		return $this->renderMenu(true);
		die();
	}

	public function render($editor) {
        
        $data = $this->context->smarty->createTemplate(_EPH_COMPOSER_DIR_  .  'editors/popups/panel_templates_editor.tpl');
        $data->assign(
				[
					'box'    => $this,
			'editor' => $editor,
				]
			);
        $data->fetch();

		
	}

	public function outputMenuButton($id, $params) {

		if (empty($params)) {
			return '';
		}

		
		$output = '<li class="wpb_template_li"><a data-template_id="' . $id . '" href="#">' . htmlspecialchars($params['name']) . '</a> <span class="wpb_remove_template" title="' . ephenyx_manager()->l("Delete template") . '" rel="' . $id . '"><i class="icon wpb_template_delete_icon"> </i></span></li>';
		return $output;
	}

	public function renderMenu($only_list = false) {

		$templates = PhenyxTool::getInstance()->jsonDecode($this->context->phenyxConfig->get($this->option_name), true);

		$output = '';
		$editor = ephenyx_manager();


		if ($only_list === false) {
			$output .= '<li><ul>
                        <li id="wpb_save_template"><a href="#" id="wpb_save_template_button" class="button">' .$editor->l('Save current page as a Template') . '</a></li>
                        <li class="divider"></li>
                        <li class="nav-header">' . $editor->l('Load Template') . '</li>
                        </ul></li>
                        <li>
                        <ul class="wpb_templates_list">';
		}

		if (empty($templates)) {
			$output .= '<li class="wpb_no_templates"><span>' . $editor->l('No custom templates yet.') . '</span></li></ul>';
			echo $output;
			return '';
		}

		$templates_arr = $templates;

		foreach ($templates as $id => $template) {

			if (is_array($template) && isset($template['name'], $template['template']) && strlen(trim($template['name'])) > 0 && strlen(trim($template['template'])) > 0) {
				$output .= $this->outputMenuButton($id, $template);
			} else {
				/* This will delete exists "Wrong" templates */
				unset($templates_arr[$id]);

				if (count($templates_arr) > 0) {
					$this->context->phenyxConfig->updateValue($this->option_name, PhenyxTool::getInstance()->jsonEncode($templates_arr));
				} else {
					$this->context->phenyxConfig->updateValue($this->option_name, '');
				}

			}

		}

		echo $output;
	}

	
	public function renderFrontendDefaultTemplate() {

		$template_index = vc_post_param('template_name');
		$data = $this->getDefaultTemplate($template_index);
		!$data && die();
		ephenyx_frontend_editor()->setTemplateContent(trim($data['content']));
		vc_include_template('editors/frontend_template.tpl.php', [
			'editor' => ephenyx_frontend_editor(),
		]);
		die();
	}

}
