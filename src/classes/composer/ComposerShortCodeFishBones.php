<?php

class ComposerShortCodeFishBones extends ComposerShortCode {

	protected $shortcode_class = false;

	public function __construct($settings) {

		$this->settings = $settings;
		$this->shortcode = $this->settings['base'];

		if (!shortcodeExists($this->shortcode)) {
			$this->addShortCode($this->shortcode, [ & $this, 'render']);
		}

	}

	public function shortcodeClass() {

		$class_name = $this->settings('php_class_name') ? $this->settings('php_class_name') : 'ComposerShortCode_' . $this->settings('base');
		$class_name = str_replace('-', '_', $class_name);

		if (class_exists($class_name) && is_subclass_of($class_name, 'ComposerShortCode')) {
			$this->shortcode_class = new $class_name($this->settings);
			return $this->shortcode_class;
		} else {
			try {

				$this->shortcode_class = new ComposerShortCode_abstract($this->settings);
				return $this->shortcode_class;
			} catch (Exception $e) {
				$file = fopen("testNewFishBoneShortCodeClass.txt", "a");
				fwrite($file, $e->getMessage() . PHP_EOL);

			}

		}

	}

	public function render($atts, $content = null) {

		return $this->shortcodeClass()->output($atts, $content);
	}

	protected function content($atts, $content = null) {

		return ''; // this method is not used
	}

	public function template($content = '') {

		return $this->shortcodeClass()->contentAdmin($this->atts, $content);
	}

}
