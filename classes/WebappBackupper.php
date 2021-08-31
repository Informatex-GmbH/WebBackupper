<?php


class WebappBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create databases and folder backups foreach webapp in config
     *
     * @return string
     * @throws Throwable
     */
    public static function createBackup(): string {
        $log = '';
        $webapps = General::getConfig('webapps');

        // loop webapps in config
        foreach ($webapps as $instanceName => $webapp) {

            // define backup and temp folder name for instance
            $backupDir = General::getBackupDir($instanceName);
            $tempDir = General::getTempDir($instanceName);

            // create database dump
            $dbBackuper = new DbBackupper();
            $dbBackuper->createDbBackup($instanceName, $tempDir, $webapp['db']['host'], $webapp['db']['port'], $webapp['db']['name'], $webapp['db']['username'], $webapp['db']['password']);
            unset($dbBackuper);

            // create folder backup
            $fileName = FolderBackupper::createFileBackup($instanceName, $tempDir, $backupDir, $webapp['directory'], $webapp['subDirectories']);

            // on success
            if ($fileName) {

                // set log msg
                $log .= date('d.m.Y H:i:s') . ' Webapp "' . $instanceName . '" backuped successfully' . "\n";

                // upload file to ftp server
                $uploaded = FTP::upload($instanceName, $backupDir, $fileName);

                if ($uploaded) {
                    $log .= date('d.m.Y H:i:s') . ' Webapp Backup "' . $instanceName . '" uploaded to FTP successfully' . "\n";
                } else {
                    $log .= date('d.m.Y H:i:s') . ' Webapp Backup "' . $instanceName . '" uploaded to FTP failed' . "\n";
                }
            } else {

                // set log msg
                $log .= date('d.m.Y H:i:s') . ' Webapp "' . $instanceName . '" backup failed' . "\n";
            }
        }

        return $log;
    }
}