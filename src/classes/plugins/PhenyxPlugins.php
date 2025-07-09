<?php
use Defuse\Crypto\Crypto;
use \Curl\Curl;
/**
 * Class ActualiteCore
 *
 * @since 1.9.1.0
 */
class PhenyxPlugins extends PhenyxObjectModel {
    
    
    
    protected static $instance;

    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'phenyx_plugin',
        'primary'   => 'id_phenyx_plugin',
        'multilang' => true,
        'fields'    => [
            'plugin'     => ['type' => self::TYPE_STRING],
            'category'     => ['type' => self::TYPE_STRING],
            'version'     => ['type' => self::TYPE_STRING],
            'depends'     => ['type' => self::TYPE_STRING],
            'associate'   => ['type' => self::TYPE_STRING],
            'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            /* Lang fields */
            'name'          => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'description'        => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 3999999999999],
        ],
    ];
    /** @var string Name */
    
    public $plugin;
    public $category;
    public $version;
    public $depends = [];
    public $associate = [];
    public $date_add;
    public $date_upd;
    public $name;
    public $description;
    
    public $image_link;
    
    public $plugins_categories = [];
    
    public function __construct($id = null, $idLang = null) {

		parent::__construct($id, $idLang);
        $this->context = Context::getContext();
        
       
        $this->plugins_categories['administration'] = $this->l('Administration');
        $this->plugins_categories['advertising_marketing'] = $this->l('Advertising and Marketing');
        $this->plugins_categories['analytics_stats'] = $this->l('Analytics and Stats');
        $this->plugins_categories['billing_invoicing'] = $this->l('Taxes & Invoicing');
        $this->plugins_categories['checkout'] = $this->l('Checkout');
        $this->plugins_categories['content_management'] = $this->l('Content Management');
        $this->plugins_categories['customer_reviews'] = $this->l('Customer Reviews');
        $this->plugins_categories['export'] = $this->l('Export');
        $this->plugins_categories['front_office_features'] = $this->l('Front office Features');
        $this->plugins_categories['i18n_localization'] = $this->l('Internationalization and Localization');
        $this->plugins_categories['merchandizing'] = $this->l('Merchandising');
        $this->plugins_categories['migration_tools'] = $this->l('Migration Tools');
        $this->plugins_categories['payments_gateways'] = $this->l('Payments and Gateways');
        $this->plugins_categories['payment_security'] = $this->l('Site certification & Fraud prevention');
        $this->plugins_categories['pricing_promotion'] = $this->l('Pricing and Promotion');
        $this->plugins_categories['quick_bulk_update'] = $this->l('Quick / Bulk update');
        $this->plugins_categories['search_filter'] = $this->l('Search and Filter');
        $this->plugins_categories['seo'] = $this->l('SEO');
        $this->plugins_categories['shipping_logistics'] = $this->l('Shipping and Logistics');
        $this->plugins_categories['slideshows'] = $this->l('Slideshows');
        $this->plugins_categories['smart_shopping'] = $this->l('Comparison site & Feed management');
        $this->plugins_categories['market_place'] = $this->l('Marketplace');
        $this->plugins_categories['others'] = $this->l('Other Plugins');
        $this->plugins_categories['mobile'] = $this->l('Mobile');
        $this->plugins_categories['dashboard'] = $this->l('Dashboard');
        $this->plugins_categories['i18n_localization'] = $this->l('Internationalization & Localization');
        $this->plugins_categories['emailing'] = $this->l('Emailing & SMS');
        $this->plugins_categories['social_networks'] = $this->l('Social Networks');
        $this->plugins_categories['social_community'] = $this->l('Social & Community');


		if ($this->id && !is_null($this->depends)) {
            if(!isset($this->context->link)) {
                $this->context->link = new Link();
            }
			$this->depends = Tools::jsonDecode($this->depends, true);
            $this->associate = Tools::jsonDecode($this->associate, true);
            $this->image_link = '/includes/plugins/'.$this->plugin.'/logo.png';
		}

	}
    
    public static function getInstance($id = null, $idLang = null) {

		if (!PhenyxPlugins::$instance) {
			PhenyxPlugins::$instance = new PhenyxPlugins($id, $idLang);
		}

		return PhenyxPlugins::$instance;
	}
    
    public function add($autoDate = false, $nullValues = false) {

		$this->depends = Tools::jsonEncode($this->depends);
        $this->associate = Tools::jsonEncode($this->associate);

		if (!parent::add($autoDate, $nullValues)) {
			return false;
		}

		return true;
	}

	public function update($nullValues = false) {

		$this->depends = Tools::jsonEncode($this->depends);
        $this->associate = Tools::jsonEncode($this->associate);
		return parent::update($nullValues);

	}
    
    public function delete() {
        
        if(is_dir(_EPH_PLUGIN_DIR_ .$this->plugin.'/')) {
            $pluginDir = _EPH_PLUGIN_DIR_ . $this->plugin;
        } else if(is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ .$this->plugin.'/')) {
            $pluginDir = _EPH_SPECIFIC_PLUGIN_DIR_ . $this->plugin;
        }
        
        $success = parent::delete();
        if($success) {
            $this->recursiveDeleteOnDisk($pluginDir);
        }

		return $success;
	}
    
    
    protected function recursiveDeleteOnDisk($dir) {

        if (strpos(realpath($dir), realpath(_EPH_PLUGIN_DIR_)) === false) {
            if (strpos(realpath($dir), realpath(_EPH_SPECIFIC_PLUGIN_DIR_)) === false) {
                return;
            }
        }

        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {

                if ($object != '.' && $object != '..') {

                    if (filetype($dir . '/' . $object) == 'dir') {
                        $this->recursiveDeleteOnDisk($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }

                }

            }

            reset($objects);
            rmdir($dir);
        }

    }
    
	
    
    
    
    public static function getPluginCollection() {
        
        
        $context = Context::getContext();
		$collection = [];
		$plugins = Db::getInstance()->executeS(
			(new DbQuery())
				->select('id_phenyx_plugin, plugin')
				->from('phenyx_plugin')
		);
        

		foreach ($plugins as $key => &$plugin) {
           
			$collection[$plugin['plugin']] = new PhenyxPlugins($plugin['id_phenyx_plugin'], $context->language->id);
		}
        
		return $collection;
	}
    
    public static function getInstanceByName($name) {

		$context = Context::getContext();
		$id_phenyx_plugin =  Db::getInstance()->getValue(
			(new DbQuery())
			->select('id_phenyx_plugin')
			->from('phenyx_plugin')
            ->where('plugin =   \'' . pSQL($name) . '\'')
		);
        
        if($id_phenyx_plugin > 0) {
            return new PhenyxPlugins($id_phenyx_plugin, $context->language->id);
        }
        
        return null;

	}
    
    public static function isRegisteredPlugin($name) {

		
		return  Db::getInstance()->getValue(
			(new DbQuery())
			->select('id_phenyx_plugin')
			->from('phenyx_plugin')
            ->where('plugin =   \'' . pSQL($name) . '\'')
		);

	}
    
    public static function getPluginsOnDisk() {
        
        $plugins = Plugin::getPluginsOnDisk();
        
        $result = true;
        
        foreach ($plugins as $km => $plugin) {
            $id_plug = self::isRegisteredPlugin($plugin->name);
            if($id_plug > 0) {
                
                $plug = new PhenyxPlugins($id_plug);
                $plug->plugin = $plugin->name;
                $plug->category = $plugin->tab;
                $plug->version = $plugin->version;
                $plug->depends = $plugin->dependencies;
                foreach (Language::getIDs(false) as $idLang) {
                    $plug->name[(int) $idLang]= $plugin->displayName;
                    $plug->description[(int) $idLang]= $plugin->description;
                }
                $result = $plug->update();  
            } else {
                
                $plug = new PhenyxPlugins();
                $plug->plugin = $plugin->name;
                $plug->category = $plugin->tab;
                $plug->version = $plugin->version;
                $plug->depends = $plugin->dependencies;
                foreach (Language::getIDs(false) as $idLang) {
                    $plug->name[(int) $idLang]= $plugin->displayName;
                    $plug->description[(int) $idLang]= $plugin->description;
                }
                $result = $plug->add(); 
            }
            
                       
        }
        
        return $result;
    }
	

}
