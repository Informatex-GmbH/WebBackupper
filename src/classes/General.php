<?php

namespace ifmx\WebBackupper\classes;

class General {

    public static array $config = [];


    /**
     * create a copy from a folder recursively
     *
     * @param string $fromFolder
     * @param string $toFolder
     * @throws \Exception
     */
    public static function copyFolder(string $fromFolder, string $toFolder): void {

        // check if folder exists
        if (!is_dir($fromFolder)) {
            Logger::error('Folder "' . $fromFolder . '" does not exist');
        }

        // create folder if not exists
        if (!is_dir($toFolder) && !mkdir($toFolder, 0777, true) && !is_dir($toFolder)) {
            Logger::error('Folder "' . $toFolder . '" could not be created');
        }

        // open from folder
        $dir = opendir($fromFolder);

        // loop trough files in source folder
        while (false !== ($file = readdir($dir))) {
            if ($file !== '.' && $file !== '..') {
                $fromFile = $fromFolder . DIRECTORY_SEPARATOR . $file;
                $toFile = $toFolder . DIRECTORY_SEPARATOR . $file;

                // if file is a folder, call this function recursive
                if (is_dir($fromFile)) {
                    self::copyFolder($fromFile, $toFile);
                } else {

                    // copy file to new folder
                    copy($fromFile, $toFile);
                }
            }
        }

        // close folder
        closedir($dir);
    }


    /**
     * deletes a folder recursively
     *
     * @param string $path
     * @return bool
     */
    public static function deleteFolder(string $path): bool {
        $return = true;

        // if path is a dir, delete everything inside
        if (is_dir($path)) {

            // open folder
            $dh = opendir($path);

            // loop through all files in a folders
            while (($fileName = readdir($dh)) !== false) {
                if ($fileName !== '.' && $fileName !== '..') {
                    self::deleteFolder($path . DIRECTORY_SEPARATOR . $fileName);
                }
            }

            // close folder
            closedir($dh);

            // delete folder
            if (!rmdir($path)) {
                $return = false;
            }

            // delete file
        } else if (!unlink($path)) {
            $return = false;
        }

        return $return;
    }


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
        if (!is_dir($backupDir) && !mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
            throw new \Exception('Folder could not be created: ' . $backupDir);
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
        if (!is_dir($logDir) && !mkdir($logDir, 0777, true) && !is_dir($logDir)) {
            throw new \Exception('Folder could not be created: ' . $logDir);
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
        if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new \Exception('Folder could not be created: ' . $tempDir);
        }

        return $tempDir;
    }


    /**
     * create zip from folder
     *
     * @param string $sourceDir
     * @param string $destinationDir
     * @param string $instanceName
     * @return string
     */
    public static function zipFolder(string $sourceDir, string $destinationDir, string $instanceName): string {
        $sourceDir = realpath($sourceDir);
        $destinationDir = realpath($destinationDir);
        $fileName = date('Y-m-d-H-i-s_') . $instanceName . '.zip';

        // initialize archive object
        $zip = new \ZipArchive();
        $zip->open($destinationDir . DIRECTORY_SEPARATOR . $fileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // create recursive directory iterator
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            // skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);

                // add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // zip archive will be created only after closing object
        $zip->close();

        return $fileName;
    }
}
