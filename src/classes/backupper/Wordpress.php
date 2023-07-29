<?php

namespace ifmx\WebBackupper\classes\backupper;

use ifmx\WebBackupper\classes;

class Wordpress {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create wordpress backups foreach wordpress instance in config
     *
     * @param array $instances
     * @param array $ftpConfig
     * @return array
     * @throws \Exception
     */
    public static function createBackup(array $instances = [], array $ftpConfig = []): array {
        $files = [];

        // loop wordpress instances in config
        foreach ($instances as $instanceName => $wpInstance) {

            if (is_array($wpInstance)) {
                if (!$wpInstance['rootDirectory']) {
                    throw new \Exception('wrong config for wordpress instance "' . $instanceName . '"');
                }
                $wpDirectory = $wpInstance['rootDirectory'];
                $backupFolders = (array)$wpInstance['directories'];
            } else {
                $wpDirectory = $wpInstance;
                $backupFolders = ['wp-content'];
            }

            $directory = realpath($wpDirectory);

            // check if folder exists
            if (is_dir($directory)) {

                // backup folders
                foreach ($backupFolders as &$backupFolder) {
                    $backupFolder = $directory . DIRECTORY_SEPARATOR . $backupFolder;
                }
                unset($backupFolder);

                // define path for wp-config.php
                $wpConfigFile = $directory . DIRECTORY_SEPARATOR . 'wp-config.php';

                // check if wordpress config file exists
                if (file_exists($wpConfigFile)) {

                    // define backup and temp folder name for instance
                    $backupDir = classes\General::getBackupDir($instanceName);
                    $tempDir = classes\General::getTempDir($instanceName);

                    // backup wp-config.php
                    copy($wpConfigFile, $tempDir . DIRECTORY_SEPARATOR . 'wp-config.php');

                    // get content from wpconfig file
                    $wpConfig = file_get_contents($wpConfigFile);
                    $dbHost = self::getFromWpConfig($wpConfig, 'DB_HOST');
                    $dbName = self::getFromWpConfig($wpConfig, 'DB_NAME');
                    $dbUser = self::getFromWpConfig($wpConfig, 'DB_USER');
                    $dbPassword = self::getFromWpConfig($wpConfig, 'DB_PASSWORD');

                    // create database dump
                    Db::createDbBackup($instanceName, $tempDir, $dbHost, null, $dbName, $dbUser, $dbPassword);

                    // create folder backup
                    $fileName = Folder::createFileBackup($instanceName, $tempDir, $backupDir, $backupFolders);

                    // on success
                    if ($fileName) {
                        $files[$instanceName] = $fileName;

                        // set log msg
                        classes\Logger::info('wordpress instance "' . $instanceName . '" backuped successfully');

                        // upload file to ftp server
                        if ($ftpConfig) {
                            $uploaded = classes\FTP::upload($instanceName, $backupDir, $fileName, $ftpConfig);

                            if ($uploaded) {
                                classes\Logger::info('wordpress backup "' . $instanceName . '" successfully uploaded to FTP');
                            } else {
                                classes\Logger::warning('wordpress backup "' . $instanceName . '" upload to FTP failed');
                            }
                        }
                    } else {

                        // set log msg
                        classes\Logger::error('wordpress instance "' . $instanceName . '" backup failed');
                    }

                } else {
                    classes\Logger::error('wordpress config file does not exist in folder: ' . $wpDirectory);
                }

            } else {
                classes\Logger::error('folder "' . $wpDirectory . '" does not exist');
            }
        }

        return $files;
    }


    // Protected
    protected static function getFromWpConfig(string $wpConfig, string $defineString): ?string {
        $matches = [];
        $regex = "/define\(\s*['|\"]" . $defineString . "['|\"],\s*['|\"](.*)['|\"]\s*\);/";
        preg_match($regex, $wpConfig, $matches);

        if ($matches) {
            return $matches[1];
        }

        return null;
    }
}
