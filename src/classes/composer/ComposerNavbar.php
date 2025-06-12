<?php

/**
 * Renders navigation bar for Editors.
 */
class ComposerNavbar {

	protected $controls = [
		'add_element',
		'templates',
		'custom_css',
	];
	protected $brand_url = 'http://ephenyx.com';
	protected $css_class = 'vc_navbar';
	protected $controls_filter_name = 'vc_nav_controls';
	public $post = false;
	public $idLang = null;
	public $context;
	public $composer;

	public function __construct($post = '', $idLang = null) {

		$this->post = $post;
		$this->idLang = $idLang;
		global $smarty;
		$this->context = Context::getContext();
		$this->composer = Composer::getInstance();
	}

	/**
	 * Generate array of controls by iterating property $controls list.
	 *
	 * @return array - list of arrays witch contains key name and html output for button.
	 */
	public function getControls() {

		$list = [];

		foreach ($this->controls as $control) {
			$method = $this->composer->vc_camel_case('get_control_' . $control);

			if (method_exists($this, $method)) {
				$list[] = [$control, $this->$method() . "\n"];
			}

		}

		return $list;
	}

	/**
	 * Get current post.
	 * @return null|WP_Post
	 */
	public function post() {

		$id = Tools::getValue('id_cms');

		if ($this->post) {
			return $this->post;
		}

		return new CMS($id);
	}

	/**
	 * Render template.
	 */
	public function render() {

		$data = $this->context->smarty->createTemplate(_EPH_COMPOSER_DIR_ . 'editors/navbar/navbar.tpl');
		$data->assign(
			[
				'languages' => Language::getLanguages(false),
				'css_class' => $this->css_class,
				'controls'  => $this->getControls(),
				'nav_bar'   => $this,
				'post'      => $this->post,
				'idLang'    => $this->idLang,
			]
		);

		return $data->fetch();

	}

	public function getLogo() {
		$output = '<a id="vc_logo" class="vc_navbar-brand" title="' . $this->l('Visual Composer')
		. '" href="javascript:void(0)">'
		. $this->l('Visual Composer') . '</a>';
		return $output;
	}

	public function getControlCustomCss() {

		return '<li class="vc_pull-right"><a id="vc_post-settings-button" class="vc_icon-btn vc_post-settings" title="'
		. $this->l('Page settings') . '">'
		. '<span id="vc_post-css-badge" class="vc_badge vc_badge-custom-css" style="display: none;">' . $this->l('CSS') . '</span></a>'
			. '</li>';
	}

	public function getControlAddElement() {
		return '<li class="vc_show-mobile">'
		. '	<a href="javascript:;" class="vc_icon-btn vc_element-button" data-model-id="vc_element" id="vc_add-new-element" title="'
		. '' . $this->l('Add new element') . '">'
			. '	</a>'
			. '</li>';
	}

	public function getControlTemplates() {
		return '<li><a href="javascript:;" class="vc_icon-btn vc_templates-button vc_navbar-border-right"  id="vc_templates-editor-button" title="'
		. $this->l('Templates') . '"></a></li>';
	}

	public function l($string, $idLang = null, $context = null) {

		$class = 'ComposerNavbar';

		if (isset($this->context->translations)) {
			return $this->context->translations->getClassTranslation($string, $class);
		}

		return $string;

	}

}
