<?php


class WebappBackupper {

    protected array $config;

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param array $config
     */
    public function __construct(array $config) {
        $this->config = $config;
    }


    /**
     * create databases and folder backups foreach webapp in config
     *
     * @return string
     * @throws Throwable
     */
    public function createBackup(): string {
        $log = '';
        $webapps = $this->config['webapps'];

        // loop webapps in config
        foreach ($webapps as $instanceName => $webapp) {

            // define backup and temp folder name for instance
            $backupDir = $this->config['system']['backupDirectory'] . DIRECTORY_SEPARATOR . $instanceName;
            $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backupper' . DIRECTORY_SEPARATOR. $instanceName;

            // create backup folder if not exists
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0777, true)) {
                    throw new Exception('Folder could not be created: ' . $backupDir);
                }
            }

            // create temp folder if not exists
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0777, true)) {
                    throw new Exception('Folder could not be created: ' . $tempDir);
                }
            }

            // create database dump
            $dbBackuper = new DbBackupper($this->config, $this->log);
            $dbBackuper->createDbBackup($instanceName, $tempDir, $webapp['db']['host'], $webapp['db']['port'], $webapp['db']['name'], $webapp['db']['username'], $webapp['db']['password']);
            unset($dbBackuper);

            // create folder backup
            $folderBackuper = new FolderBackupper($this->config, $this->log);
            $fileName = $folderBackuper->createFileBackup($instanceName, $tempDir, $backupDir, $webapp['directory'], $webapp['subDirectories']);

            // on success
            if ($fileName) {

                // set log msg
                $log .= date('d.m.Y H:i:s') . ' Webapp "' . $instanceName . '" backuped successfully' . "\n";

                // upload file to ftp server
                $ftp = new FTP($this->config);
                $uploaded = $ftp->upload($instanceName, $backupDir, $fileName);
                unset($ftp);

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