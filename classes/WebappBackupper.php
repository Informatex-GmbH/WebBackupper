<?php



class WebappBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create databases and folder backups foreach webapp in config
     *
     * @param array $webapps
     * @return bool
     * @throws Exception
     */
    public static function createBackup(array $webapps = []): bool {

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
                Logger::info('webapp "' . $instanceName . '" backuped successfully');

                // upload file to ftp server
                $uploaded = FTP::upload($instanceName, $backupDir, $fileName);

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
