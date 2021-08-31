<?php


class WordpressBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create wordpress backups foreach wordpress instance in config
     *
     * @param array $backupFolders
     * @return string
     * @throws Exception
     */
    public static function createBackup(array $backupFolders = []): string {
        $log = '';
        $wpDirectories = General::getConfig('wpDirectories');

        if (!$backupFolders) {
            $backupFolders = ['wp-content'];
        }

        // loop wordpress instances in config
        foreach ($wpDirectories as $instanceName => $wpDirectory) {
            $directory = realpath($wpDirectory);

            // check if folder exists
            if (is_dir($directory)) {

                // define path for wp-config.php
                $wpConfigFile = $directory . DIRECTORY_SEPARATOR . 'wp-config.php';

                // check if wordpress config file exists
                if (file_exists($wpConfigFile)) {

                    // include config file
                    require $wpConfigFile;

                    // define backup and temp folder name for instance
                    $backupDir = General::getBackupDir($instanceName);
                    $tempDir = General::getTempDir($instanceName);

                    // backup wp-config.php
                    copy($wpConfigFile, $tempDir . DIRECTORY_SEPARATOR . 'wp-config.php');

                    // create database dump
                    $dbBackuper = new DbBackupper();
                    $dbBackuper->createDbBackup($instanceName, $tempDir, DB_HOST, null,DB_NAME, DB_USER, DB_PASSWORD);
                    unset($dbBackuper);

                    // create folder backup
                    $fileName = FolderBackupper::createFileBackup($instanceName, $tempDir, $backupDir, $wpDirectory, $backupFolders);

                    // on success
                    if ($fileName) {

                        // set log msg
                        $log .= date('d.m.Y H:i:s') . ' Wordpress Instance "' . $instanceName . '" backuped successfully' . "\n";

                        // upload file to ftp server
                        $uploaded = FTP::upload($instanceName, $backupDir, $fileName);

                        if ($uploaded) {
                            $log .= date('d.m.Y H:i:s') . ' Wordpress Instance Backup "' . $instanceName . '" uploaded to FTP successfully' . "\n";
                        } else {
                            $log .= date('d.m.Y H:i:s') . ' Wordpress Instance Backup "' . $instanceName . '" uploaded to FTP failed' . "\n";
                        }
                    } else {

                        // set log msg
                        $log .= date('d.m.Y H:i:s') . ' Wordpress Instance "' . $instanceName . '" backup failed' . "\n";
                    }

                } else {
                    throw new Exception('wordpress config file does not exist in folder: ' . $wpDirectory);
                }

            } else {
                throw new Exception('folder "' . $wpDirectory . '" does not exist');
            }
        }

        return $log;
    }
}