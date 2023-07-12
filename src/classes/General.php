<?php

namespace ifmx\WebBackupper\classes;

class General {

    public static array $config = [];


    /**
     * returns part of config or the hole config
     *
     * @param string|null $elements
     * @throws \Exception
     */
    public static function getConfig(string $elements = null) {
        $config = self::$config;

        $values = explode(',', $elements);
        foreach ($values as $val) {
            if ($config && array_key_exists(trim($val), $config)) {
                if ($config[trim($val)] === false) {
                    $config = false;
                } else {
                    $config = empty($config[trim($val)]) ? null : $config[trim($val)];
                }
            } else {
                $config = null;
            }
        }

        return $config;
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
            if (!mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
                throw new \Exception('Folder could not be created: ' . $backupDir);
            }
        }

        return $backupDir;
    }


    /**
     * returns the filesize with unit
     *
     * @param string $filePath
     * @return string
     */
    public static function getFileSize(string $filePath): string {
        $realPath = realpath($filePath);

        if ($realPath) {
            $fileSize = filesize($filePath);

            $unit = 'Byte';
            if ($fileSize >= 1073741824) {
                $fileSize = round($fileSize / 1073741824, 2);
                $unit = 'GB';
            } else if ($fileSize >= 1048576) {
                $fileSize = round($fileSize / 1048576, 2);
                $unit = 'MB';
            } else if ($fileSize >= 1024) {
                $fileSize = round($fileSize / 1024);
                $unit = 'KB';
            }

            return $fileSize . $unit;
        }

        return '';
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
            if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
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
            if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
                throw new \Exception('Folder could not be created: ' . $tempDir);
            }
        }

        return $tempDir;
    }
}
