<?php

namespace ifmx\WebBackupper\classes;

class WordpressBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create wordpress backups foreach wordpress instance in config
     *
     * @param array $instances
     * @param array $ftpConfig
     * @return bool
     * @throws \Exception
     */
    public static function createBackup(array $instances = [], array $ftpConfig = []): bool {

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

                // define path for wp-config.php
                $wpConfigFile = $directory . DIRECTORY_SEPARATOR . 'wp-config.php';

                // check if wordpress config file exists
                if (file_exists($wpConfigFile)) {

                    // define backup and temp folder name for instance
                    $backupDir = General::getBackupDir($instanceName);
                    $tempDir = General::getTempDir($instanceName);

                    // backup wp-config.php
                    copy($wpConfigFile, $tempDir . DIRECTORY_SEPARATOR . 'wp-config.php');

                    // get content from wpconfig file
                    $wpConfig = file_get_contents($wpConfigFile);
                    $dbHost = self::getFromWpConfig($wpConfig, 'DB_HOST');
                    $dbName = self::getFromWpConfig($wpConfig, 'DB_NAME');
                    $dbUser = self::getFromWpConfig($wpConfig, 'DB_USER');
                    $dbPassword = self::getFromWpConfig($wpConfig, 'DB_PASSWORD');

                    // create database dump
                    DbBackupper::createDbBackup($instanceName, $tempDir, $dbHost, null, $dbName, $dbUser, $dbPassword);

                    // create folder backup
                    $fileName = FolderBackupper::createFileBackup($instanceName, $tempDir, $backupDir, $backupFolders);

                    // on success
                    if ($fileName) {

                        // set log msg
                        Logger::info('wordpress instance "' . $instanceName . '" backuped successfully');

                        // upload file to ftp server
                        if ($ftpConfig) {
                            $uploaded = FTP::upload($instanceName, $backupDir, $fileName, $ftpConfig['isSftp'], $ftpConfig['host'], $ftpConfig['username'], $ftpConfig['password'], $ftpConfig['path'], $ftpConfig['port']);

                            if ($uploaded) {
                                Logger::info('wordpress backup "' . $instanceName . '" successfully uploaded to FTP');
                            } else {
                                Logger::warning('wordpress backup "' . $instanceName . '" upload to FTP failed');
                            }
                        }
                    } else {

                        // set log msg
                        Logger::error('wordpress instance "' . $instanceName . '" backup failed');
                    }

                } else {
                    Logger::error('wordpress config file does not exist in folder: ' . $wpDirectory);
                }

            } else {
                Logger::error('folder "' . $wpDirectory . '" does not exist');
            }
        }

        return true;
    }


    // Protected
    protected static function getFromWpConfig(string $wpConfig, string $defineString): ?string {
        $matches = [];
        $regex = "/define\(\s*'$defineString',\s*'(.*)'\s*\);/";
        preg_match($regex, $wpConfig, $matches);

        if ($matches) {
            return $matches[1];
        }

        return null;
    }
}
