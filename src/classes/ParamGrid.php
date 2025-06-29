<?php

/**
 * Class ParamGrid
 *
 * @since 2.1.0.0
 */
class ParamGrid {

	public $paramClass;

	public $paramTable;

	public $paramController;

	public $paramIdentifier;

	public $paramGridObj = [];

	public $paramGridId;

	public $paramGridVar;

	public $height;

	public $width = '100%';

	public $recIndx;

	public $data;

	public $dataModel = [];

	public $autoFit = true;

	public $resizable = true;

	public $scrollModel = [];

	public $animOn = true;

	public $animDuration = 400;

	public $animModel = [];

	public $complete;

	public $wrap = true;

	public $autofill = true;

	public $colModel;

	public $showNumberCell = 0;

	public $numberCell = [];

	public $pageModel = [
		'type'       => '\'local\'',
		'rPP'        => 100,
		'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
	];

	public $create;

	public $rowInit;

	public $change;

	public $cellSave;

	public $cellClick;

	public $cellDblClick;

	public $cellKeyDown;

	public $showTitle = 1;

	public $showTop = 1;

	public $showHeader = 1;

	public $showToolbar = 1;

	public $title = '\'\'';

	public $collapsible = 0;
	public $freezeCols = 0;
	public $rowBorders = 1;
	public $columnBorders = 0;
	public $stripeRows = 1;
	public $selectionModelType = 'row';
	public $selectionModel = [];

	public $paragrid_option = [];

	public $editModel;

	public $controllerName;

	public $paragridScript;

	public $contextMenuoption;

	public $functionContextMenu = false;

	public $dragOn = 0;

	public $dragdiHelper;

	public $dragclsHandle;

	public $dragModel = [];

	public $dropOn = 0;

	public $dropModel = [];

	public $moveNode;

	public $toolbar;

	public $groupModel;

	public $filterModel = [
		'on'          => true,
		'mode'        => '\'AND\'',
		'header'      => true,
		'type'        => '\'local\'',
		'menuIcon'    => 0,
		'gridOptions' => [
			'numberCell' => [
				'show' => 0,
			],
			'width'      => '\'flex\'',
			'flex'       => [
				'one' => true,
			],
		],
	];

	public $fillHandle = '\'all\'';

	public $beforeRowExpand;

	public $contextMenu;

	public $gridFunction = [];

	public $gridExtraFunction;

	public $gridAfterLoadFunction;

	public $load;

	public $summaryData;

	public $detailModel;

	public $subDetailModel;

	public $detailContextMenu;

	public $treeModel;

	public $treeExpand;

	public $otherFunction;

	public $requestModel;

	public $requestCustomModel = null;

	public $requestField = null;

	public $requestComplementaryModel;

	public $heightModel;

	public $getHeightModel;

	public $ajaxUrl;

	public $rowSelect;

	public $selectEnd;

	public $rowClick;

	public $rowDblClick;

	public $summaryTitle;

	public $autoRowHead = true;

	public $autoRow = true;

	public $refresh;

	public $editor;

	public $history;

	public $editorBlur;

	public $sortModel;

	public $beforeSort;

	public $beforeFilter;

	public $beforeTableView;

	public $uppervar;

	public $editorBegin;

	public $editorEnd;

	public $editorFocus;

	public $postRenderInterval;

	public $needRequestModel = true;

	public $needColModel = true;

	public $check;

	public $onlyObject = false;

	public $editable = 1;

	public $formulas = false;

	public $is_subModel = false;

	public $maxHeight;

	public $editorKeyUp;

	public $minWidth;

	public $autoAddRow = 0;

	public $autoAddCol = 0;

	public $columnTemplate;

	public $beforeCellClick;

	public $tabModel;

	public $sort;

	public $showBottom = true;
    
    public $docReady = 1;

	public function __construct($class = null, $controller = null, $table = null, $identifier = null) {

		$this->paramClass = $class;
		$this->paramController = $controller;
		$this->paramTable = $table;
		$this->paramIdentifier = $identifier;

	}

