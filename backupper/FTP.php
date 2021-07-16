<?php


class FTP {

    protected array $config;

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param array $config
     * @throws \Throwable
     */
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function upload(string $instanceName, string $backupDir, string $fileName): bool {

        if ($this->config['ftp'] && is_array($this->config['ftp'])) {
            if ($this->config['ftp']['isSftp']) {
                return $this->sendToSftp($instanceName, $backupDir, $fileName, $this->config['ftp']['host'], $this->config['ftp']['port'], $this->config['ftp']['username'], $this->config['ftp']['password']);
            } else {
                return $this->sendToFtp($instanceName, $backupDir, $fileName, $this->config['ftp']['host'], $this->config['ftp']['port'], $this->config['ftp']['username'], $this->config['ftp']['password']);
            }
        }

        return false;
    }
    
    // Protected

    protected function sendToFtp(string $instanceName, string $backupDir, string $fileName, string $ftpHost, int $ftpPort, string $ftpUsername, string $ftpPassword): bool {

        $sourceFile = $backupDir . DIRECTORY_SEPARATOR . $fileName;
        $destPath = $this->config['ftp']['folder'] . '/' . $instanceName;
        $destFile = $destPath . '/' . $fileName;

        // Verbindung aufbauen
        $ftpConnection = ftp_connect($ftpHost, $ftpPort);

        // Login
        ftp_login($ftpConnection, $ftpUsername, $ftpPassword);

        // check if folder exist
        $folderExists = is_dir("ftp://{$ftpUsername}:{$ftpPassword}@$ftpHost/" . $destPath);
        if (!$folderExists) {
            ftp_mkdir($ftpConnection, $destPath);
        }
        ftp_pasv($ftpConnection, true);

        // Upload File
        ftp_put($ftpConnection, $destFile, $sourceFile);

        // Close connection
        return ftp_close($ftpConnection);
    }


    protected function sendToSftp(string $instanceName, string $backupDir, string $fileName, string $ftpHost, int $ftpPort, string $ftpUsername, string $ftpPassword): bool {

        $sourceFile = $backupDir . DIRECTORY_SEPARATOR . $fileName;
        $destPath = $this->config['ftp']['folder'] . '/' . $instanceName;
        $destFile = $destPath . '/' . $fileName;

        //connect to server
        $resConnection = ssh2_connect($ftpHost, $ftpPort);

        if (ssh2_auth_password($resConnection, $ftpUsername, $ftpPassword)) {

            // Initialize SFTP subsystem
            $resSFTP = ssh2_sftp($resConnection);

            // check if folder exist
            $folderExists = is_dir("ssh2.sftp://{$resSFTP}/" . $destPath);

            if (!$folderExists) {
                ssh2_sftp_mkdir($resSFTP, $destPath, true);
            }

            $resFile = fopen("ssh2.sftp://{$resSFTP}/" . $destFile, 'w');
            $srcFile = fopen($sourceFile, 'r');
            stream_copy_to_stream($srcFile, $resFile);
            fclose($resFile);
            fclose($srcFile);

            return true;
        } else {
            throw new Exception('Unable to authenticate on server');
        }

        return false;
    }
}