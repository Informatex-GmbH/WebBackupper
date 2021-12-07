<?php

namespace ifmx\WebBackupper\classes;

class FTP {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * uploads a file to ftp server
     *
     * @param string $instanceName
     * @param string $backupDir
     * @param string $fileName
     * @param array  $ftpConfigs
     * @return bool
     * @throws \Exception
     */
    public static function upload(string $instanceName, string $backupDir, string $fileName, array $ftpConfigs): bool {

        // check if multiple configs are given or not
        if (!is_array($ftpConfigs[array_keys($ftpConfigs)[0]])) {
            $ftpConfigs[] = $ftpConfigs;
        }

        foreach ($ftpConfigs as $ftpConfig) {
            $isSftp = $ftpConfig['isSftp'];
            $ftpHost = $ftpConfig['host'];
            $ftpUsername = $ftpConfig['username'];
            $ftpPassword = $ftpConfig['password'];
            $ftpPath = $ftpConfig['path'];
            $ftpPort = $ftpConfig['port'];

            // send to sftp server
            if ($isSftp) {
                self::sendToSftp($instanceName, $backupDir, $fileName, $ftpHost, $ftpUsername, $ftpPassword, $ftpPath, $ftpPort);

            // send to ftp server
            } else {
                self::sendToFtp($instanceName, $backupDir, $fileName, $ftpHost, $ftpUsername, $ftpPassword, $ftpPath, $ftpPort);
            }
        }

        return true;
    }

    // -------------------------------------------------------------------
    // Protected Functions
    // -------------------------------------------------------------------

    /**
     * send file to ftp server
     *
     * @param string   $instanceName
     * @param string   $backupDir
     * @param string   $fileName
     * @param string   $ftpHost
     * @param string   $ftpUsername
     * @param string   $ftpPassword
     * @param string   $ftpPath
     * @param int|null $ftpPort
     * @return bool
     * @throws \Exception
     */
    protected static function sendToFtp(string $instanceName, string $backupDir, string $fileName, string $ftpHost, string $ftpUsername, string $ftpPassword, string $ftpPath, ?int $ftpPort = null): bool {
        try {

            Logger::debug('start upload to ftp server');

            // define default port
            if (!$ftpPort) {
                $ftpPort = 21;
            }

            // set paths
            $sourceFile = $backupDir . DIRECTORY_SEPARATOR . $fileName;
            $destPath = $ftpPath . '/' . $instanceName;
            $destFile = $destPath . '/' . $fileName;

            Logger::debug('try connect to ftp server');

            // connect to server
            $connection = ftp_ssl_connect($ftpHost, $ftpPort);

            // check connection
            if ($connection) {
                Logger::debug('successfully connected to ftp server');
                Logger::debug('try to authenticate on ftp server');

                // login to server
                if (ftp_login($connection, $ftpUsername, $ftpPassword)) {
                    Logger::debug('successfully authenticated on ftp server');

                    // check if folder exist
                    $folderExists = is_dir("ftp://$ftpUsername:$ftpPassword@$ftpHost/" . $destPath);
                    if (!$folderExists) {
                        Logger::debug('try to create folder "' . $destPath . '" on sftp server');

                        if (ftp_mkdir($connection, $destPath)) {
                            Logger::debug('successfully created folder "' . $destPath . '" on ftp server');
                        } else {
                            Logger::error('could not create folder "' . $destPath . '" on ftp server');

                            return false;
                        }
                    }
                    ftp_pasv($connection, true);

                    // get file size
                    $fileSize = General::getFileSize($sourceFile);

                    Logger::debug('start upload file "' . $destFile . '" to ftp server. file size: ' . $fileSize);

                    // upload file
                    if (ftp_put($connection, $destFile, $sourceFile)) {
                        Logger::debug('successfully uploaded file "' . $destFile . '" to ftp server');
                    } else {
                        Logger::error('upload failed for file "' . $destFile . '" to ftp server');
                    }

                    // close connection
                    return ftp_close($connection);

                } else {
                    Logger::error('unable to authenticate on ftp server: ' . $ftpHost . ':' . $ftpPort);
                }
            } else {
                Logger::error('unable to connect to ftp server: ' . $ftpHost . ':' . $ftpPort);
            }

            return false;

        } catch (Throwable $e) {
            Logger::error($e->getMessage());

            return false;
        }
    }


    /**
     * send file to sftp server
     *
     * @param string   $instanceName
     * @param string   $backupDir
     * @param string   $fileName
     * @param string   $ftpHost
     * @param string   $ftpUsername
     * @param string   $ftpPassword
     * @param string   $ftpPath
     * @param int|null $ftpPort
     * @return bool
     * @throws \Exception
     */
    protected static function sendToSftp(string $instanceName, string $backupDir, string $fileName, string $ftpHost, string $ftpUsername, string $ftpPassword, string $ftpPath, ?int $ftpPort = null): bool {
        try {

            Logger::debug('start upload to sftp server');

            // define default port
            if (!$ftpPort) {
                $ftpPort = 22;
            }

            // set paths
            $sourceFile = $backupDir . DIRECTORY_SEPARATOR . $fileName;
            $destPath = $ftpPath . '/' . $instanceName;
            $destFile = $destPath . '/' . $fileName;

            Logger::debug('try connect to sftp server');

            // connect to server
            $connection = ssh2_connect($ftpHost, $ftpPort);

            // check connection
            if ($connection) {
                Logger::debug('successfully connected to sftp server');
                Logger::debug('try to authenticate on sftp server');

                // login to server
                if (ssh2_auth_password($connection, $ftpUsername, $ftpPassword)) {
                    Logger::debug('successfully authenticated on sftp server');

                    // Initialize SFTP subsystem
                    $sftp = ssh2_sftp($connection);

                    // check if folder exist
                    $folderExists = is_dir("ssh2.sftp://$sftp/" . $destPath);
                    if (!$folderExists) {
                        Logger::debug('try to create folder "' . $destPath . '" on sftp server');

                        if (ssh2_sftp_mkdir($sftp, $destPath, true)) {
                            Logger::debug('successfully created folder "' . $destPath . '" on sftp server');
                        } else {
                            Logger::error('could not create folder "' . $destPath . '" on sftp server');

                            return false;
                        }
                    }

                    // upload file
                    $remFile = fopen("ssh2.sftp://$sftp/" . $destFile, 'w');
                    $srcFile = fopen($sourceFile, 'r');

                    if ($srcFile) {
                        if ($remFile) {

                            // get file size
                            $fileSize = General::getFileSize($sourceFile);

                            Logger::debug('start upload file "' . $destFile . '" to sftp server. file size: ' . $fileSize);

                            if (stream_copy_to_stream($srcFile, $remFile)) {
                                Logger::debug('successfully uploaded file "' . $destFile . '" to sftp server');
                            } else {
                                Logger::error('upload failed for file "' . $destFile . '" to sftp server');
                            }

                            // close files
                            fclose($remFile);
                            fclose($srcFile);

                            return true;

                        } else {
                            Logger::error('unable to create file on sftp server: ' . $destFile);
                        }
                    } else {
                        Logger::error('unable to open local file: ' . $sourceFile);
                    }

                    return false;

                } else {
                    Logger::error('unable to authenticate on sftp server: ' . $ftpHost . ':' . $ftpPort);
                }
            } else {
                Logger::error('unable to connect to sftp server: ' . $ftpHost . ':' . $ftpPort);
            }

            return false;

        } catch (Throwable $e) {
            Logger::error($e->getMessage());

            return false;
        }
    }
}
