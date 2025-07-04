<?php

class Upgrader {

	public $context;
    
    public $phenyxTools;

	public static $instance;

	public function __construct() {

		$this->className = get_class($this);
		$this->context = Context::getContext();

		if (!isset($this->context->phenyxConfig)) {
			$this->context->phenyxConfig = Configuration::getInstance();
		}

		if (!isset($this->context->_hook)) {
			$this->context->_hook = Hook::getInstance();
		}
        
        if (!isset($this->context->_tools)) {
            $this->context->_tools = PhenyxTool::getInstance();
        }
        
        $this->phenyxTools = new PhenyxTools();

	}

	public static function getInstance() {

		if (!static::$instance) {
			static::$instance = new Upgrader();
		}

		return static::$instance;
	}

	public function executeWebService($data) {

		Hook::getInstance()->exec('actionWebEphenyx', ['data' => $data]);
		$action = $data->action;

		switch ($action) {
		case 'checkLicence':

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo json_encode($license);
			break;
		case 'getPhenyxPlugins':
			$installedPlugins = $data->plugins;
			$plugins = IoPlugin::getPhenyxPluginsOnDisk($license->id, $installedPlugins);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			Context::getContext()->license = [];
			echo json_encode($plugins);
			break;
		case 'getZipPlugin':
			$plugin = $data->plugin;
			$link = [
				'pluginLink' => IoPlugin::generatePluginZip($plugin),
			];

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo json_encode($link);
			break;
		case 'getJsonFile':
			$md5List = $this->phenyxTools->generateCurrentJson();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo json_encode($md5List);
			break;
		case 'getOwnJsonFile':
			$result = $this->phenyxTools->generateOwnCurrentJson();

			if ($result) {

				$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			} else {
				$status = $_SERVER['SERVER_PROTOCOL'] . ' 400 Error';

			}

			header($status);
			header('Content-Type: application/json');
			echo $status;
			break;
		case 'getDefaultTheme':
			$default_theme = $this->phenyxTools->default_theme;

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $default_theme;
			break;
		case 'getdBParam':
			$md5List = [
				'_DB_SERVER_' => _DB_SERVER_,
				'_DB_NAME_'   => _DB_NAME_,
				'_DB_USER_'   => _DB_USER_,
				'_DB_PASSWD_' => _DB_PASSWD_,
			];

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo json_encode($md5List);
			break;
		case 'getInstalledLangs':
			$langs = $this->context->_tools->getIoLangs();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo json_encode($langs);
			break;
		case 'getPluginOnDisk':
			$plugins = $this->phenyxTools->plugins;

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo json_encode($plugins);
			break;

		case 'executeCron':
			CronJobs::runTasksCrons();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $status;
			break;
		case 'indexBookAccount':
			$account = StdAccount::getInstance();
			$account->archiveRequest();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $status;
			break;

		case 'getOject':
			$query = $data->query;
			$class = $data->classe;
			$idObject = $this->executeSqlRequest($query, 'getValue');
			$object = new $class($idObject);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo json_encode($object);
			break;
		case 'getTranslation':
			$google_api_key = Configuration::get('EPH_GOOGLE_TRANSLATE_API_KEY');
			Translation::getInstance();
			$iso = $data->iso;
			$origin = $data->origin;
			$translation = $this->context->_tools->getGoogleTranslation($google_api_key, $origin, $iso);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo json_encode($translation);
			break;
		case 'getTranslations':
			$iso_codes = $data->iso_codes;
			$translation = new Translation(null, $iso_codes);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo json_encode($translation->translations);
			break;
		case 'createTranslation':
			$object = $data->object;
			$result = Translation::addTranslation($object);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'createNotification':
			$object = $data->object;
			$result = PhenyxNotification::addNotification($object);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'cleanDirectory':
			$path = $data->directory;
			$this->context->_tools->removeEmptyDirs($path);
			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			break;
		case 'cleanEmptyDirectory':
			$this->context->_tools->cleanEmptyDirectory();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			break;
		case 'deleteBulkFile':
			$files = $data->files;
			$this->context->_tools->deleteBulkFiles($files);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			break;
		case 'pushSqlRequest':
			$query = $data->query;
			$method = $data->method;
			$request = $this->executeSqlRequest($query, $method);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo json_encode($request);
			break;
		case 'getGenerateTabs':

			$topbars = $this->context->_tools->getTabs();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo json_encode($topbars);
			break;
		case 'showTab':
			$id_back_tab = $data->id_back_tab;
			$backTab = BackTab::getInstance($id_back_tab);
			$backTab->active = 1;
			$backTab->update();
			$this->context->_tools->generateTabs(false);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo $status;
			break;
		case 'hideTab':
			$id_back_tab = $data->id_back_tab;
			$backTab = BackTab::getInstance($id_back_tab);
			$backTab->active = 0;
			$backTab->update();
			$this->context->_tools->generateTabs(false);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

			header($status);
			header('Content-Type: application/json');
			echo $status;
			break;
		case 'writeNewSettings':
			$version = $data->version;
			$result = $this->phenyxTools->writeNewSettings($version);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'cleanBckTab':
			$result = $this->phenyxTools->cleanBackTabs();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'cleanMeta':
			$result = $this->phenyxTools->cleanMetas();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'cleanPlugin':
			$result = $this->phenyxTools->cleanPlugins();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'cleanHook':
			$result = $this->phenyxTools->cleanHook();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'alterSqlTable':
			$table = $data->table;
			$column = $data->column;
			$type = $data->type;
			$after = $data->after;
			$result = $this->phenyxTools->alterSqlTable($table, $column, $type, $after);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'mergeLanuages':
			$result = $this->phenyxTools->mergeLanguages();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'mergeGlobalLanuages':
			$translations = $data->translations;
			$translation = new Translation();
			$translation->updateGlobalTranslations($translations);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $status;
			break;
		case 'generatePhenyxData':
			$request = PhenyxBackup::generatePhenyxData();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo json_encode($request);
			break;
		case 'buidlIndexation':
			Search::indexation();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo json_encode($request);
			break;
		case 'generateClassIndex':
			$this->context->_tools->generateIndex();

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $status;
			break;
		case 'downloadFile':
			$content = $data->content;
			$destination = $data->destination;
			$result = $this->phenyxTools->getIoFiles($content, $destination);

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'downloadZipFile':
			$fileTest = fopen("testdownloadZipFile.txt", "w");
			$zipPath = $data->zipPath;
			$content = file_get_contents($zipPath);
			file_put_contents(_EPH_UPGRADER_DIR_ . 'upgrade.zip', $content);

			if (file_exists(_EPH_UPGRADER_DIR_ . 'upgrade.zip')) {
				$zip = new ZipArchive;

				if ($zip->open(_EPH_UPGRADER_DIR_ . 'upgrade.zip') === TRUE) {
					$zip->extractTo(_EPH_ROOT_DIR_ . '/');
					$zip->close();
					unlink(_EPH_UPGRADER_DIR_ . 'upgrade.zip');
					$result = true;
				} else {
					$result = false;
				}

			} else {
				$result = false;
			}

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;
		case 'deleteFiles':
			$files = $data->files;
			$result = true;

			foreach ($files as $file) {

				if (file_exists(_EPH_ROOT_DIR_ . $file)) {
					$result &= unlink(_EPH_ROOT_DIR_ . $file);
				}

			}

			if (ob_get_length() != 0) {
				header('Content-Type: application/json');
			}

			$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
			header($status);
			header('Content-Type: application/json');
			echo $result;
			break;

		}

	}

