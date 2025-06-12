<?php

/**
 * Class IoPlugin
 *
 * @since 2.1.0.0
 *
 * @property $confirmUninstall
 */
class IoPlugin extends Plugin {

    public $warn;

    protected function getCacheId($name = null) {

        $cache_array = [];
        $cache_array[] = $name !== null ? $name : $this->name;

        if (Configuration::get('EPH_SSL_ENABLED')) {
            $cache_array[] = (int) Tools::usingSecureMode();
        }

        if (Group::isFeatureActive() && isset($this->context->user)) {
            $cache_array[] = (int) Group::getCurrent()->id;
            $cache_array[] = implode('_', User::getGroupsStatic($this->context->user->id));
        }

        if (Language::isMultiLanguageActivated()) {
            $cache_array[] = (int) $this->context->language->id;
        }

        if (Currency::isMultiCurrencyActivated()) {
            $cache_array[] = (int) $this->context->currency->id;
        }

        $cache_array[] = (int) $this->context->country->id;

        return implode('|', $cache_array);
    }

    public static function getPhenyxPluginsOnDisk($id_licence, $customer_plugins) {
        $customer_plugins = Tools::jsonDecode(Tools::jsonEncode($customer_plugins), true);

        $license = new License($id_licence);
        $context = Context::getContext();
        $link = new Link();
        $phenyxPlugins = [];
        $phenyxDepends = [];
        $pluginList = [];

        if (is_array($customer_plugins) && count($customer_plugins)) {

            foreach ($customer_plugins as $plugin => $value) {
                $phenyxPlugins[$plugin] = $value;
                $pluginList[] = $phenyxPlugins[$plugin];
                $phenyxDepends[$plugin] = $value['dependencies'];

            }

        }

        $pluginNameList = [];
        $pluginsNameToCursor = [];
        $errors = [];

        $pluginsDir = Plugin::getPluginsDirOnDisk();

        foreach ($pluginsDir as $plugin) {

            if (array_key_exists($plugin, $phenyxPlugins)) {
                continue;
            }

            if (!class_exists($plugin, false)) {

                if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php')) {
                    $filePath = _EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php';
                    $file = trim(file_get_contents(_EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php'));
                } else

                if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php')) {
                    $filePath = _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php';
                    $file = trim(file_get_contents(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php'));
                }

                if (substr($file, 0, 5) == '<?php') {
                    $file = substr($file, 5);
                }

                if (substr($file, -2) == '?>') {
                    $file = substr($file, 0, -2);
                }

                $file = preg_replace('/\n[\s\t]*?use\s.*?;/', '', $file);

                if (eval('if (false){   ' . $file . "\n" . ' }') !== false) {

                    if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php')) {
                        require_once _EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php';
                        $image = 'includes/plugins/' . $plugin . '/logo.png';
                    } else

                    if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php')) {
                        require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php';
                        $image = 'includes/specific_plugins/' . $plugin . '/logo.png';
                    }

                } else {
                    $errors[] = sprintf(Tools::displayError('%1$s (parse error in %2$s)'), $plugin, substr($filePath, strlen(_EPH_ROOT_DIR_)));
                }

            }

            $item = [];
            $tmpPlugin = Adapter_ServiceLocator::get($plugin);

            $item = [
                'id'                     => is_null($tmpPlugin->id) ? 0 : $tmpPlugin->id,
                'warning'                => $tmpPlugin->warning,
                'name'                   => $tmpPlugin->name,
                'version'                => $tmpPlugin->version,
                'tab'                    => $tmpPlugin->tab,
                'displayName'            => $tmpPlugin->displayName,
                'dependencies'           => $tmpPlugin->dependencies,
                'description'            => !is_null($tmpPlugin->description) ? stripslashes($tmpPlugin->description) : '',
                'author'                 => $tmpPlugin->author,
                'author_uri'             => (isset($tmpPlugin->author_uri) && $tmpPlugin->author_uri) ? $tmpPlugin->author_uri : false,
                'limited_countries'      => $tmpPlugin->limited_countries,
                'parent_class'           => get_parent_class($plugin),
                'is_configurable'        => $tmpPlugin->is_configurable = method_exists($tmpPlugin, 'getContent') ? 1 : 0,
                'config_controller'      => $tmpPlugin->config_controller,
                'active'                 => $tmpPlugin->active,
                'trusted'                => true,
                'currencies'             => isset($tmpPlugin->currencies) ? $tmpPlugin->currencies : null,
                'currencies_mode'        => isset($tmpPlugin->currencies_mode) ? $tmpPlugin->currencies_mode : null,
                'confirmUninstall'       => isset($tmpPlugin->confirmUninstall) ? html_entity_decode($tmpPlugin->confirmUninstall) : null,
                'description_full'       => isset($tmpPlugin->description_full) ? stripslashes($tmpPlugin->description_full) : null,
                'additional_description' => isset($tmpPlugin->additional_description) ? stripslashes($tmpPlugin->additional_description) : null,
                'compatibility'          => isset($tmpPlugin->compatibility) ? (array) $tmpPlugin->compatibility : null,
                'nb_rates'               => isset($tmpPlugin->nb_rates) ? (array) $tmpPlugin->nb_rates : null,
                'avg_rate'               => isset($tmpPlugin->avg_rate) ? (array) $tmpPlugin->avg_rate : null,
                'badges'                 => isset($tmpPlugin->badges) ? (array) $tmpPlugin->badges : null,
                'url'                    => isset($tmpPlugin->url) ? $tmpPlugin->url : null,
                'removable'              => $tmpPlugin->removable,
                'dependencies'           => $tmpPlugin->dependencies,
                'image_link'             => $link->getBaseFrontLink() . $image,
                'is_ondisk'              => false,
                'has_reset'              => method_exists($tmpPlugin, 'reset') ? true : false,
            ];

            $pluginList[] = $item;

            unset($tmpPlugin);

        }

        return $pluginList;
    }

    public static function generatePluginZip($plugin) {

        $file = fopen("testgeneratePluginZip.txt", "w");
        fwrite($file, $plugin . PHP_EOL);

        if (file_exists(_EPH_ROOT_DIR_ . '/plugins/' . $plugin . '.zip')) {
            unlink(_EPH_ROOT_DIR_ . '/plugins/' . $plugin . '.zip');
        }

        $rootPath = _EPH_PLUGIN_DIR_ . $plugin;
        $zip = new ZipArchive();
        $zip->open(_EPH_ROOT_DIR_ . '/plugins/' . $plugin . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {

            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }

        }

        $zip->close();

        return '/plugins/' . $plugin . '.zip';
    }

}