	public function generateParaGridOption() {

		$this->paramGridObj = (!empty($this->paramGridObj)) ? $this->paramGridObj : 'obj' . $this->paramClass;
		$this->paramGridVar = (!empty($this->paramGridVar)) ? $this->paramGridVar : 'grid' . $this->paramClass;
		$this->paramGridId = (!empty($this->paramGridId)) ? $this->paramGridId : 'grid_' . $this->paramController;

		if (!empty($this->recIndx)) {
			$this->dataModel['recIndx'] = '\'' . $this->recIndx . '\'';
		}

		if ($this->needRequestModel) {
			$this->requestModel = (!empty($this->requestModel)) ? $this->requestModel : '{
            	location: "remote",
				dataType: "json",
            	method: "GET",
				recIndx: "' . $this->paramIdentifier . '",
				url: AjaxLink' . $this->paramController . ',
				postData: function () {
                	return {
                    	action: "get' . $this->paramClass . 'Request",
                    	ajax: 1
					};
            	},
            	getData: function (dataJSON) {
					return { data: dataJSON };
            	}
        	}';

		}

		$this->dataModel = (is_array($this->dataModel) && count($this->dataModel)) ? $this->dataModel : $this->paramController . 'Model';

		$this->heightModel = (!empty($this->heightModel)) ? $this->heightModel : '';

		if ($this->needColModel) {
			$this->colModel = (!empty($this->colModel)) ? $this->colModel : 'get' . $this->paramClass . 'Fields()';
		}

		$this->scrollModel = [
			'autoFit' => $this->autoFit,
		];

		$this->numberCell = [
			'show' => $this->showNumberCell,
		];

		$this->selectionModel = [
			'type' => '\'' . $this->selectionModelType . '\'',
		];

		$this->paragrid_option['paragrids'][] = (!empty($this->paragrid_option)) ? $this->paragrid_option : [
			'paramGridVar'              => $this->paramGridVar,
			'paramGridId'               => $this->paramGridId,
			'paramGridObj'              => $this->paramGridObj,
			'requestModel'              => $this->requestModel,
			'requestComplementaryModel' => $this->requestComplementaryModel,

			'builder'                   => [
				'height'         => (!empty($this->heightModel)) ? $this->heightModel : '\'flex\'',
				'width'          => '\'' . $this->width . '\'',
				'scrollModel'    => $this->scrollModel,
				'animModel'      => $this->animModel,
				'wrap'           => $this->wrap,
				'autofill'       => $this->autofill,
				'numberCell'     => $this->numberCell,
				'showHeader'     => $this->showHeader,
				'showToolbar'    => $this->showToolbar,
				'showTop'        => $this->showTop,
				'showTop'        => $this->showTop,
				'showBottom'     => $this->showBottom,
				'resizable'      => $this->resizable,
				'columnBorders'  => $this->columnBorders,
				'collapsible'    => $this->collapsible,
				'freezeCols'     => $this->freezeCols,
				'autoAddRow'     => $this->autoAddRow,
				'autoAddCol'     => $this->autoAddCol,
				'rowBorders'     => $this->rowBorders,
				'stripeRows'     => $this->stripeRows,
				'selectionModel' => $this->selectionModel,
				'editable'       => $this->editable,
			],
			'gridAfterLoadFunction'     => $this->gridAfterLoadFunction,

		];