	public function executeSqlRequest($query, $method) {

		switch ($method) {
		case 'execute':
			return Db::getInstance()->execute($query);
			break;
		case 'executeS':
			return Db::getInstance()->executeS($query);
			break;
		case 'getValue':
			return Db::getInstance()->getValue($query);
			break;
		case 'getRow':
			return Db::getInstance()->getRow($query);
			break;
		}

	}

	public function instalTab($class_name, $name, $function = true, $plugin = null, $idParent = null, $parentName = null, $position = null, $openFunction = null, $divider = 0) {

		$translator = Language::getInstance();

		if (is_null($parentName) && is_null($idParent)) {
			return false;
		}

		if (!is_null($parentName)) {
			$idParent = (int) BackTab::getIdFromClassName($parentName);

			if (!$idParent) {
				return false;
			}

		}

		$idTab = (int) BackTab::getIdFromClassName($class_name);

		if (!$idTab) {
			$tab = new BackTab();

			if ($function) {

				if (!is_null($openFunction)) {
					$tab->function = $openFunction;
				} else {
					$tab->function = 'openAjaxController(\'' . $class_name . '\')';
				}

			}

			$tab->plugin = $plugin;
			$tab->id_parent = $idParent;
			$tab->class_name = $class_name;
			$tab->has_divider = $divider;
			$tab->active = 1;
			$tab->name = [];

			foreach (Language::getLanguages(true) as $lang) {
				$tab->name[$lang['id_lang']] = $translator->getGoogleTranslation($name, $lang['iso_code']);
			}

			unset($lang);
			$result = $tab->add(true, false, true, $position);
			return $this->deployPluginMeta(strtolower($class_name), $name, 'admin');
		} else {
			$tab = new BackTab($idTab);

			if ($function) {

				if (!is_null($openFunction)) {
					$tab->function = $openFunction;
				} else {
					$tab->function = 'openAjaxController(\'' . $class_name . '\')';
				}

			}

			$tab->plugin = $plugin;
			$tab->id_parent = $idParent;
			$tab->class_name = $class_name;
			$tab->has_divider = $divider;
			$tab->active = 1;
			$tab->name = [];

			foreach (Language::getLanguages(true) as $lang) {
				$tab->name[$lang['id_lang']] = $translator->getGoogleTranslation($name, $lang['iso_code']);
			}

			unset($lang);
			$result = $tab->update(true, false, $position);
			return $this->deployMeta(strtolower($class_name), $name, 'admin');
		}

	}

