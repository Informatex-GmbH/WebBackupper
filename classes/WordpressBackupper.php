<?php


class WordpressBackupper {

    protected array $config;
    protected array $backupFolders = ['wp-content'];

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
     * create wordpress backups foreach wordpress instance in config
     *
     * @return string
     * @throws Throwable
     */
    public function createBackup(): string {
        $log = '';
        $wpDirectories = $this->config['wpDirectories'];

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
                    $dbBackuper = new DbBackupper($this->config);
                    $dbBackuper->createDbBackup($instanceName, $tempDir, DB_HOST, null,DB_NAME, DB_USER, DB_PASSWORD);
                    unset($dbBackuper);

                    // create folder backup
                    $folderBackuper = new FolderBackupper($this->config);
                    $fileName = $folderBackuper->createFileBackup($instanceName, $tempDir, $backupDir, $wpDirectory, $this->backupFolders);

                    // on success
                    if ($fileName) {

                        // set log msg
                        $log .= date('d.m.Y H:i:s') . ' Wordpress Instance "' . $instanceName . '" backuped successfully' . "\n";

                        // upload file to ftp server
                        $ftp = new FTP($this->config);
                        $uploaded = $ftp->upload($instanceName, $backupDir, $fileName);
                        unset($ftp);

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