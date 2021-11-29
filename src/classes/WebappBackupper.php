<?php

namespace ifmx\WebBackupper\classes;

class WebappBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create databases and folder backups foreach webapp in config
     *
     * @param array $webapps
     * @param array $ftpConfig
     * @return bool
     * @throws \Exception
     */
    public static function createBackup(array $webapps = [], array $ftpConfig = []): bool {

        // loop webapps in config
        foreach ($webapps as $instanceName => $webapp) {

            // define backup and temp folder name for instance
            $backupDir = General::getBackupDir($instanceName);
            $tempDir = General::getTempDir($instanceName);

            $dbHost = $webapp['db']['host'];
            $dbPort = $webapp['db']['port'];
            $dbName = $webapp['db']['name'];
            $dbUsername = $webapp['db']['username'];
            $dbPassword = $webapp['db']['password'];

            // create database dump
            DbBackupper::createDbBackup($instanceName, $tempDir, $dbHost, $dbPort, $dbName, $dbUsername, $dbPassword);

            // backup folders
            if ($webapp['subDirectories']) {
                $backupFolders = [];
                foreach ($webapp['subDirectories'] as $subDirectory) {
                    $backupFolders[] = $webapp['directory'] . DIRECTORY_SEPARATOR . $subDirectory;
                }
            } else {
                $backupFolders = $webapp['directory'];
            }

            // create folder backup
            $fileName = FolderBackupper::createFileBackup($instanceName, $tempDir, $backupDir, $backupFolders);

            // on success
            if ($fileName) {

                // set log msg
                Logger::info('webapp "' . $instanceName . '" backuped successfully');

                // upload file to ftp server
                $ftpIsSftp = $ftpConfig['isSftp'] ?: General::getConfig('ftp, isSftp');
                $ftpHost = $ftpConfig['host'] ?: General::getConfig('ftp, host');
                $ftpUsername = $ftpConfig['username'] ?: General::getConfig('ftp, username');
                $ftpPassword = $ftpConfig['password'] ?: General::getConfig('ftp, password');
                $ftpPort = $ftpConfig['port'] ?: General::getConfig('ftp, port');
                $ftpPath = $ftpConfig['path'] ?: General::getConfig('ftp, path');
                $uploaded = FTP::upload($instanceName, $backupDir, $fileName, $ftpIsSftp, $ftpHost, $ftpUsername, $ftpPassword, $ftpPath, $ftpPort);

                if ($uploaded) {
                    Logger::info('webapp backup "' . $instanceName . '" uploaded to FTP successfully');
                } else {
                    Logger::warning('webapp backup "' . $instanceName . '" uploaded to FTP failed');
                }
            } else {

                // set log msg
                Logger::error('webapp "' . $instanceName . '" backup failed');
            }
        }

        return true;
    }
}
