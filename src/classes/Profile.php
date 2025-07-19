<?php

/**
 * Class ProfileCore
 *
 * @since 1.9.1.0
 */
class Profile extends PhenyxObjectModel {

    const PERMISSION_VIEW = 'view';
    const PERMISSION_ADD = 'add';
    const PERMISSION_EDIT = 'edit';
    const PERMISSION_DELETE = 'delete';
    
    public $require_context = false;
    // @codingStandardsIgnoreStart
    protected static $_cache_accesses = [];

    protected static $_cache_employee_accesses = [];
    public $generated;
    /** @var string Name */
    public $name;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'profile',
        'primary'   => 'id_profile',
        'multilang' => true,
        'fields'    => [
            /* Lang fields */
            'generated'           => ['type' => self::TYPE_BOOL, 'lang' => true],
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
    ];

    /**
     * Get all available profiles
     *
     * @param $idLang
     *
     * @return array Profiles
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProfiles($idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('p.`id_profile`, `name`')
                ->from('profile', 'p')
                ->leftJoin('profile_lang', 'pl', 'p.`id_profile` = pl.`id_profile`')
                ->where('`id_lang` = ' . (int) $idLang)
                ->orderBy('`id_profile` ASC')
        );
    }

    /**
     * Get the current profile name
     *
     * @param int      $idProfile
     * @param int|null $idLang
     *
     * @return string Profile
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProfile($idProfile, $idLang = null) {

        if (!$idLang) {
            $idLang = Context::getContext()->phenyxConfig->get('EPH_LANG_DEFAULT');
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`name`')
                ->from('profile', 'p')
                ->leftJoin('profile_lang', 'pl', 'p.`id_profile` = pl.`id_profile`')
                ->where('p.`id_profile` = ' . (int) $idProfile)
                ->where('pl.`id_lang` = ' . (int) $idLang)
        );
    }

    /**
     * @param int $idProfile
     * @param int $idTab
     *
     * @return bool
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProfileAccess($idProfile, $idTab) {

        // getProfileAccesses is cached so there is no performance leak
        $idProfile = (int)$idProfile;
        $accesses = Profile::getProfileAccesses($idProfile);
        if (isset($accesses[$idTab]) && is_array($accesses[$idTab])) {
            return $accesses[$idTab];
        }

        $perm = static::formatPermissionValue($idProfile === _EPH_ADMIN_PROFILE_);
        return [
            'id_profile' => $idProfile,
            'id_tab'     => $idTab,
            'class_name' => '',
            'view'       => $perm,
            'add'        => $perm,
            'edit'       => $perm,
            'delete'     => $perm,
        ];
    }

    /**
     * @param int    $idProfile
     * @param string $type
     *
     * @return bool
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProfileAccesses($idProfile, $type = 'id_back_tab') {

        static::$_cache_employee_accesses = [];
		// @codingStandardsIgnoreStart
		if (!in_array($type, ['id_back_tab', 'class_name'])) {
            return false;
        }
		
        if (!isset(static::$_cache_employee_accesses[$idProfile])) {
            static::$_cache_employee_accesses[$idProfile] = [];
        }
		
        if (!isset(static::$_cache_employee_accesses[$idProfile][$type])) {
            static::$_cache_employee_accesses[$idProfile][$type] = [];
			
            if ($idProfile == _EPH_ADMIN_PROFILE_) {
                foreach (BackTab::getBackTabs(Context::getContext()->language->id) as $tab) {
                    static::$_cache_employee_accesses[$idProfile][$type][$tab['id_back_tab']] = [
                        'id_profile' => _EPH_ADMIN_PROFILE_,
                        'id_back_tab'  => $tab['id_back_tab'],
                        'view'       => '1',
                        'add'        => '1',
                        'edit'       => '1',
                        'delete'     => '1',
                    ];
                }

            } else {
                $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('*')
                        ->from('employee_access', 'a')
                        ->leftJoin('back_tab', 't', 't.`id_back_tab` = a.`id_back_tab`')
                        ->where('`id_profile` = ' . (int) $idProfile)
                );

                foreach ($result as $row) {
                    static::$_cache_employee_accesses[$idProfile][$type][$row[$type]] = $row;
                }

            }

        }

        return static::$_cache_employee_accesses[$idProfile][$type];
    }

    
	
	public static function getProfilePartnerAccesses(License $license, $idProfile, $type = 'id_back_tab') {

      
		
		$accesses = [];
        if ($idProfile == _EPH_ADMIN_PROFILE_) {

        	foreach (BackTab::getBackTabs(Context::getContext()->language->id) as $tab) {
            	$accesses[$idProfile][$type][$tab[$type]] = [
                	'id_profile' => _EPH_ADMIN_PROFILE_,
                    'id_back_tab'  => $tab['id_back_tab'],
                    'view'       => '1',
                    'add'        => '1',
                    'edit'       => '1',
                    'delete'     => '1',
                ];
            }

       } else {
			
			$query =  'SELECT *
			FROM `eph_employee_access` a
			LEFT JOIN `eph_back_tab` `t` ON t.`id_back_tab` = a.`id_back_tab`
			WHERE a.`id_profile` = '. (int) $idProfile;
			
			$result = $license->pushSqlRequest($query, 'executeS');
           	

            foreach ($result as $row) {
				
           		$accesses[$idProfile][$type][$row[$type]] = $row;
            }

        }
        return $accesses[$idProfile][$type];
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function add($autoDate = true, $nullValues = false) {

        if (parent::add($autoDate, true)) {
            $result = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'access (SELECT ' . (int) $this->id . ', id_back_tab, 0, 0, 0, 0 FROM ' . _DB_PREFIX_ . 'tab)');
            $result &= Db::getInstance()->execute(
                '
                INSERT INTO ' . _DB_PREFIX_ . 'plugin_access
                (`id_profile`, `id_plugin`, `configure`, `view`, `uninstall`)
                (SELECT ' . (int) $this->id . ', id_plugin, 0, 1, 0 FROM ' . _DB_PREFIX_ . 'plugin)
            '
            );

            return $result;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxDatabaseExceptionException
     */
    public function delete() {

        if (parent::delete()) {
            return (
                Db::getInstance()->delete('access', '`id_profile` = ' . (int) $this->id)
                && Db::getInstance()->delete('plugin_access', '`id_profile` = ' . (int) $this->id)
            );
        }

        return false;
    }
    
    public static function isValidPermission($permission)  {
       return $permission && is_string($permission) && in_array($permission, [
           Profile::PERMISSION_VIEW,
           Profile::PERMISSION_DELETE,
           Profile::PERMISSION_ADD,
           Profile::PERMISSION_EDIT,
       ]);
    }
    
    public static function formatPermissionValue($hasPermission) {
        return $hasPermission ? '1' : '0';
    }

}
