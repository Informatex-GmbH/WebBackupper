<?php


class WordpressBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create wordpress backups foreach wordpress instance in config
     *
     * @param array|null $wpDirectories
     * @return bool
     * @throws Exception
     */
    public static function createBackup(array $wpDirectories = null): bool {

        if (!$wpDirectories) {
            $wpDirectories = General::getConfig('wpDirectories');
        }

        // loop wordpress instances in config
        foreach ($wpDirectories as $instanceName => $wpInstance) {

            if (is_array($wpInstance)) {
                if (!$wpInstance['rootDirectory']) {
                    throw new Exception('wrong config for wordpress instance "' . $instanceName . '"');
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

                // define path for wp-config.php
                $wpConfigFile = $directory . DIRECTORY_SEPARATOR . 'wp-config.php';

                // check if wordpress config file exists
                if (file_exists($wpConfigFile)) {

                    // include config file
                    require $wpConfigFile;

                    // set timezone again because wp overwrite these
                    date_default_timezone_set(General::getConfig('system, timezone'));

                    // define backup and temp folder name for instance
                    $backupDir = General::getBackupDir($instanceName);
                    $tempDir = General::getTempDir($instanceName);

                    // backup wp-config.php
                    copy($wpConfigFile, $tempDir . DIRECTORY_SEPARATOR . 'wp-config.php');

                    // create database dump
                    DbBackupper::createDbBackup($instanceName, $tempDir, DB_HOST, null,DB_NAME, DB_USER, DB_PASSWORD);

                    // create folder backup
                    $fileName = FolderBackupper::createFileBackup($instanceName, $tempDir, $backupDir, $wpDirectory, $backupFolders);

                    // on success
                    if ($fileName) {

                        // set log msg
                        Logger::info('Wordpress Instance "' . $instanceName . '" backuped successfully');

                        // upload file to ftp server
                        $uploaded = FTP::upload($instanceName, $backupDir, $fileName);

                        if ($uploaded) {
                            Logger::info('Wordpress Instance Backup "' . $instanceName . '" uploaded to FTP successfully');
                        } else {
                            Logger::warning('Wordpress Instance Backup "' . $instanceName . '" uploaded to FTP failed');
                        }
                    } else {

                        // set log msg
                        Logger::error('Wordpress Instance "' . $instanceName . '" backup failed');
                    }

                } else {
                    throw new Exception('wordpress config file does not exist in folder: ' . $wpDirectory);
                }

            } else {
                throw new Exception('folder "' . $wpDirectory . '" does not exist');
            }
        }

        return true;
    }
}