<?php


class WpBackupper {

    protected array $config;
    protected string $log;
    protected array $backupFolders = ['wp-content'];

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
        $wpDirectories = $this->config['wpDirectories'];

        foreach ($wpDirectories as $instanceName => $wpDirectory) {
            $directory = realpath($wpDirectory);

            if (is_dir($directory)) {

                $wpConfigFile = $directory . DIRECTORY_SEPARATOR . 'wp-config.php';
                if (file_exists($wpConfigFile)) {
                    require $wpConfigFile;

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
                    $dbBackuper->createDbBackup($instanceName, $tempDir, DB_HOST, null,DB_NAME, DB_USER, DB_PASSWORD);
                    unset($dbBackuper);

                    $folderBackuper = new FolderBackupper($this->config, $this->log);
                    $zipFileName = $folderBackuper->createFileBackup($instanceName, $tempDir, $backupDir, $wpDirectory, $this->backupFolders);

                    $this->log .= date('d.m.Y H:i:s') . ' Wordpress Instance "' . $instanceName . '" successfully backuped' . "\n";
                    
                    $ftp = new FTP($this->config);
                    $uploaded = $ftp->upload($instanceName, $backupDir, $zipFileName);
                    unset($ftp);

                    if ($uploaded) {
                        $this->log .= date('d.m.Y H:i:s') . ' Wordpress Instance Backup "' . $instanceName . '" successfully uploaded to FTP' . "\n";
                    }

                } else {
                    throw new Exception('Wordpress Config File does not exist');
                }

            } else {
                throw new Exception("Dir $wpDirectory does not exist");
            }
        }

        return 'success';
    }
}