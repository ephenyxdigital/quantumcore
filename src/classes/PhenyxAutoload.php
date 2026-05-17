<?php

namespace EphenyxDigital\QuantumCore;

use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


/**
 * Class PhenyxAutoload
 *
 * @since 1.9.1.0
 */
class PhenyxAutoload {

    // @codingStandardsIgnoreStart
    /**
     * File where classes index is stored
     */
    const INDEX_FILE = 'app/cache/class_index.php';
	
	


    /**
     * @var PhenyxAutoload
     */
    protected static $instance;
	
    // Fix #11: _include_override_path was public — it is an internal configuration
    // flag and should not be accessible from outside. Changed to protected.
    protected $_include_override_path = true;
	
    protected static $class_aliases = [
        'Collection' => 'PhenyxCollection',
        'Autoload'   => 'PhenyxAutoload',
        'Backup'     => 'PhenyxBackup',
        'Logger'     => 'PhenyxLogger',
    ];
    /**
     * @var array array('classname' => 'path/to/override', 'classnamecore' => 'path/to/class/core')
     */
    public $index = [];

   
    /**
     * @var string Root directory
     */
    protected $root_dir;
    // @codingStandardsIgnoreEnd

    /**
     * PhenyxAutoload constructor.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function __construct() {

        
		$this->root_dir = _EPH_CORE_DIR_ . '/';
        $file = $this->normalizeDirectory(_EPH_ROOT_DIR_) . PhenyxAutoload::INDEX_FILE;

        if (@filemtime($file) && is_readable($file)) {
            $this->index = include $file;
        } else {            
            $this->generateIndex();
        }

    }

    /**
     * @param $directory
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function normalizeDirectory($directory) {

        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Generate classes index
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function generateIndex() {

        $classes = array_merge(
            $this->getClassesFromDir(_EPH_EPHENYX_DIR_.'classes/'),			
            $this->getClassesFromDir(_EPH_EPHENYX_DIR_.'controllers/'),	
            $this->getClassesFromPlugins(defined('_EPH_HOST_MODE_'))
        );
		

        if ($this->_include_override_path) {
            $classes = array_merge(
                $classes,
                $this->getClassesFromDir('includes/override/classes/', defined('_EPH_HOST_MODE_')),
                $this->getClassesFromDir('includes/override/controllers/', defined('_EPH_HOST_MODE_'))
            );
        }
        
        $classes = array_merge(
            $classes,
            $this->getClassesFromDir('includes/specific_classes/')
        );
		$classes = array_merge(
            $classes,
            $this->getClassesFromDir('includes/specific_controllers/')
        );
        
        

        ksort($classes);
        $content = '<?php return ' . var_export($classes, true) . '; ?>';

        // Write classes index on disc to cache it
        $filename = $this->normalizeDirectory(_EPH_ROOT_DIR_) . PhenyxAutoload::INDEX_FILE;
        $filenameTmp = tempnam(dirname($filename), basename($filename . '.'));

        if ($filenameTmp !== false && file_put_contents($filenameTmp, $content) !== false) {

            if (!@rename($filenameTmp, $filename)) {
                unlink($filenameTmp);
            } else {
                @chmod($filename, 0666);

                if (function_exists('opcache_invalidate')) {
                    // Fix #1: original called opcache_invalidate($filenameTmp) but
                    // after a successful rename() $filenameTmp no longer exists —
                    // it is now $filename that holds the new index. Corrected.
                    opcache_invalidate($filename);
                }

            }

        }

        // $filename_tmp couldn't be written. $filename should be there anyway (even if outdated), no need to die.
        else {
            Tools::error_log('Cannot write temporary file ' . $filenameTmp);
        }

        $this->index = $classes;
    }
	
    protected function getClassesFromDir($path, $hostMode = false) {

        $classes = [];
        $rootDir = $hostMode ? $this->normalizeDirectory(_EPH_ROOT_DIR_) : $this->root_dir;

        // Fix #7: scandir() returns false if the directory does not exist.
        // Without this guard the foreach would throw a Warning.
        $entries = @scandir($rootDir . $path);

        if ($entries === false) {
            return $classes;
        }

        foreach ($entries as $file) {

            // Fix #2: accessing $file[0] on an empty string triggers a deprecation
            // in PHP 8.1+. Explicit empty-string guard added (mirrors Performer fix).
            if ($file === '' || $file[0] === '.') {
                continue;
            }

            if (is_dir($rootDir . $path . $file)) {
                $classes = array_merge($classes, $this->getClassesFromDir($path . $file . '/', $hostMode));
            } elseif (substr($file, -4) === '.php') {
                $content = file_get_contents($rootDir . $path . $file);

                $namespacePattern = '[\\a-z0-9_]*[\\]';
                $pattern = '#\W((abstract\s+)?class|interface)\s+(?P<classname>' . basename($file, '.php') . '(?:Core)?)'
                    . '(?:\s+extends\s+' . $namespacePattern . '[a-z][a-z0-9_]*)?(?:\s+implements\s+' . $namespacePattern . '[a-z][\\a-z0-9_]*(?:\s*,\s*' . $namespacePattern . '[a-z][\\a-z0-9_]*)*)?\s*\{#i';

                if (preg_match($pattern, $content, $m)) {
                    $classes[$m['classname']] = [
                        'path'     => $path . $file,
                        'type'     => trim($m[1]),
                        'override' => $hostMode,
                    ];

                    if (substr($m['classname'], -4) === 'Core') {
                        $nonCoreName = substr($m['classname'], 0, -4);

                        // Fix #13 (twin of scanPluginDir): never let a Core
                        // placeholder write trump an existing real override.
                        if (!isset($classes[$nonCoreName]) || empty($classes[$nonCoreName]['path'])) {
                            $classes[$nonCoreName] = [
                                'path'     => '',
                                'type'     => $classes[$m['classname']]['type'],
                                'override' => $hostMode,
                            ];
                        }
                    }

                }

            }

        }

        return $classes;
    }
    
    public function getClassesFromPlugins($hostMode = false) {

        $rootDir = $hostMode ? $this->normalizeDirectory(_EPH_ROOT_DIR_) : $this->root_dir;
        $classes = [];

        // Fix #5 (revised at user's request): only installed plugins should have
        // their classes indexed — uninstalled plugins on disk can cause conflicts.
        // We cannot call Plugin::getPluginsInstalled() here because Plugin itself
        // must be loaded by this autoloader first (circular dependency).
        // Solution: query the `plugin` table directly via a raw PDO connection,
        // with no dependency on any framework class.
        $installedPlugins = $this->getInstalledPluginNames();

        if (empty($installedPlugins)) {
            return $classes;
        }

        $pluginDirs = [
            $rootDir . 'includes/plugins/',
            $rootDir . 'includes/specific_plugins/',
        ];

        foreach ($pluginDirs as $baseDir) {

            if (!is_dir($baseDir)) {
                continue;
            }

            foreach ($installedPlugins as $pluginName) {

                $pluginDir = $baseDir . $pluginName;

                if (!is_dir($pluginDir)) {
                    continue;
                }

                $classes = array_merge(
                    $classes,
                    $this->scanPluginDir($pluginDir . '/', $rootDir, $hostMode)
                );
            }

        }

        return $classes;
    }

    /**
     * Return the list of installed plugin names directly from the database,
     * bypassing all framework classes to avoid circular autoload dependencies.
     *
     * Uses the same credentials that config.inc.php already loaded into the
     * _DB_* constants, connecting via PDO with a minimal query.
     *
     * @return string[]  Plugin names (e.g. ['ph_manager', 'ph_ecommerce'])
     */
    protected function getInstalledPluginNames(): array {

        // Constants may not be defined yet on very first install — fail gracefully.
        if (!defined('_DB_SERVER_') || !defined('_DB_NAME_') || !defined('_DB_USER_') || !defined('_DB_PASSWD_')) {
            return [];
        }

        try {
            $prefix = defined('_DB_PREFIX_') ? _DB_PREFIX_ : 'ph_';
            $dsn    = 'mysql:host=' . _DB_SERVER_ . ';dbname=' . _DB_NAME_ . ';charset=utf8mb4';
            $pdo    = new PDO($dsn, _DB_USER_, _DB_PASSWD_, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT            => 5,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_COLUMN,
            ]);

            // Fix #12: the original query had no ORDER BY, so the row order
            // returned by MySQL was effectively unspecified (and could shift
            // between deployments or after table maintenance). That made the
            // override resolution in scanPluginDir() non-deterministic, since
            // "last scanned wins" for any name collision. Ordering by the
            // admin-managed `position` (ascending, with `id_plugin` as a
            // stable tiebreaker) makes the scan order reproducible.
            $stmt = $pdo->query(
                'SELECT `name` FROM `' . $prefix . 'plugin`'
                . ' WHERE `active` = 1'
                . ' ORDER BY `position` ASC, `id_plugin` ASC'
            );

            return $stmt ? $stmt->fetchAll() : [];

        } catch (\Exception $e) {
            // DB not yet available (e.g. first install) — return empty list.
            return [];
        }
    }

    /**
     * Scan a single plugin directory and extract class definitions from
     * classes/, controllers/admin/ and controllers/front/ subdirectories.
     *
     * Fix #3: the original loop variable $file (SplFileInfo object) was
     *   overwritten with str_replace() output mid-iteration, corrupting the
     *   iterator state for subsequent files.
     * Fix #4: the path 'specific_includes/plugins/' tested in file_exists()
     *   differed from the path used in the iterator ('includes/specific_plugins/').
     * Fix #8: when neither path matched, files were silently skipped.
     * Fix #9: extracted from getClassesFromPlugins() to avoid ~80% duplication.
     *
     * @param  string $pluginDir  Absolute path to the plugin root (with trailing /)
     * @param  string $rootDir    Absolute root dir (with trailing /)
     * @param  bool   $hostMode
     * @return array
     */
    protected function scanPluginDir(string $pluginDir, string $rootDir, bool $hostMode): array {

        $classes = [];

        if (!is_dir($pluginDir)) {
            return $classes;
        }

        $skipList = ['.', '..', 'index.php', '.htaccess', 'dwsync.xml', 'settings.inc.php'];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $namespacePattern = '[\\a-z0-9_]*[\\]';

        foreach ($iterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            $filePath = $fileInfo->getPathname();
            $fileName = $fileInfo->getFilename();

            if (in_array($fileName, $skipList, true)) {
                continue;
            }

            // Only index classes/ and controllers/admin|front/ — skip overrides
            $relativePath = str_replace($pluginDir, '', $filePath);

            if (strpos($relativePath, 'override/') !== false) {
                continue;
            }

            $inClassesDir      = strpos($relativePath, 'classes/') !== false;
            $inAdminController = strpos($relativePath, 'controllers/admin/') !== false;
            $inFrontController = strpos($relativePath, 'controllers/front/') !== false;

            if (!$inClassesDir && !$inAdminController && !$inFrontController) {
                continue;
            }

            if (substr($fileName, -4) !== '.php') {
                continue;
            }

            $content = @file_get_contents($filePath);

            if ($content === false) {
                continue;
            }

            $pattern = '#\W((abstract\s+)?class|interface)\s+(?P<classname>' . basename($fileName, '.php') . '(?:Core)?)'
                . '(?:\s+extends\s+' . $namespacePattern . '[a-z][a-z0-9_]*)?(?:\s+implements\s+' . $namespacePattern . '[a-z][\\a-z0-9_]*(?:\s*,\s*' . $namespacePattern . '[a-z][\\a-z0-9_]*)*)?\s*\{#i';

            if (!preg_match($pattern, $content, $m)) {
                continue;
            }

            // Fix #3: original did `$file = str_replace(...)` which overwrote the
            // SplFileInfo loop variable. Using a separate $classPath variable.
            $classPath = str_replace(_EPH_ROOT_DIR_, '', $filePath);

            $classes[$m['classname']] = [
                'path'     => $classPath,
                'type'     => trim($m[1]),
                'override' => $hostMode,
            ];

            if (substr($m['classname'], -4) === 'Core') {
                $nonCoreName = substr($m['classname'], 0, -4);

                // Fix #13: when plugin A declares FooCore (which also emits a
                // FrontLink-style placeholder `Foo => {path: ''}`) and plugin
                // B declares the real override `class Foo extends FooCore`,
                // the previous unconditional assignment let the placeholder
                // overwrite the real override if A happened to be scanned
                // after B. Guard the placeholder write so a real override
                // entry (one with a non-empty path) is never trumped.
                if (!isset($classes[$nonCoreName]) || empty($classes[$nonCoreName]['path'])) {
                    $classes[$nonCoreName] = [
                        'path'     => '',
                        'type'     => $classes[$m['classname']]['type'],
                        'override' => $hostMode,
                    ];
                }
            }

        }

        return $classes;
    }

    public static function getInstance() {

        if (!PhenyxAutoload::$instance) {
            PhenyxAutoload::$instance = new PhenyxAutoload();
        }

        return PhenyxAutoload::$instance;
    }

    public function load($className) {

        // Retrocompatibility

        if (isset(PhenyxAutoload::$class_aliases[$className]) && !interface_exists($className, false) && !class_exists($className, false)) {
            return eval('class ' . $className . ' extends ' . PhenyxAutoload::$class_aliases[$className] . ' {}');
        }

        // regenerate the class index if the requested file doesn't exists

        if ((isset($this->index[$className]) && $this->index[$className]['path'] && !is_file($this->root_dir . $this->index[$className]['path']))
            || (isset($this->index[$className . 'Core']) && $this->index[$className . 'Core']['path'] && !is_file($this->root_dir . $this->index[$className . 'Core']['path']))
        ) {
            $this->generateIndex();
        }

        // If $classname has not core suffix (E.g. Shop, Product)

        if (substr($className, -4) != 'Core') {
            $classDir = (isset($this->index[$className]['override'])
                && $this->index[$className]['override'] === true) ? $this->normalizeDirectory(_EPH_ROOT_DIR_) : $this->root_dir;

            // If requested class does not exist, load associated core class

            if (isset($this->index[$className]) && !$this->index[$className]['path']) {
                require_once $classDir . $this->index[$className . 'Core']['path'];

                if ($this->index[$className . 'Core']['type'] != 'interface') {
                    eval($this->index[$className . 'Core']['type'] . ' ' . $className . ' extends ' . $className . 'Core {}');
                }

            } else {
                // request a non Core Class load the associated Core class if exists

                if (isset($this->index[$className . 'Core'])) {
                    require_once $this->root_dir . $this->index[$className . 'Core']['path'];
                }

                if (isset($this->index[$className])) {
                    require_once $classDir . $this->index[$className]['path'];
                }

            }

        }

        // Call directly ProductCore, ShopCore class
        else
        if (isset($this->index[$className]['path']) && $this->index[$className]['path']) {
            require_once $this->root_dir . $this->index[$className]['path'];
        }

    }

    public function getClassPath($className) {

        return (isset($this->index[$className]) && isset($this->index[$className]['path'])) ? $this->index[$className]['path'] : null;
    }

}