	public function deployMeta($page, $name, $type = 'front') {

		$result = true;
		$idMeta = Meta::getIdMetaByPage($page);

		if (!$idMeta) {
			$translator = Language::getInstance();
			$meta = new Meta();
			$meta->controller = $type;
			$meta->page = $page;
			$meta->plugin = $this->name;

			foreach (Language::getLanguages(true) as $lang) {
				$meta->title[$lang['id_lang']] = $translator->getGoogleTranslation($name, $lang['iso_code']);
				$meta->url_rewrite[$lang['id_lang']] = Tools::str2url($meta->title[$lang['id_lang']]);
			}

			$result = $meta->add();
		}

		return $result;
	}
    
    public function installMeta($meta) {

        $meta = Tools::jsonDecode(Tools::jsonEncode($meta), true);
        $exist = Meta::getIdMetaByPage($meta->page);
        if(!$exist) {
            $newObjet = new Meta();
            foreach($meta as $key => $value) {
                if(is_array($value)) {
                    foreach (Language::getLanguages(true) as $lang) {
                        if (property_exists($newObjet, $key) && isset($value[$lang['iso_code']])) {
				            $newObjet->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
			             }
                    
                    }
                } else if (property_exists($newObjet, $key) && $key != 'id_meta') {
				    $newObjet->{$key} = $value;
			 }
            
            }
            
            $result = $newObjet->add();
        } else {
            $newObjet = new Meta($exist);
            foreach($meta as $key => $value) {
                if(is_array($value)) {
                    foreach (Language::getLanguages(true) as $lang) {
                        if (property_exists($newObjet, $key) && isset($value[$lang['iso_code']])) {
				            $newObjet->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
			             }
                    
                    }
                } else if (property_exists($newObjet, $key) && $key != 'id_meta') {
				    $newObjet->{$key} = $value;
			 }
            
            }
            
            $result = $newObjet->update();
        }
        
        return $result;
               
		
	}
    
