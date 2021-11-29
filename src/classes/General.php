<?php

namespace ifmx\WebBackupper\classes;

class General {

    public static array $config = [];


    /**
     * returns part of config or the hole config
     *
     * @param string|null $elements
     * @return array
     * @throws \Exception
     */
    public static function getConfig(string $elements = null) {
        $config = self::$config;

        $elements = explode(',', $elements);
        foreach ($elements as $val) {
            if ($config[trim($val)] === false) {
                $config = false;
            } else {
                $config = empty($config[trim($val)]) ? null : $config[trim($val)];
            }
        }

        return $config;
    }


    /**
     * returns all instance names in config
     *
     * @return array
     * @throws \Exception
     */
    public static function getInstanceNames(): array {
        $instanceNames = [];

        // backup databases
        $databases = General::getConfig('databases');
        if (isset($databases) && is_array($databases)) {
            foreach ($databases as $instanceName => $database) {
                $instanceNames[] = $instanceName;
            }
        }

        // backup directories
        $directories = General::getConfig('directories');
        if (isset($directories) && is_array($directories)) {
            foreach ($directories as $instanceName => $directory) {
                $instanceNames[] = $instanceName;
            }
        }

        // backup wordpress instances
        $wordpress = General::getConfig('wordpress');
        if (isset($wordpress) && is_array($wordpress)) {
            foreach ($wordpress as $instanceName => $wpDirectory) {
                $instanceNames[] = $instanceName;
            }
        }

        // backup folders and database to one file
        $webapps = General::getConfig('webapps');
        if (isset($webapps) && is_array($webapps)) {
            foreach ($webapps as $instanceName => $webapp) {
                $instanceNames[] = $instanceName;
            }
        }

        return $instanceNames;
    }


    /**
     * create backup dir and returns the path
     *
     * @throws \Exception
     */
    public static function getBackupDir(string $name): string {

        // define backup folder name for instance
        $backupDir = self::getConfig('sysDirectories, backup') . DIRECTORY_SEPARATOR . $name;

        // create backup folder if not exists
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0777, true)) {
                throw new \Exception('Folder could not be created: ' . $backupDir);
            }
        }

        return $backupDir;
    }


    /**
     * create log dir and returns the path
     *
     * @return string
     * @throws \Exception
     */
    public static function getLogDir(): string {

        // define log folder
        $logDir = self::getConfig('sysDirectories, log');

        // create temp folder if not exists
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0777, true)) {
                throw new \Exception('Folder could not be created: ' . $logDir);
            }
        }

        return $logDir;
    }


    /**
     * create temp dir and return the path
     *
     * @param string $name
     * @return string
     * @throws \Exception
     */
    public static function getTempDir(string $name): string {
        // define temp folder name for instance
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backupper' . DIRECTORY_SEPARATOR . $name;

        // create temp folder if not exists
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0777, true)) {
                throw new \Exception('Folder could not be created: ' . $tempDir);
            }
        }

        return $tempDir;
    }
}
