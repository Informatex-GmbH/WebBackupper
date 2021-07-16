<?php


class WebappBackupper {

    protected array $config;
    protected string $log;

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param array $config
     * @param string $log
     */
    public function __construct(array $config, string &$log) {
        $this->config = $config;
        $this->log = &$log;
    }


    public function createBackup(): string {
        $webapps = $this->config['webapps'];

        foreach ($webapps as $instanceName => $webapp) {

            $backupDir = $this->config['system']['backupDirectory'] . DIRECTORY_SEPARATOR . $instanceName;
            $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backupper' . DIRECTORY_SEPARATOR. $instanceName;

            // Create Backup Folder if not exists
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0777, true)) {
                    throw new Exception('Folder could not be created: ' . $backupDir);
                }
            }

            // Create Temp Folder if not exists
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0777, true)) {
                    throw new Exception('Folder could not be created: ' . $tempDir);
                }
            }

            $dbBackuper = new DbBackupper($this->config, $this->log);
            $dbBackuper->createDbBackup($instanceName, $tempDir, $webapp['db']['host'], $webapp['db']['port'], $webapp['db']['name'], $webapp['db']['username'], $webapp['db']['password']);
            unset($dbBackuper);

            $folderBackuper = new FolderBackupper($this->config, $this->log);
            $zipFileName = $folderBackuper->createFileBackup($instanceName, $tempDir, $backupDir, $webapp['directory'], $webapp['subDirectories']);
            
            $this->log .= date('d.m.Y H:i:s') . ' Webapp "' . $instanceName . '" successfully backuped' . "\n";
            
            $ftp = new FTP($this->config);
            $uploaded = $ftp->upload($instanceName, $backupDir, $zipFileName);
            unset($ftp);

            if ($uploaded) {
                $this->log .= date('d.m.Y H:i:s') . ' Webapp Backup "' . $instanceName . '" successfully uploaded to FTP' . "\n";
            }
        }

        return 'success';
    }
}