		foreach ($this->paragrid_option['paragrids'] as &$values) {

			if ($this->needColModel) {
				$values['builder']['colModel'] = $this->colModel;
			}

			if (!empty($this->dataModel)) {
				$values['builder']['dataModel'] = $this->dataModel;
			}

			if (!empty($this->editor)) {
				$values['builder']['editor'] = $this->editor;
			}

			if (!empty($this->sort)) {
				$values['builder']['sort'] = $this->sort;
			}

			if (!empty($this->maxHeight)) {
				$values['builder']['maxHeight'] = $this->maxHeight;
			}

			if (!empty($this->minWidth)) {
				$values['builder']['minWidth'] = $this->minWidth;
			}

			if (!empty($this->title) && $this->showTitle) {
				$values['builder']['title'] = $this->title;
			}

			$values['builder']['showTitle'] = $this->showTitle;

			if (!empty($this->pageModel)) {
				$values['builder']['pageModel'] = $this->pageModel;
			}

			if (!empty($this->fillHandle)) {
				$values['builder']['fillHandle'] = $this->fillHandle;
			}

			if (!empty($this->rowSelect)) {
				$values['builder']['rowSelect'] = $this->rowSelect;
			}

			if (!empty($this->selectEnd)) {
				$values['builder']['selectEnd'] = $this->selectEnd;
			}

			if (!empty($this->rowClick)) {
				$values['builder']['rowClick'] = $this->rowClick;
			}

			if (!empty($this->rowDblClick)) {
				$values['builder']['rowDblClick'] = $this->rowDblClick;
			}

			if (!empty($this->filterModel)) {
				$values['builder']['filterModel'] = $this->filterModel;

			}

			if (!empty($this->sortModel)) {
				$values['builder']['sortModel'] = $this->sortModel;

			}

			if (!empty($this->beforeSort)) {
				$values['builder']['beforeSort'] = $this->beforeSort;

			}

			if (!empty($this->beforeFilter)) {
				$values['builder']['beforeFilter'] = $this->beforeFilter;

			}

			if (!empty($this->beforeCellClick)) {
				$values['builder']['beforeCellClick'] = $this->beforeCellClick;

			}

			if (!empty($this->editorBegin)) {
				$values['builder']['editorBegin'] = $this->editorBegin;

			}

			if (!empty($this->editorBlur)) {
				$values['builder']['editorBlur'] = $this->editorBlur;

			}

			if (!empty($this->editorEnd)) {
				$values['builder']['editorEnd'] = $this->editorEnd;

			}

			if (!empty($this->editorFocus)) {
				$values['builder']['editorFocus'] = $this->editorFocus;

			}

			if (!empty($this->beforeTableView)) {
				$values['builder']['beforeTableView'] = $this->beforeTableView;

			}

			if (!empty($this->autoRowHead)) {
				$values['builder']['autoRowHead'] = $this->autoRowHead;
			}

			if (!empty($this->history)) {
				$values['builder']['history'] = $this->history;
			}

			if (!empty($this->autoRow)) {
				$values['builder']['autoRow'] = $this->autoRow;
			}

			if (!empty($this->groupModel)) {
				$values['builder']['groupModel'] = $this->groupModel;
			}

			if (!empty($this->toolbar)) {
				$values['builder']['toolbar'] = $this->toolbar;
			}

			if (!empty($this->complete)) {
				$values['builder']['complete'] = $this->complete;
			}

			if (!empty($this->rowInit)) {
				$values['builder']['rowInit'] = $this->rowInit;
			}

			if (!empty($this->create)) {
				$values['builder']['create'] = $this->create;
			}

			if (!empty($this->change)) {
				$values['builder']['change'] = $this->change;
			}

			if (!empty($this->check)) {
				$values['builder']['check'] = $this->check;
			}

			if (!empty($this->cellSave)) {
				$values['builder']['cellSave'] = $this->cellSave;
			}

			if (!empty($this->cellClick)) {
				$values['builder']['cellClick'] = $this->cellClick;
			}

			if (!empty($this->load)) {
				$values['builder']['load'] = $this->load;
			}

			if (!empty($this->cellDblClick)) {
				$values['builder']['cellDblClick'] = $this->cellDblClick;
			}

			if (!empty($this->cellKeyDown)) {
				$values['builder']['cellKeyDown'] = $this->cellKeyDown;
			}

			if (!empty($this->editModel)) {
				$values['builder']['editModel'] = $this->editModel;
			}

			if (!empty($this->summaryData)) {
				$values['builder']['summaryData'] = $this->summaryData;
			}

			if (!empty($this->formulas)) {
				$values['builder']['formulas'] = $this->formulas;
			}

			if (!empty($this->columnTemplate)) {
				$values['builder']['columnTemplate'] = $this->columnTemplate;
			}

			if (!empty($this->tabModel)) {
				$values['builder']['tabModel'] = $this->tabModel;
			}

			if ($this->dragOn == 1) {
				$this->dragModel = [
					'on'        => $this->dragOn,
					'diHelper'  => $this->dragdiHelper,
					'clsHandle' => $this->dragclsHandle,
				];
				$values['builder']['dragModel'] = $this->dragModel;
			}

			if ($this->dropOn == 1) {
				$this->dropModel = [
					'on' => $this->dropOn,
				];
				$values['builder']['dropModel'] = $this->dropModel;
			}

			if (!empty($this->moveNode)) {
				$values['builder']['moveNode'] = $this->moveNode;
			}

			if (!empty($this->dragModel)) {
				$values['builder']['dragModel'] = $this->dragModel;
			}

			if (!empty($this->summaryTitle)) {
				$values['builder']['summaryTitle'] = $this->summaryTitle;
			}

			if (!empty($this->dropModel)) {
				$values['builder']['dropModel'] = $this->dropModel;
			}

			if (!empty($this->contextMenu)) {
				$values['contextMenu'] = $this->contextMenu;
			}

			if (!empty($this->detailModel)) {
				$values['builder']['detailModel'] = $this->detailModel;
			}

			if (!empty($this->treeModel)) {
				$values['builder']['treeModel'] = $this->treeModel;
			}

			if (!empty($this->treeExpand)) {
				$values['builder']['treeExpand'] = $this->treeExpand;
			}

			if (!empty($this->subDetailModel)) {
				$values['subDetailModel'] = $this->subDetailModel;
			}

			if (!empty($this->detailContextMenu)) {
				$values['detailContextMenu'] = $this->detailContextMenu;
			}

			if (!empty($this->refresh)) {
				$values['builder']['refresh'] = $this->refresh;
			}

			if (!empty($this->postRenderInterval)) {
				$values['builder']['postRenderInterval'] = $this->postRenderInterval;
			}

			if (!empty($this->editorKeyUp)) {
				$values['builder']['editorKeyUp'] = $this->editorKeyUp;
			}

		}

