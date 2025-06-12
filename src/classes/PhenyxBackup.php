<?php

/**
 * Class PhenyxBackup
 *
 * @since 1.9.1.0
 */
class PhenyxBackup {

    
    public static $backupDir = '/app/backup';
    public $context;
    public $id;
    public $error;
    public $customBackupDir = null;
    public $ephBackupAll = true;
    public $ephBackupDropTable = true;
    
    public function __construct($filename = null) {

        if ($filename) {
            $this->id = $this->getRealBackupPath($filename);
        }
        $this->context = Context::getContext();
        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();
            
        }

        $ephBackupAll = $this->context->phenyxConfig->get('EPH_BACKUP_ALL');
        $ephBackupDropTable = $this->context->phenyxConfig->get('EPH_BACKUP_DROP_TABLE');
        $this->ephBackupAll = $ephBackupAll !== false ? $ephBackupAll : true;
        $this->ephBackupDropTable = $ephBackupDropTable !== false ? $ephBackupDropTable : true;
    }
    
    public function getRealBackupPath($filename = null) {

        $backupDir = static::getBackupPath($filename);

        if (!empty($this->customBackupDir)) {
            $backupDir = str_replace(
                _EPH_ROOT_DIR_ . static::$backupDir,
                _EPH_ROOT_DIR_ . $this->customBackupDir,
                $backupDir
            );

            if (strrpos($backupDir, DIRECTORY_SEPARATOR)) {
                $backupDir .= DIRECTORY_SEPARATOR;
            }

        }

        return $backupDir;
    }

    public static function getBackupPath($filename = '') {

        $backupdir = realpath(_EPH_ROOT_DIR_ . static::$backupDir);

        if ($backupdir === false) {
            die(Tools::displayError('"Backup" directory does not exist.'));
        }

        // Check the realpath so we can validate the backup file is under the backup directory

        if (!empty($filename)) {
            $backupfile = realpath($backupdir . DIRECTORY_SEPARATOR . $filename);
        } else {
            $backupfile = $backupdir . DIRECTORY_SEPARATOR;
        }

        if ($backupfile === false || strncmp($backupdir, $backupfile, strlen($backupdir)) != 0) {
            die(Tools::displayError());
        }

        return $backupfile;
    }

    public static function backupExist($filename) {

        $backupdir = realpath(_EPH_ROOT_DIR_ . static::$backupDir);

        if ($backupdir === false) {
            die(Tools::displayError('"Backup" directory does not exist.'));
        }

        return @filemtime($backupdir . DIRECTORY_SEPARATOR . $filename);
    }

    public function setCustomBackupPath($dir) {

        $customDir = DIRECTORY_SEPARATOR . trim($dir, '/') . DIRECTORY_SEPARATOR;

        if (is_dir(_EPH_ROOT_DIR_ . $customDir)) {
            $this->customBackupDir = $customDir;
        } else {
            return false;
        }

        return true;
    }

    public function getBackupURL() {

        return __EPH_BASE_URI__ . basename(_EPH_ROOT_DIR_) . 'app/backup.php?filename=' . basename($this->id);
    }

    public function deleteSelection($list) {

        foreach ($list as $file) {
            $backup = new self($file);

            if (!$backup->delete()) {
                $this->error = $backup->error;

                return false;
            }

        }

        return true;
    }

    public function add() {

        if (!$this->ephBackupAll) {
            $ignoreInsertTable = [
                _DB_PREFIX_ . 'connections',
                _DB_PREFIX_ . 'connections_page',
                _DB_PREFIX_ . 'connections_source',
                _DB_PREFIX_ . 'guest',
                _DB_PREFIX_ . 'statssearch',
            ];
        } else {
            $ignoreInsertTable = [];
        }
        
        $extraIgnoreTables = Hook::getInstance()->exec('getExtraIgonesBackupTables', [], null, true);
        if (is_array($extraIgnoreTables)) {

            foreach ($extraIgnoreTables as $plugin => $ignoreTable) {
                $ignoreInsertTable[] = $ignoreTable;
            }

        }

        // Generate some random number, to make it extra hard to guess backup file names
        $rand = dechex(mt_rand(0, min(0xffffffff, mt_getrandmax())));
        $date = time();
        $backupfile = $this->getRealBackupPath() . $date . '-' . $rand . '.sql';

        // Figure out what compression is available and open the file

        if (function_exists('bzopen')) {
            $backupfile .= '.bz2';
            $fp = @bzopen($backupfile, 'w');
        } else
        if (function_exists('gzopen')) {
            $backupfile .= '.gz';
            $fp = @gzopen($backupfile, 'w');
        } else {
            $fp = @fopen($backupfile, 'w');
        }

        if ($fp === false) {
            echo Tools::displayError('Unable to create backup file') . ' "' . addslashes($backupfile) . '"';

            return false;
        }

        $this->id = realpath($backupfile);

        // Find all tables
        $tables = Db::getInstance()->executeS('SHOW TABLES');
        $found = 0;

        foreach ($tables as $table) {
            $table = current($table);

            // Skip tables which do not start with _DB_PREFIX_

            if (strlen($table) < strlen(_DB_PREFIX_) || strncmp($table, _DB_PREFIX_, strlen(_DB_PREFIX_)) != 0) {
                continue;
            }

            // Export the table schema
            $schema = Db::getInstance()->executeS('SHOW CREATE TABLE `' . $table . '`');

            if (count($schema) != 1 || !isset($schema[0]['Table']) || !isset($schema[0]['Create Table'])) {
                fclose($fp);
                $this->delete();
                echo Tools::displayError('An error occurred while backing up. Unable to obtain the schema of') . ' "' . $table;

                return false;
            }

            if ($this->ephBackupDropTable) {
                fwrite($fp, 'DROP TABLE IF EXISTS `' . $schema[0]['Table'] . '`;' . "\n");
            }

            fwrite($fp, $schema[0]['Create Table'] . ";\n\n");

            if (!in_array($schema[0]['Table'], $ignoreInsertTable)) {
                $data = Db::getInstance()->query('SELECT * FROM `' . $schema[0]['Table'] . '`');
                $sizeof = DB::getInstance()->NumRows();
                $lines = explode("\n", $schema[0]['Create Table']);

                if ($data && $sizeof > 0) {
                    // Export the table data
                    fwrite($fp, 'INSERT INTO `' . $schema[0]['Table'] . "` VALUES\n");
                    $i = 1;

                    while ($row = DB::getInstance()->nextRow($data)) {
                        $s = '(';

                        foreach ($row as $field => $value) {
                            $tmp = "'" . pSQL($value, true) . "',";

                            if ($tmp != "'',") {
                                $s .= $tmp;
                            } else {

                                foreach ($lines as $line) {

                                    if (strpos($line, '`' . $field . '`') !== false) {

                                        if (preg_match('/(.*NOT NULL.*)/Ui', $line)) {
                                            $s .= "'',";
                                        } else {
                                            $s .= 'NULL,';
                                        }

                                        break;
                                    }

                                }

                            }

                        }

                        $s = rtrim($s, ',');

                        if ($i % 200 == 0 && $i < $sizeof) {
                            $s .= ");\nINSERT INTO `" . $schema[0]['Table'] . "` VALUES\n";
                        } else
                        if ($i < $sizeof) {
                            $s .= "),\n";
                        } else {
                            $s .= ");\n";
                        }

                        fwrite($fp, $s);
                        ++$i;
                    }

                }

            }

            $found++;
        }

        fclose($fp);

        if ($found == 0) {
            $this->delete();
            echo Tools::displayError('No valid tables were found to backup.');

            return false;
        }

        return true;
    }
    
    public function generatePhenyxData() {

       $insertTable = [
           _DB_PREFIX_ . 'back_tab',
           _DB_PREFIX_ . 'back_tab_lang',
           _DB_PREFIX_ . 'meta',
           _DB_PREFIX_ . 'meta_lang',
       ];

        // Generate some random number, to make it extra hard to guess backup file names
        $rand = dechex(mt_rand(0, min(0xffffffff, mt_getrandmax())));
        $date = time();
        $backupfile = $this->getRealBackupPath() . $date . '-' . $rand . '.sql';

        // Figure out what compression is available and open the file
        $this->id = realpath($backupfile);
        $fp = fopen($backupfile,"w");

        // Find all tables
        $tables = Db::getInstance()->executeS('SHOW TABLES');
        $found = 0;

        foreach ($tables as $table) {
            $table = current($table);

            // Skip tables which do not start with _DB_PREFIX_

            if (strlen($table) < strlen(_DB_PREFIX_) || strncmp($table, _DB_PREFIX_, strlen(_DB_PREFIX_)) != 0) {
                continue;
            }

            // Export the table schema
            $schema = Db::getInstance()->executeS('SHOW CREATE TABLE `' . $table . '`');

            if (count($schema) != 1 || !isset($schema[0]['Table']) || !isset($schema[0]['Create Table'])) {
                fclose($fp);
                $this->delete();
                echo Tools::displayError('An error occurred while backing up. Unable to obtain the schema of') . ' "' . $table;

                return false;
            }

            fwrite($fp, '/* Scheme for table ' . $schema[0]['Table'] . " */\n");

            if ($this->ephBackupDropTable) {
                fwrite($fp, 'DROP TABLE IF EXISTS `' . $schema[0]['Table'] . '`;' . "\n");
            }

            fwrite($fp, $schema[0]['Create Table'] . ";\n\n");

            if (in_array($schema[0]['Table'], $insertTable)) {
                $data = Db::getInstance()->query('SELECT * FROM `' . $schema[0]['Table'] . '`');
                $sizeof = DB::getInstance()->NumRows();
                $lines = explode("\n", $schema[0]['Create Table']);

                if ($data && $sizeof > 0) {
                    // Export the table data
                    fwrite($fp, 'INSERT INTO `' . $schema[0]['Table'] . "` VALUES\n");
                    $i = 1;

                    while ($row = DB::getInstance()->nextRow($data)) {
                        $s = '(';

                        foreach ($row as $field => $value) {
                            $tmp = "'" . pSQL($value, true) . "',";

                            if ($tmp != "'',") {
                                $s .= $tmp;
                            } else {

                                foreach ($lines as $line) {

                                    if (strpos($line, '`' . $field . '`') !== false) {

                                        if (preg_match('/(.*NOT NULL.*)/Ui', $line)) {
                                            $s .= "'',";
                                        } else {
                                            $s .= 'NULL,';
                                        }

                                        break;
                                    }

                                }

                            }

                        }

                        $s = rtrim($s, ',');

                        if ($i % 200 == 0 && $i < $sizeof) {
                            $s .= ");\nINSERT INTO `" . $schema[0]['Table'] . "` VALUES\n";
                        } else
                        if ($i < $sizeof) {
                            $s .= "),\n";
                        } else {
                            $s .= ");\n";
                        }

                        fwrite($fp, $s);
                        ++$i;
                    }

                }

            }

            $found++;
        }

        fclose($fp);

        if ($found == 0) {
            $this->delete();
            echo Tools::displayError('No valid tables were found to backup.');

            return false;
        }
        $sql = Tools::file_get_contents($backupfile);
        $this->delete();
        return $sql;
    }

    public function delete() {

        if (!$this->id || !unlink($this->id)) {
            $this->error = Tools::displayError('Error deleting') . ' ' . ($this->id ? '"' . $this->id . '"' :
                Tools::displayError('Invalid ID'));

            return false;
        }

        return true;
    }

}
