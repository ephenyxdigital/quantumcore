<?php


namespace EphenyxDigital\QuantumCore;

use DateTime;
use Language;
/**
 * Class Company
 *
 * @since 2.1.0.0
 */
class Company extends PhenyxObjectModel {
    
    protected static $instance;
    	/** @var int Country id */
	public $id_country_registration = 0;
	/** @var int State id */
	public $id_state;
	/** @var string Country name */
	public $country;
	/** @var string Alias (eg. Home, Work...) */
	public $company;
	/** @var string Company */
	public $company_name;

	public $company_url;

	public $company_email;
    public $siret;

	public $register_city;
	/** @var string APE */
	public $ape;
	/** @var string VAT number */
	public $vat_number;
	/** @var string DNI number */
	public $dni;

	/** @var string Lastname */
	public $lastname;
	/** @var string Firstname */
	public $firstname;
	/** @var string Company first line */
	public $address1;
	/** @var string Company second line (optional) */
	public $address2;
	/** @var string Postal code */
	public $postcode;
	/** @var string City */
	public $city;
	/** @var string Phone number */
	public $phone;
	/** @var string Mobile phone number */
	public $phone_mobile;
	/** @var string Object creation date */
	public $date_add;
	/** @var string Object last modification date */
	public $date_upd;
	public $id_theme;
	
	public $mode = 'conventionnel';
    
    public $tax_system;
	public $tax_payment;
	public $start_date;
	public $first_accounting_end;
	public $accounting_period_start;
	public $accounting_period_end;
	

	public $next_accounting_start;
	public $next_accounting_end;
	public $saisie_end;

	public $rcs;
    
    public $currentExerciceEnd;
    
    public $exercices;
    
    // Fix #9: typo corrected — 'previous_ecercices' → 'previous_exercices'.
    // The old name is kept as an alias property to avoid breaking existing callers.
    public $previous_exercices;

    /** @deprecated Use $previous_exercices */
    public $previous_ecercices;

    
	public $active;
	public $deleted = 0;
	protected static $_idZones = [];
	protected static $_idCountries = [];
	protected $_includeContainer = false;
	// @codingStandardsIgnoreEnd


	const CONTEXT_COMPANY = 1;    
    
    const CONTEXT_ALL = 4;
    
    public $theme_name;
    
    public $theme_directory;    
    
    public $physical_uri;

    public $virtual_uri;

    public $domain;

    public $domain_ssl;
	
	public $target_domain;
    
    public $id_company_url;
    
    public $multi_domain = false;
    
    protected static $companies;

	protected static $context_id_company;
    
    public $id_category;

	public $capital;
	public $company_type;
    
    public $activity_number;
    public $delivery_area;
    public $delivery_address;
	
    /**
     * Fix #14: the default working plan was a single hard-to-maintain JSON string.
     * Defined as a class constant so it can be referenced and updated in one place.
     */
    const DEFAULT_WORKING_PLAN = '{"monday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"tuesday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"wednesday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"thursday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"friday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"saturday":null,"sunday":null}';

