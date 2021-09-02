<?php


class WebappBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create databases and folder backups foreach webapp in config
     *
     * @param array|null $webapps
     * @return bool
     * @throws Exception
     */
    public static function createBackup(?array $webapps = null): bool {

        if (!$webapps) {
            $webapps = General::getConfig('webapps');
        }

        // loop webapps in config
        foreach ($webapps as $instanceName => $webapp) {

            // define backup and temp folder name for instance
            $backupDir = General::getBackupDir($instanceName);
            $tempDir = General::getTempDir($instanceName);

            // create database dump
            DbBackupper::createDbBackup($instanceName, $tempDir, $webapp['db']['host'], $webapp['db']['port'], $webapp['db']['name'], $webapp['db']['username'], $webapp['db']['password']);

            // create folder backup
            $fileName = FolderBackupper::createFileBackup($instanceName, $tempDir, $backupDir, $webapp['directory'], $webapp['subDirectories']);

            // on success
            if ($fileName) {

                // set log msg
                Logger::info('Webapp "' . $instanceName . '" backuped successfully');

                // upload file to ftp server
                $uploaded = FTP::upload($instanceName, $backupDir, $fileName);

                if ($uploaded) {
                    Logger::info('Webapp Backup "' . $instanceName . '" uploaded to FTP successfully');
                } else {
                    Logger::warning('Webapp Backup "' . $instanceName . '" uploaded to FTP failed');
                }
            } else {

                // set log msg
                Logger::error('Webapp "' . $instanceName . '" backup failed');
            }
        }

        return true;
    }
}