    public function installMetas($metas) {

        $result = true;
        $metas = Tools::jsonDecode(Tools::jsonEncode($metas), true);
        foreach($metas as $meta) {
            $exist = Meta::getIdMetaByPage($meta->page);
            if(!$exist) {
                $newObjet = new Meta();
                foreach($meta as $key => $value) {
                    if(is_array($value)) {
                        foreach (Language::getLanguages(true) as $lang) {
                            if (property_exists($newObjet, $key) && isset($value[$lang['iso_code']])) {
				                $newObjet->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
			                 }                    
                        }
                    } else if (property_exists($newObjet, $key) && $key != 'id_meta') {
				        $newObjet->{$key} = $value;
			     }
            
                }
            
                $result &= $newObjet->add();
            } else {
                $newObjet = new Meta($exist);
                foreach($meta as $key => $value) {
                    if(is_array($value)) {
                        foreach (Language::getLanguages(true) as $lang) {
                            if (property_exists($newObjet, $key) && isset($value[$lang['iso_code']])) {
				                $newObjet->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
			                 }                    
                        }
                    } else if (property_exists($newObjet, $key) && $key != 'id_meta') {
				        $newObjet->{$key} = $value;
			     }
            
                }
            
                $result &= $newObjet->update();
            }
        }
        
        
        return $result;
               
		
	}
    
    public function installBackTabs($backtabs) {
        
        $result = true;
        $backtabs = Tools::jsonDecode(Tools::jsonEncode($backtabs), true);
        foreach($backtabs as $backtab) {
            if(!empty($backtab['plugin'])) {
                if(!Plugin::isInstalled($backtab['plugin']))  {
                    continue;
                }
            }
            $exist = BackTab::getIdBackTabByClass($backtab['class_name']);
            if(!$exist) {
            
                $newObjet = new BackTab();
                foreach($backtab as $key => $value) {
                    if(is_array($value)) {
                        foreach (Language::getLanguages(true) as $lang) {
                            if (property_exists($newObjet, $key) && isset($value[$lang['iso_code']])) {
				                $newObjet->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
			                }                    
                        }
                    } else if (property_exists($newObjet, $key) && $key != 'id_back_tab') {
				        $newObjet->{$key} = $value;
			     }
            
                }
                $newObjet->id_parent = BackTab::getIdBackTabByClass($backtab['parent_class']);
            
                $result &= $newObjet->add();
            } else {
            
                $newObjet = new BackTab($exist);
                foreach($backtab as $key => $value) {
                    if(is_array($value)) {
                        foreach (Language::getLanguages(true) as $lang) {
                            if (property_exists($newObjet, $key) && isset($value[$lang['iso_code']])) {
				                $newObjet->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
			                 }                    
                        }
                    } else if (property_exists($newObjet, $key) && $key != 'id_back_tab') {
				        $newObjet->{$key} = $value;
			     }
            
                }
                $newObjet->id_parent = BackTab::getIdBackTabByClass($backtab['parent_class']);
                $result &= $newObjet->update();
            }
        }
        
        $this->context->_tools->generateTabs(false);
        
        return $result;
		
	}
    
    public function installBackTab($backtab) {
        
        $backtab = Tools::jsonDecode(Tools::jsonEncode($backtab), true);
        $exist = BackTab::getIdBackTabByClass($backtab['class_name']);
        if(!empty($backtab['plugin'])) {
            if(!Plugin::isInstalled($backtab['plugin']))  {
                return true;
            }
        }
        if(!$exist) {
            
            $newObjet = new BackTab();
            foreach($backtab as $key => $value) {
                if(is_array($value)) {
                    foreach (Language::getLanguages(true) as $lang) {
                        if (property_exists($newObjet, $key) && isset($value[$lang['iso_code']])) {
				            $newObjet->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
			             }
                    
                    }
                } else if (property_exists($newObjet, $key) && $key != 'id_back_tab') {
				    $newObjet->{$key} = $value;
			 }
            
            }
            $newObjet->id_parent = BackTab::getIdBackTabByClass($backtab['parent_class']);
            
            $result = $newObjet->add();
        } else {
            
            $newObjet = new BackTab($exist);
            foreach($backtab as $key => $value) {
                if(is_array($value)) {
                    foreach (Language::getLanguages(true) as $lang) {
                        if (property_exists($newObjet, $key) && isset($value[$lang['iso_code']])) {
				            $newObjet->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
			             }
                    
                    }
                } else if (property_exists($newObjet, $key) && $key != 'id_back_tab') {
				    $newObjet->{$key} = $value;
			 }
            
            }
            $newObjet->id_parent = BackTab::getIdBackTabByClass($backtab['parent_class']);
            $result = $newObjet->update();
        }
        $this->context->_tools->generateTabs(false);
        
        return $result;
		
	}


}