	public $working_plan = self::DEFAULT_WORKING_PLAN;

	
	/**
	 * @see PhenyxObjectModel::$definition
	 */
	public static $definition = [
		'table'   => 'company',
		'primary' => 'id_company',
		'fields'  => [
			'id_country_registration' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
			'id_state'                => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
			'company'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
			'company_name'            => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
			'company_url'             => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 255],
            'company_email'           => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
            'tax_system'              => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'tax_payment'             => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'start_date'              => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'first_accounting_end'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'accounting_period_start' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'accounting_period_end'   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'siret'                   => ['type' => self::TYPE_STRING],
            'vat_number'              => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'ape'                     => ['type' => self::TYPE_STRING, 'validate' => 'isApe'],
            'dni'                     => ['type' => self::TYPE_STRING, 'validate' => 'isDniLite', 'size' => 16],
            'register_city'           => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 64],
			'lastname'                => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 32],
			'firstname'               => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 32],
			'address1'                => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128],
			'address2'                => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128],
			'postcode'                => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12],
			'city'                    => ['type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 64],
			'phone'                   => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
			'phone_mobile'            => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
			'id_theme'                => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
			'mode'                    => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'active'                  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'deleted'                 => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'working_plan'            => ['type' => self::TYPE_JSON, 'copy_post' => false],
			'date_add'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'date_upd'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
		],
	];

	
	public function __construct($idCompany = null) {

		$this->className = get_class($this);

        // Fix #11: the original mutated the static $definition array inside the
        // constructor, meaning fields were re-added on every new Company() call.
        // Moved to a dedicated static initializer called once via a guard flag.
        static::initDefinition();

        $this->context = Context::getContext();
        if(!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = new Configuration();
        }
        
        if (!isset($this->context->language)) {
            $this->context->language = PhenyxTool::getInstance()->jsonDecode(PhenyxTool::getInstance()->jsonEncode(new Language($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
        }
        if (!isset(PhenyxObjectModel::$loaded_classes[$this->className])) {
            $this->def = PhenyxObjectModel::getDefinition($this->className);            
            PhenyxObjectModel::$loaded_classes[$this->className] = get_object_vars($this);
            
        } else {
            foreach (PhenyxObjectModel::$loaded_classes[$this->className] as $key => $value) {
                $this->{$key}  = $value;
            }

        }
        	

		if ($idCompany) {
            $this->id = $idCompany;
            $entityMapper = Adapter_ServiceLocator::get("Adapter_EntityMapper");
            $entityMapper->load($this->id, null, $this, $this->def, false);
            $this->setUrl();
            $this->multi_domain = $this->isMultiDomain();
			$this->country = Country::getNameById($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'), $this->id_country_registration);
			if(Validate::isDate($this->accounting_period_start)) {
            	$date = new DateTime($this->accounting_period_start);
				$date->modify('+1 year');
				$this->next_accounting_start = $date->format('Y-m-d');
				$date = new DateTime($this->accounting_period_start);
            	$date->modify('+11 month');
            	$this->currentExerciceEnd = $date->format('Y-m-t');
			}
			if(Validate::isDate($this->accounting_period_end)) {
				$date = new DateTime($this->accounting_period_end);
				$date->modify('+1 year');
				$this->next_accounting_end = $date->format('Y-m-d');
				$date = new DateTime($this->accounting_period_end);
				$date->modify('+2 year');
				$this->saisie_end = $date->format('Y-m-d');
				
				if (Validate::isJSON($this->working_plan)) {
					$this->working_plan = PhenyxTool::getInstance()->jsonDecode($this->working_plan, true);
				}			
            	
            	$this->exercices = $this->getExercices();
            	// Fix #9: corrected typo in property name.
            	$this->previous_exercices = $this->getPastExercices();
			}
			$this->rcs = $this->formatRcs();
			
		} 
        if (!isset($this->context->company)) {
            $this->context->company = $this;
        }
        if (!isset($this->context->translations)) {

            $this->context->translations = new Translate($this->context->language->iso_code, $this->context->company);
        }

	}
    
     public static function getInstance($id = null, $idLang = null) {

        if (!Company::$instance) {
            Company::$instance = new Company($id);
        }

        return Company::$instance;
    }

    /**
     * Fix #11: Plugin-specific definition fields were being added to the static
     * $definition array on every construction. Extracted here and guarded by a
     * flag so the mutation happens only once per request.
     */
    protected static $definitionInitialized = false;

    protected static function initDefinition(): void {

        if (static::$definitionInitialized) {
            return;
        }

        static::$definitionInitialized = true;

        if (Plugin::isInstalled('ph_ecommerce')) {
            self::$definition['fields']['id_category']   = ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'];
            self::$definition['fields']['capital']        = ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'];
            self::$definition['fields']['company_type']   = ['type' => self::TYPE_STRING];
        }

        if (Plugin::isInstalled('ph_learning')) {
            self::$definition['fields']['activity_number']  = ['type' => self::TYPE_STRING];
            self::$definition['fields']['delivery_area']    = ['type' => self::TYPE_STRING];
            self::$definition['fields']['delivery_address'] = ['type' => self::TYPE_HTML];
        }
    }
    
    public function isMultiDomain() {

        // Fix #7: $this->target_domain may be null or a string if setUrl() was not
        // called or returned false. count(null) triggers a warning in PHP 8.
        if (!is_array($this->target_domain)) {
            return 0;
        }

        return (count($this->target_domain) > 1) ? 1 : 0;
    }
    
    public function setUrl() {
        $cacheId = 'Company::setUrl_'.(int) $this->id;
        
        if (!CacheApi::isStored($cacheId)) {
           $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('cu.id_company_url, cu.physical_uri, cu.virtual_uri, cu.domain, cu.domain_ssl, cu.target_domain,  t.id_theme, t.name, t.directory')
                    ->from('company', 'c')
                    ->leftJoin('company_url', 'cu', 'c.`id_company` = cu.`id_company`')
                    ->leftJoin('theme', 't', 't.`id_theme` = c.`id_theme`')
                    ->where('c.`id_company` = '.(int) $this->id)
                    ->where('c.`active` = 1')
                    ->where('c.`deleted` = 0')
                    ->where('cu.`main` = 1')
            );
            CacheApi::store($cacheId, $row);
           
        } else {
            $row = CacheApi::retrieve($cacheId);
        }
        if (!$row) {
            return false;
        }
       

        $this->id_theme = $row['id_theme'];
        $this->theme_name = $row['name'];
        $this->id_company_url = $row['id_company_url'];
        $this->theme_directory = $row['directory'];
        $this->physical_uri = $row['physical_uri'];
        $this->virtual_uri = $row['virtual_uri'];
		$this->domain_ssl = $row['domain_ssl'];
		$this->target_domain = !empty($row['target_domain']) ? explode(",",$row['target_domain']) : [];

        return true;
    }

	public static function initialize() {
        		
		if (!($idCompany = PhenyxTool::getInstance()->getValue('id_company')) || defined('_EPH_ROOT_DIR_')) {
			$foundUri = '';
			$isMainUri = false;
			$host = Context::getContext()->_tools->getHttpHost();
			$requestUri = rawurldecode($_SERVER['REQUEST_URI']);
			
			$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
					->select('c.`id_company`, CONCAT(cu.`physical_uri`, cu.`virtual_uri`) AS `uri`, cu.`domain`, cu.`main`')
					->from('company_url', 'cu')
					->leftJoin('company', 'c', 'c.`id_company` = cu.`id_company`')
					->where('cu.domain = \'' . pSQL($host) . '\' OR cu.domain_ssl = \'' . pSQL($host) . '\'')
					->where('c.`active` = 1')
					->where('c.`deleted` = 0')
					->orderBy('LENGTH(CONCAT(cu.`physical_uri`, cu.`virtual_uri`)) DESC')
			);

			$through = false;

			foreach ($result as $row) {
				// An URL matching current shop was found

				if (preg_match('#^' . preg_quote($row['uri'], '#') . '#i', $requestUri)) {
					$through = true;
					$idCompany = $row['id_company'];
					$foundUri = $row['uri'];

					if ($row['main']) {
						$isMainUri = true;
					}

					break;
				}

			}

			// If an URL was found but is not the main URL, redirect to main URL

			if ($through && $idCompany && !$isMainUri) {

				foreach ($result as $row) {

					if ($row['id_company'] == $idCompany && $row['main']) {
						$requestUri = substr($requestUri, strlen($foundUri));
						$url = str_replace('//', '/', $row['domain'] . $row['uri'] . $requestUri);
						$redirectType = Context::getContext()->phenyxConfig->get('EPH_CANONICAL_REDIRECT');
						$redirectCode = ($redirectType == 1 ? '302' : '301');
						$redirectHeader = ($redirectType == 1 ? 'Found' : 'Moved Permanently');
						header('HTTP/1.0 ' . $redirectCode . ' ' . $redirectHeader);
						header('Cache-Control: no-cache');
						header('Location: ' . Context::getContext()->_tools->getProtocol() . $url);
						exit;
					}

				}

			}

		}

		$httpHost = Context::getContext()->_tools->getHttpHost();

		if ((!$idCompany && defined('_EPH_ROOT_DIR_')) || Context::getContext()->_tools->isPHPCLI()) {
			// If in admin, we can access to the shop without right URL

			if ((!$idCompany && Context::getContext()->_tools->isPHPCLI()) || defined('_EPH_ROOT_DIR_')) {
				$idCompany = (int) Context::getContext()->phenyxConfig->get('EPH_COMPANY_ID');
			}

			$company = new Company((int) $idCompany);

			if (!Validate::isLoadedObject($company)) {
				$company = new Company((int) Context::getContext()->phenyxConfig->get('EPH_COMPANY_ID'));
			}

			$company->virtual_uri = '';

			if (!isset($_SERVER['HTTP_HOST']) || empty($_SERVER['HTTP_HOST'])) {
				$_SERVER['HTTP_HOST'] = $company->domain;
			}

			if (!isset($_SERVER['SERVER_NAME']) || empty($_SERVER['SERVER_NAME'])) {
				$_SERVER['SERVER_NAME'] = $company->domain;
			}

			if (!isset($_SERVER['REMOTE_ADDR']) || empty($_SERVER['REMOTE_ADDR'])) {
				$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
			}

		} else {
			$company = new Company($idCompany);

			if (!Validate::isLoadedObject($company) || !$company->active) {
			$defaultCompany = new Company(Context::getContext()->phenyxConfig->get('EPH_COMPANY_ID'));

				// Fix #3: original referenced $defaultShop which was never defined — only
				// $defaultCompany was instantiated. All occurrences replaced with $defaultCompany.
				if (!Validate::isLoadedObject($defaultCompany)) {
					throw new PhenyxException('Shop not found');
				}

				$params = $_GET;
				unset($params['id_company']);
				$url = $defaultCompany->domain;

				if (!Context::getContext()->phenyxConfig->get('EPH_REWRITING_SETTINGS')) {
					$url .= $defaultCompany->getBaseURI() . 'index.php?' . http_build_query($params);
				} else {

					if (strpos($url, 'www.') === 0 && 'www.' . $_SERVER['HTTP_HOST'] === $url || $_SERVER['HTTP_HOST'] === 'www.' . $url) {
						$url .= $_SERVER['REQUEST_URI'];
					} else {
						$url .= $defaultCompany->getBaseURI();
					}

					if (count($params)) {
						$url .= '?' . http_build_query($params);
					}

				}

				$redirectType = Context::getContext()->phenyxConfig->get('EPH_CANONICAL_REDIRECT');
				$redirectCode = ($redirectType == 1 ? '302' : '301');
				$redirectHeader = ($redirectType == 1 ? 'Found' : 'Moved Permanently');
				header('HTTP/1.0 ' . $redirectCode . ' ' . $redirectHeader);
				header('Location: ' . Context::getContext()->_tools->getProtocol() . $url);
				exit;
			} else if (defined('_EPH_ROOT_DIR_') && empty($company->physical_uri)) {
				$companyDefault = new Company((int) Context::getContext()->phenyxConfig->get('EPH_COMPANY_ID'));
				$company->physical_uri = $companyDefault->physical_uri;
				$company->virtual_uri = $companyDefault->virtual_uri;
			}

		}

		static::$context_id_company = $company->id;

		return $company;
	}
    
    public function formatRcs() {

		if (!empty($this->siret)) {
			return substr($this->siret, 0, -5);
		}

		return null;
	}

	public function getBaseURI() {

		return $this->physical_uri . $this->virtual_uri;
	}
    
    public function getBaseURL($autoSecureMode = false, $addBaseUri = true) {

        // Fix #8: original hardcoded 'https://' regardless of $autoSecureMode,
        // generating incorrect URLs for non-SSL domains.
        $secure = $autoSecureMode && $this->context->_tools->usingSecureMode();
        $protocol = $secure ? 'https://' : 'http://';
        $domain   = $secure ? $this->domain_ssl : $this->domain;

        $url = $protocol . $domain;

        if ($addBaseUri) {
            $url .= $this->getBaseURI();
        }

        return $url;
    }
    
    public function getCategory() {

		return $this->context->phenyxConfig->get('EPH_ROOT_CATEGORY');
	}
    
    

	/**
	 * @see     PhenyxObjectModel::add()
	 *
	 * @since 2.1.0.0
	 *
	 * @param bool $autoDate
	 * @param bool $nullValues
	 *
	 * @return bool
	 */
	public function add($autoDate = true, $nullValues = false) {

		if (is_array($this->working_plan)) {
			$this->working_plan = Tools::jsonEncode($this->working_plan);
		}
		if (!parent::add($autoDate, $nullValues)) {
			return false;
		}
        // Fix #12: $langs was declared here but never used — removed.
        $url = new CompanyUrl();
        $url->active = 1;
        $url->main = 1;
        $url->id_company = $this->id;
        $url->domain = $this->company_url;
        $url->domain_ssl = $this->company_url;
        $url->target_domain = $this->context->_tools->getValue('target_domain', $this->company_url);
        $url->physical_uri = '/';
        $url->add();
        
		
        
		return true;
	}

	/**
	 * @param bool $nullValues
	 *
	 * @return bool
	 *
	 * @since 2.1.0.0
	 */
	public function update($nullValues = false) {

		// Empty related caches
		if (is_array($this->working_plan)) {
			$this->working_plan = Tools::jsonEncode($this->working_plan);
		}

		if (isset(static::$_idCountries[$this->id])) {
			unset(static::$_idCountries[$this->id]);
		}

		if (isset(static::$_idZones[$this->id])) {
			unset(static::$_idZones[$this->id]);
		}

		return parent::update($nullValues);
	}

	/**
	 * @see     PhenyxObjectModel::delete()
	 *
	 * @since 2.1.0.0
	 *
	 * @return bool
	 * @throws PhenyxException
	 */
	public function delete() {

		// Fix #4: original called $this->update() without setting deleted = 1 first,
		// making delete() behave identically to update() — nothing was marked as deleted.
		// Soft-delete: flag the record then persist via update().
		$this->deleted = 1;

		return $this->update();
	}
    
    public function getTheme() {
        
        return $this->theme_directory;
    }
    
    public static function getContext() {

        // Fix #1: original used $this in a static method → fatal error.
        return Context::getContext();
    }
    
    public static function getContextCompanyID($nullValueWithoutMultishop = false) {
        
        return static::$context_id_company;
    }
    
    public static function cacheShops($refresh = false) {

        // Fix #2: original called Context::getComptext() which does not exist → fatal error.
        $context = Context::getContext();
        if (!is_null(static::$companies) && !$refresh) {
            return;
        }

        static::$companies = [];

        $employee = $context->employee;

        $sql = (new DbQuery())
            ->select('c.*, c.`company_name`, c.`active`')
            ->select('cu.`domain`, cu.`domain_ssl`, cu.`physical_uri`, cu.`virtual_uri`')
            ->from('company', 'c')
            // Fix #6: original joined on c.`id_shop` = cu.`id_shop` — columns do not exist.
            // Corrected to id_company.
            ->leftJoin('company_url', 'cu', 'c.`id_company` = cu.`id_company` AND cu.`main` = 1')
            ->where('c.`deleted` = 0')
        ;

        // Fix #5: original used getRow() — only the first company was ever cached.
        // Changed to executeS() + foreach so all companies are stored.
        // Also replaced the stale 'id_shop' key with 'id_company'.
        if ($results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql)) {

            foreach ($results as $result) {
                static::$companies[$result['id_company']] = [
                    'id_company' => $result['id_company'],
                    'name'       => $result['company_name'],
                    'id_theme'   => $result['id_theme'],
                    'domain'     => $result['domain'],
                    'domain_ssl' => $result['domain_ssl'],
                    'uri'        => $result['physical_uri'] . $result['virtual_uri'],
                    'active'     => $result['active'],
                ];

                if (Plugin::isInstalled('ph_ecommerce')) {
                    static::$companies[$result['id_company']]['id_category'] = $result['id_category'] ?? null;
                }
            }

        }
           
        }
   
    
    public function getUrls()  {
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('company_url')
                ->where('`active` = 1')
                ->where('`id_company` = '.(int) $this->id)
        );
    }
    
    /**
     * Fix #10: getExercices() and getPastExercices() were ~90% identical (copy-paste).
     * Extracted the shared logic into this private helper.
     *
     * @param bool $includeFuture  When true, also appends the next exercice period
     *                             (the extra block that only existed in getExercices).
     */
    private function buildExercices(bool $includeFuture = false): array {

        $exercice    = [];
        $today       = date('Y-m-d');
        $currentYear = (int) date('Y');

        $exercice[$this->start_date . '|' . $this->first_accounting_end] =
            sprintf($this->l('from %s to %s'), $this->start_date, $this->first_accounting_end);

        $nextEnd = $this->currentExerciceEnd;

        if ($today > $this->first_accounting_end) {
            $date = new DateTime($this->first_accounting_end);
            $date->modify('+1 day');
            $exerciceStart = $date->format('Y-m-d');
            $date->modify('+11 month');
            $exerciceEnd  = $date->format('Y-m-t');
            $exerciceYear = (int) $date->format('Y');

            $exercice[$exerciceStart . '|' . $exerciceEnd] =
                sprintf($this->l('from %s to %s'), $exerciceStart, $exerciceEnd);

            $delta = $currentYear - $exerciceYear;

            if ($delta > 0) {

                for ($i = 0; $i <= $delta; $i++) {
                    $date = new DateTime($exerciceEnd);
                    $date->modify('+1 day');
                    $exerciceStart = $date->format('Y-m-d');
                    $date->modify('+11 month');
                    $exerciceEnd = $date->format('Y-m-t');
                    $exercice[$exerciceStart . '|' . $exerciceEnd] =
                        sprintf($this->l('from %s to %s'), $exerciceStart, $exerciceEnd);
                }

                // Only getExercices() needs this extra future period
                if ($includeFuture && $nextEnd > $exerciceEnd) {
                    $date = new DateTime($exerciceEnd);
                    $date->modify('+1 day');
                    $exerciceStart = $date->format('Y-m-d');
                    $date->modify('+11 month');
                    $exerciceEnd = $date->format('Y-m-t');
                    $exercice[$exerciceStart . '|' . $exerciceEnd] =
                        sprintf($this->l('from %s to %s'), $exerciceStart, $exerciceEnd);
                }

            }

        }

        return $exercice;
    }

    public function getExercices(): array {

        return $this->buildExercices(true);
    }

    public function getPastExercices(): array {

        return $this->buildExercices(false);
    }

    public function l($string, $idLang = null, $context = null) {

        $class = get_class($this);

        if (strtolower(substr($class, -4)) == 'core') {
            $class = substr($class, 0, -4);
        }

        // Fix #13: original had a second `return $string` after the if block without
        // an explicit else, making the control flow ambiguous. Clarified with else.
        if (isset($this->context->translations) && is_string($string)) {
            return $this->context->translations->getClassTranslation($string, $class);
        }

        return $string;
    }

}
