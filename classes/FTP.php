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


    /**
     * uploads a file to ftp server
     *
     * @param string $instanceName
     * @param string $backupDir
     * @param string $fileName
     * @return bool
     * @throws Exception
     */
    public function upload(string $instanceName, string $backupDir, string $fileName): bool {

        // check for ftp settings in config
        if ($this->config['ftp'] && is_array($this->config['ftp'])) {

            // sned to sftp server
            if ($this->config['ftp']['isSftp']) {
                return $this->sendToSftp($instanceName, $backupDir, $fileName, $this->config['ftp']['host'], $this->config['ftp']['port'], $this->config['ftp']['username'], $this->config['ftp']['password']);

            // sen dto ftp server
            } else {
                return $this->sendToFtp($instanceName, $backupDir, $fileName, $this->config['ftp']['host'], $this->config['ftp']['port'], $this->config['ftp']['username'], $this->config['ftp']['password']);
            }
        }

        return false;
    }

    // -------------------------------------------------------------------
    // Protected Functions
    // -------------------------------------------------------------------

    /**
     * send file to ftp server
     *
     * @param string $instanceName
     * @param string $backupDir
     * @param string $fileName
     * @param string $ftpHost
     * @param int|null $ftpPort
     * @param string $ftpUsername
     * @param string $ftpPassword
     * @return bool
     */
    protected function sendToFtp(string $instanceName, string $backupDir, string $fileName, string $ftpHost, int $ftpPort = null, string $ftpUsername, string $ftpPassword): bool {

        // define default port
        if (!$ftpPort) {
            $ftpPort = 21;
        }

        // set paths
        $sourceFile = $backupDir . DIRECTORY_SEPARATOR . $fileName;
        $destPath = $this->config['ftp']['folder'] . '/' . $instanceName;
        $destFile = $destPath . '/' . $fileName;

        // connect to server
        $ftpConnection = ftp_ssl_connect($ftpHost, $ftpPort);

        // login to server
        if (ftp_login($ftpConnection, $ftpUsername, $ftpPassword)) {

            // check if folder exist
            $folderExists = is_dir("ftp://{$ftpUsername}:{$ftpPassword}@$ftpHost/" . $destPath);
            if (!$folderExists) {
                ftp_mkdir($ftpConnection, $destPath);
            }
            ftp_pasv($ftpConnection, true);

            // upload file
            ftp_put($ftpConnection, $destFile, $sourceFile);

            // close connection
            return ftp_close($ftpConnection);

        } else {
            throw new Exception('Unable to authenticate on server');
        }

        return false;
    }


    /**
     * send file to sftp server
     *
     * @param string $instanceName
     * @param string $backupDir
     * @param string $fileName
     * @param string $ftpHost
     * @param int|null $ftpPort
     * @param string $ftpUsername
     * @param string $ftpPassword
     * @return bool
     * @throws Exception
     */
    protected function sendToSftp(string $instanceName, string $backupDir, string $fileName, string $ftpHost, int $ftpPort = null, string $ftpUsername, string $ftpPassword): bool {

        // define default port
        if (!$ftpPort) {
            $ftpPort = 22;
        }

        // set paths
        $sourceFile = $backupDir . DIRECTORY_SEPARATOR . $fileName;
        $destPath = $this->config['ftp']['folder'] . '/' . $instanceName;
        $destFile = $destPath . '/' . $fileName;

        // connect to server
        $resConnection = ssh2_connect($ftpHost, $ftpPort);

        // login to server
        if (ssh2_auth_password($resConnection, $ftpUsername, $ftpPassword)) {

            // Initialize SFTP subsystem
            $resSFTP = ssh2_sftp($resConnection);

            // check if folder exist
            $folderExists = is_dir("ssh2.sftp://{$resSFTP}/" . $destPath);
            if (!$folderExists) {
                ssh2_sftp_mkdir($resSFTP, $destPath, true);
            }

            // upload file
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