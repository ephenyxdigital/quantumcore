<?php

/**
 * Class ParamContextMenu
 *
 * @since 2.1.0.0
 */
class ParamContextMenu {

	public $contextMenuClass;

	public $contextMenuController;

	public $items = [];

	public function __construct($contextMenuClass, $contextMenuController) {

		$this->contextMenuClass = $contextMenuClass;
		$this->contextMenuController = $contextMenuController;

	}

	public function buildContextMenu() {

		$items = '';

		foreach ($this->items as $key => $values) {
			$content = '';

			if (is_array($values)) {
				$items .= '"' . $key . '":{' . PHP_EOL;

				foreach ($values as $k => $value) {

					if ($k == 'items') {
						$content .= '"items":{' . PHP_EOL;

						foreach ($value as $k2 => $item) {
							$content .= $k2 . ':{' . PHP_EOL;

							foreach ($item as $k3 => $val) {
								$content .= $k3 . ':' . $val . ',' . PHP_EOL;
							}

							$content .= '},' . PHP_EOL;
						}

						$content .= '},' . PHP_EOL;
					} else {
						$content .= $k . ':' . $value . ',' . PHP_EOL;
					}

				}

				$items .= $content . '},';
			} else {
				$items .= '"' . $key . '":' . PHP_EOL;
				$content .= $values;
				$items .= $content . ',';
			}

		}

		$builder = [
			'selector'  => '\'.pq-body-outer .pq-grid-row\'',
			'animation' => [
				'duration' => 250,
				'show'     => '\'fadeIn\'',
				'hide'     => '\'fadeOut\'',
			],
			'build'     => 'function($triggerElement, e){

                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . 'grid' . $this->contextMenuClass . '.getRowData( {rowIndx: rowIndex} );
                selected = selgrid' . $this->contextMenuClass . '.getSelection().length;
                var dataLenght = grid' . $this->contextMenuClass . '.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {' . PHP_EOL . $items . PHP_EOL . '}' . PHP_EOL . '}' . PHP_EOL . '}',
		];

		return $builder;
	}


}
