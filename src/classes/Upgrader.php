<?php

/**
 * Class Upgrader
 *
 * Corrections :
 * - executeSqlRequest() → static (appelé statiquement dans dispatcher.php, PHP 9 ferait erreur fatale)
 * - installBackTab()    : generateTabs() sorti de la boucle → appelé une seule fois après
 * - installMeta/BackTab : helper privé applyFields() pour dédupliquer les 40 lignes dupliquées
 * - installMetas()      : résultat de installMeta() pris en compte (était ignoré)
 */

class Upgrader {

    public $context;
    public $phenyxTools;
    public $className;
    public $meta_pages;
    public static $instance;

    public function __construct() {

        $this->className = get_class($this);
        $this->context   = Context::getContext();

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();
        }

        if (!isset($this->context->_hook)) {
            $this->context->_hook = Hook::getInstance();
        }

        if (!isset($this->context->_tools)) {
            $this->context->_tools = PhenyxTool::getInstance();
        }

        $this->meta_pages  = Meta::getPages(true);
        $this->phenyxTools = new PhenyxTools();
    }

    public static function getInstance() {

        if (!static::$instance) {
            static::$instance = new Upgrader();
        }

        return static::$instance;
    }

    /**
     * CORRIGÉ : méthode rendue statique.
     * Elle était appelée statiquement dans dispatcher.php (Upgrader::executeSqlRequest())
     * mais déclarée comme méthode d'instance → deprecation notice PHP 8, erreur fatale PHP 9.
     * La méthode n'utilise pas $this donc le passage en static est sans impact.
     */
    public static function executeSqlRequest(string $query, string $method) {

        switch ($method) {

            case 'execute':
                return Db::getInstance()->execute($query);

            case 'executeS':
                return Db::getInstance()->executeS($query);

            case 'getValue':
                return Db::getInstance()->getValue($query);

            case 'getRow':
                return Db::getInstance()->getRow($query);

            default:
                PhenyxLogger::addLog(
                    'Upgrader::executeSqlRequest — méthode inconnue : ' . $method,
                    2, null, 'Upgrader'
                );
                return false;
        }
    }

    public function instalTab(
        $class_name, $name, $function = true, $plugin = null,
        $idParent = null, $parentName = null, $position = null,
        $openFunction = null, $divider = 0
    ) {

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

        $tab              = $idTab ? new BackTab($idTab) : new BackTab();
        $tab->plugin      = $plugin;
        $tab->id_parent   = $idParent;
        $tab->class_name  = $class_name;
        $tab->has_divider = $divider;
        $tab->active      = 1;
        $tab->name        = [];

        if ($function) {
            $tab->function = !is_null($openFunction)
                ? $openFunction
                : 'openAjaxController(\'' . $class_name . '\')';
        }

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $translator->getGoogleTranslation($name, $lang['iso_code']);
        }

        if ($idTab) {
            $tab->update(true, false, $position);
            return $this->deployMeta(strtolower($class_name), $name, 'admin');
        } else {
            $tab->add(true, false, true, $position);
            return $this->deployPluginMeta(strtolower($class_name), $name, 'admin');
        }
    }

    public function deployMeta($page, $name, $type = 'front') {

        $idMeta = Meta::getIdMetaByPage($page);

        if ($idMeta) {
            return true;
        }

        $translator = Language::getInstance();
        $meta       = new Meta();
        $meta->controller = $type;
        $meta->page       = $page;
        $meta->plugin     = $this->name ?? null;

        foreach (Language::getLanguages(true) as $lang) {
            $meta->title[$lang['id_lang']]       = $translator->getGoogleTranslation($name, $lang['iso_code']);
            $meta->url_rewrite[$lang['id_lang']] = Tools::str2url($meta->title[$lang['id_lang']]);
        }

        return $meta->add();
    }

    // =========================================================================
    // HELPER PRIVÉ — évite la duplication du bloc d'affectation des champs
    // =========================================================================

    /**
     * Applique les champs d'un tableau associatif sur un objet PhenyxObjectModel.
     * Gère les champs scalaires et les champs multilingues (tableaux iso_code → valeur).
     *
     * @param PhenyxObjectModel $object     L'objet à hydrate
     * @param array             $data       Les données source
     * @param array             $skipKeys   Clés à ignorer (ex : ['id_meta', 'id'])
     */
    private function applyFields(object $object, array $data, array $skipKeys = []): void {

        foreach ($data as $key => $value) {

            if (in_array($key, $skipKeys, true)) {
                continue;
            }

            if (!property_exists($object, $key)) {
                continue;
            }

            if (is_array($value)) {
                foreach (Language::getLanguages(true) as $lang) {

                    if (isset($value[$lang['iso_code']])) {
                        $object->{$key}[$lang['id_lang']] = $value[$lang['iso_code']];
                    }
                }
            } else {
                $object->{$key} = $value;
            }
        }
    }

    // =========================================================================
    // INSTALLATION DES METAS
    // =========================================================================

    public function installMeta($meta): bool {

        $meta       = Tools::jsonDecode(Tools::jsonEncode($meta), true);
        $metaplugin = null;

        if (array_key_exists($meta['controller'], $this->meta_pages)) {

            foreach ($this->meta_pages[$meta['controller']]['plugin'] as $plugin => $page) {

                if ($page === $meta['page']) {

                    if (!Plugin::isInstalled($plugin)) {
                        return true; // Le plugin n'est pas installé, on skip
                    }

                    $metaplugin = $plugin;
                }
            }
        }

        $exist = Meta::getIdMetaByPage($meta['page']);

        $newObject = $exist ? new Meta($exist) : new Meta();
        $this->applyFields($newObject, $meta, ['id_meta', 'id']);
        $newObject->plugin = $metaplugin;

        return $exist ? (bool) $newObject->update() : (bool) $newObject->add();
    }

    /**
     * CORRIGÉ : le résultat de installMeta() est maintenant pris en compte.
     * L'ancienne version ignorait les retours et renvoyait toujours true.
     */
    public function installMetas($metas): bool {

        $result = true;
        $metas  = Tools::jsonDecode(Tools::jsonEncode($metas), true);

        foreach ($metas as $meta) {
            $result = $result && $this->installMeta($meta);
        }

        return $result;
    }

    // =========================================================================
    // INSTALLATION DES BACK TABS
    // =========================================================================

    /**
     * CORRIGÉ : generateTabs() sorti de installBackTab() et appelé une seule fois ici.
     * L'ancienne version appelait generateTabs() à chaque itération (N fois pour N tabs)
     * puis encore une fois après la boucle — soit N+1 appels inutiles.
     */
    public function installBackTabs($backtabs): bool {

        $result   = true;
        $backtabs = Tools::jsonDecode(Tools::jsonEncode($backtabs), true);

        foreach ($backtabs as &$backtab) {
            $result = $result && $this->installBackTab($backtab, false);
        }

        // Une seule régénération des tabs après toutes les installations
        $this->context->_tools->generateTabs(false);

        return $result;
    }

    /**
     * @param bool $regenerateTabs Si false, ne régénère pas les tabs (délégué à installBackTabs)
     */
    public function installBackTab($backtab, bool $regenerateTabs = true): bool {

        $backtab = Tools::jsonDecode(Tools::jsonEncode($backtab), true);
        $exist   = BackTab::getIdFromFuncAndClassName($backtab['className'], $backtab['function']);

        if (!$exist) {

            if (!empty($backtab['plugin']) && !Plugin::isInstalled($backtab['plugin'])) {
                return true; // Plugin non installé, on skip
            }

            $newObject = new BackTab();
            $this->applyFields($newObject, $backtab, ['id_back_tab', 'id_parent', 'id']);
            $newObject->id_parent = BackTab::getIdBackTabByClass($backtab['parent_class']);
            $result = (bool) $newObject->add();

        } else {

            $newObject = new BackTab($exist);
            $this->applyFields($newObject, $backtab, ['id_back_tab', 'id_parent', 'id']);
            $newObject->id_parent = BackTab::getIdBackTabByClass($backtab['parent_class']);

            if (!empty($backtab['plugin']) && !Plugin::isInstalled($backtab['plugin'])) {
                $newObject->plugin = null;
            }

            $result = (bool) $newObject->update();
        }

        if ($regenerateTabs) {
            $this->context->_tools->generateTabs(false);
        }

        return $result;
    }
}