		if (!empty($this->gridFunction)) {
			$this->paragrid_option['gridFunction'] = $this->gridFunction;
		}

		if (!empty($this->gridExtraFunction)) {

			foreach ($this->gridExtraFunction as $function) {
				$this->paragrid_option['extraFunction'][] = $function;
			}

		}

		if (!empty($this->otherFunction)) {
			$this->paragrid_option['otherFunction'] = $this->otherFunction;
		}

	}

	public function generateParagridScript() {

		$is_function = false;
		$context = Context::getContext();

		$paramGridVar = '';
		$jsScript = '';

		if (!$this->is_subModel) {

			foreach ($this->paragrid_option['paragrids'] as $key => $value) {

				if (isset($value['paramGridVar'])) {
					$paramGridVar = $value['paramGridVar'];
					$jsScript .= 'var ' . $value['paramGridVar'] . ';' . PHP_EOL;
					$jsScript .= 'var ' . $this->paramGridObj . ';' . PHP_EOL;
					$jsScript .= 'var sel' . $this->paramGridVar . '' . PHP_EOL;
				}

			}

			if (!empty($this->uppervar)) {
				$jsScript .= $this->uppervar;
			}
            
            if($this->docReady) {
                $jsScript .= '$(document).ready(function(){' . PHP_EOL;
            } else {
                $jsScript .= '';
            }

			foreach ($this->paragrid_option['paragrids'] as $key => $value) {

				if (empty($this->recIndx)) {

					if (!empty($this->ajaxUrl)) {
						$jsScript .= 'ajax' . $this->paramController . ' = ' . $this->ajaxUrl . ';' . PHP_EOL;
					}

					if (!empty($this->requestComplementaryModel)) {
						$jsScript .= 'var ' . $this->paramController . 'ComplementaryModel = ' . $this->requestComplementaryModel . ' ;' . PHP_EOL;
					}

					if ($this->needRequestModel) {
						$jsScript .= 'var ' . $this->paramController . 'Model = ' . $this->requestModel . ';' . PHP_EOL;
					}

				}

			}

			foreach ($this->paragrid_option as $key => $value) {

				if ($key == 'paragrids') {

					foreach ($this->paragrid_option[$key] as $element => $values) {

						if (empty($values['paramGridVar'])) {
							continue;
						}

						$this->paramGridVar = $values['paramGridVar'];
						$this->paramGridId = $values['paramGridId'];
						$this->paramGridObj = $values['paramGridObj'];

						$jsScript .= $this->paramGridObj . ' = {' . PHP_EOL;

						foreach ($values['builder'] as $option => $value) {

							if (is_array($value)) {
								$jsScript .= '      ' . $this->deployArrayScript($option, $value) . PHP_EOL;
							} else {
								$jsScript .= '      ' . $option . ': ' . $value . ',' . PHP_EOL;
							}

						}

						$jsScript .= '  };' . PHP_EOL;

						if (!empty($this->requestComplementaryModel) && $this->needRequestModel) {
							$jsScript .= $this->paramGridObj . '.dataModel = ' . $this->paramController . 'ComplementaryModel;' . PHP_EOL;
						}

						if (!$this->onlyObject) {
							$jsScript .= '  ' . $this->paramGridVar . ' = pq.grid(\'#' . $this->paramGridId . '\', ' . $this->paramGridObj . ');' . PHP_EOL;

							$jsScript .= '   sel' . $this->paramGridVar . ' = ' . $this->paramGridVar . '.SelectRow();' . PHP_EOL;
							$jsScript .= ' $(\'#' . $this->paramGridId . '\').pqGrid("refresh");' . PHP_EOL;

							if (isset($this->gridAfterLoadFunction)) {
								$jsScript .= $this->gridAfterLoadFunction . PHP_EOL;
							}

							if (isset($values['contextMenu']) && !$this->functionContextMenu) {

								foreach ($values['contextMenu'] as $contextMenu => $value) {
									$jsScript .= '  $("' . $contextMenu . '").contextMenu({' . PHP_EOL;

									foreach ($value as $option => $value) {

										if (is_array($value)) {
											$jsScript .= '      ' . $this->deployArrayScript($option, $value) . PHP_EOL;
										} else {
											$jsScript .= '      ' . $option . ': ' . $value . ',' . PHP_EOL;
										}

									}

									$jsScript .= '  });' . PHP_EOL;
								}

							}

							if (isset($values['subDetailModel'])) {

								foreach ($values['subDetailModel'] as $detailModel => $value) {
									$jsScript .= '  var ' . $detailModel . ' = function( data ) {' . PHP_EOL;
									$jsScript .= '      return  {' . PHP_EOL;

									foreach ($value as $option => $value) {
										$jsScript .= '      ' . $value . PHP_EOL;

									}

									$jsScript .= '      };' . PHP_EOL;
									$jsScript .= '  };' . PHP_EOL;
								}

							}

							if (isset($values['detailContextMenu'])) {

								foreach ($values['detailContextMenu'] as $contextMenu => $value) {
									$jsScript .= '  $("' . $contextMenu . '").contextMenu({' . PHP_EOL;

									foreach ($value as $option => $value) {

										if (is_array($value)) {
											$jsScript .= '      ' . $this->deployArrayScript($option, $value) . PHP_EOL;
										} else {
											$jsScript .= '      ' . $option . ': ' . $value . ',' . PHP_EOL;
										}

									}

									$jsScript .= '  });' . PHP_EOL;
								}

							}

						}

					}

				}

			}
            
            if($this->docReady) {
                $jsScript .= '});' . PHP_EOL . PHP_EOL;
            } else {
                $jsScript .= '';
            }

		} else {
			$is_function = true;

			foreach ($this->paragrid_option as $key => $value) {

				if ($key == 'paragrids') {

					foreach ($this->paragrid_option[$key] as $element => $values) {

						if (empty($values['paramGridVar'])) {
							continue;
						}

						foreach ($values['builder'] as $option => $value) {

							if (is_array($value)) {
								$jsScript .= '      ' . $this->deployArrayScript($option, $value) . PHP_EOL;
							} else {
								$jsScript .= '      ' . $option . ': ' . $value . ',' . PHP_EOL;
							}

						}

					}

				}

			}

		}

		if (isset($values['contextMenu']) && $this->functionContextMenu) {
			$jsScript .= 'function launch' . $this->paramClass . 'ContextMenu() {' . PHP_EOL;

			foreach ($values['contextMenu'] as $contextMenu => $value) {
				$jsScript .= '  $("' . $contextMenu . '").contextMenu({' . PHP_EOL;

				foreach ($value as $option => $value) {

					if (is_array($value)) {
						$jsScript .= '      ' . $this->deployArrayScript($option, $value) . PHP_EOL;
					} else {
						$jsScript .= '      ' . $option . ': ' . $value . ',' . PHP_EOL;
					}

				}

				$jsScript .= '  });' . PHP_EOL;
			}

			$jsScript .= '}' . PHP_EOL;

		}

		if ($key == 'extraFunction') {

			foreach ($this->paragrid_option[$key] as $function) {
				$jsScript .= $function;

			}

		}

		foreach ($this->paragrid_option as $key => $value) {

			if ($key == 'gridFunction') {
				$is_function = true;

				foreach ($this->paragrid_option[$key] as $function => $value) {
					$jsScript .= 'function ' . $function . ' {' . PHP_EOL;
					$jsScript .= $value . PHP_EOL;
					$jsScript .= '}' . PHP_EOL;
				}

			}

			if ($key == 'otherFunction') {

				foreach ($this->paragrid_option[$key] as $function => $value) {
					$jsScript .= 'function ' . $function . ' {' . PHP_EOL;
					$jsScript .= $value . PHP_EOL;
					$jsScript .= '}' . PHP_EOL;
				}

			}

		}

		if ($is_function == false) {

			if (is_null($this->requestField) && $this->needColModel) {
				$jsScript .= 'function get' . $this->paramClass . 'Fields() {' . PHP_EOL;
				$jsScript .= '  var result;' . PHP_EOL;
				$jsScript .= '  $.ajax({' . PHP_EOL;
				$jsScript .= '      type: \'GET\',' . PHP_EOL;
				$jsScript .= '      url: AjaxLink' . $this->paramController . ',' . PHP_EOL;
				$jsScript .= '      data: {' . PHP_EOL;
				$jsScript .= '          action: \'get' . $this->paramClass . 'Fields\',' . PHP_EOL;
				$jsScript .= '          ajax: true' . PHP_EOL;
				$jsScript .= '      },' . PHP_EOL;
				$jsScript .= '      async: false,' . PHP_EOL;
				$jsScript .= '      dataType: \'json\',' . PHP_EOL;
				$jsScript .= '      success: function (data) {' . PHP_EOL;
				$jsScript .= '          result = data;' . PHP_EOL;
				$jsScript .= '      }' . PHP_EOL;
				$jsScript .= '  });' . PHP_EOL;
				$jsScript .= '  return result;' . PHP_EOL;
				$jsScript .= '}' . PHP_EOL . PHP_EOL;
			} else {
				$jsScript .= $this->requestField;
			}

			if (is_null($this->requestCustomModel)) {
				$jsScript .= 'function get' . $this->paramClass . 'Request() {' . PHP_EOL;
				$jsScript .= '  var result;' . PHP_EOL;
				$jsScript .= '  $.ajax({' . PHP_EOL;
				$jsScript .= '      type: \'GET\',' . PHP_EOL;
				$jsScript .= '      url: AjaxLink' . $this->paramController . ',' . PHP_EOL;
				$jsScript .= '      data: {' . PHP_EOL;
				$jsScript .= '          action: \'get' . $this->paramClass . 'Request\',' . PHP_EOL;
				$jsScript .= '          ajax: true' . PHP_EOL;
				$jsScript .= '      },' . PHP_EOL;
				$jsScript .= '      async: false,' . PHP_EOL;
				$jsScript .= '      dataType: \'json\',' . PHP_EOL;
				$jsScript .= '      success: function (data) {' . PHP_EOL;
				$jsScript .= '          result = data;' . PHP_EOL;
				$jsScript .= '      }' . PHP_EOL;
				$jsScript .= '  });' . PHP_EOL;
				$jsScript .= '  return result;' . PHP_EOL;
				$jsScript .= '}' . PHP_EOL;
			} else {
				$jsScript .= $this->requestCustomModel;
			}

		}

		return $jsScript;

	}

	public function deployArrayScript($option, $value, $sub = false) {

		if ($sub) {

			if (is_string($option) && is_array($value) && !Tools::is_assoc($value)) {
				$jsScript = $option . ': [' . PHP_EOL;

				foreach ($value as $suboption => $value) {

					if (is_array($value)) {
						$jsScript .= '          ' . $this->deployArrayScript($suboption, $value, true);
					} else

					if (is_string($suboption)) {
						$jsScript .= '          ' . $suboption . ': ' . $value . ',' . PHP_EOL;
					} else {
						$jsScript .= '          ' . $value . ',' . PHP_EOL;
					}

				}

				$jsScript .= '          ],' . PHP_EOL;
				return $jsScript;

			} else {

				if (is_string($option)) {
					$jsScript = $option . ': {' . PHP_EOL;
				} else {
					$jsScript = ' {' . PHP_EOL;
				}

			}

		} else {

			if (is_string($option)) {
				$jsScript = $option . ': {' . PHP_EOL;
			} else {
				$jsScript = ' {' . PHP_EOL;
			}

		}

		foreach ($value as $suboption => $value) {

			if (is_array($value)) {
				$jsScript .= '          ' . $this->deployArrayScript($suboption, $value, true);
			} else

			if (is_string($suboption)) {
				$jsScript .= '          ' . $suboption . ': ' . $value . ',' . PHP_EOL;
			} else {
				$jsScript .= '          ' . $value . ',' . PHP_EOL;
			}

		}

		if ($sub) {
			$jsScript .= '          },' . PHP_EOL;
		} else {
			$jsScript .= '      },' . PHP_EOL;
		}

		return $jsScript;

	}

	protected function l($string, $class = 'ParamGrid', $addslashes = false, $htmlentities = true) {

		// if the class is extended by a plugin, use plugins/[plugin_name]/xx.php lang file
		$currentClass = get_class($this);

		if (Plugin::getPluginNameFromClass($currentClass)) {
			$string = str_replace('\'', '\\\'', $string);

			return Context::getContext()->translations->getPluginTranslation(Plugin::$classInPlugin[$currentClass], $string, $currentClass);
		}

		global $_LANGADM;

		if ($class == __CLASS__) {
			$class = 'ParamGrid';
		}

		$key = md5(str_replace('\'', '\\\'', $string));
		$str = (array_key_exists(get_class($this) . $key, $_LANGADM)) ? $_LANGADM[get_class($this) . $key] : ((array_key_exists($class . $key, $_LANGADM)) ? $_LANGADM[$class . $key] : $string);
		$str = $htmlentities ? htmlentities($str, ENT_QUOTES, 'utf-8') : $str;

		return str_replace('"', '&quot;', ($addslashes ? addslashes($str) : stripslashes($str)));
	}

}
