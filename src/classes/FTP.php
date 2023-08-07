<?php

namespace ifmx\WebBackupper\classes;

class FTP {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * delete files from ftp server
     *
     * @param string   $instanceName
     * @param array    $ftpConfig
     * @param int|null $limit
     * @return bool
     * @throws \Exception|\Throwable
     */
    public static function delete(string $instanceName, array $ftpConfig, ?int $limit = null): bool {

        $isSftp = $ftpConfig['isSftp'];
        $ftpHost = $ftpConfig['host'];
        $ftpUsername = $ftpConfig['username'];
        $ftpPassword = $ftpConfig['password'];
        $ftpPath = $ftpConfig['path'];
        $ftpPort = $ftpConfig['port'];

        // delete from to sftp server
        if ($isSftp) {
            return self::deleteFromSftp($instanceName, $ftpHost, $ftpUsername, $ftpPassword, $ftpPath, $ftpPort, $limit);
        }

        // delete from ftp server
        return self::deleteFromFtp($instanceName, $ftpHost, $ftpUsername, $ftpPassword, $ftpPath, $ftpPort, $limit);
    }


    /**
     * downloads files from ftp server
     *
     * @param string $tempDir
     * @param array  $ftpConfig
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     */
    public static function download(string $tempDir, array $ftpConfig): bool {

        $isSftp = $ftpConfig['isSftp'];
        $ftpHost = $ftpConfig['host'];
        $ftpUsername = $ftpConfig['username'];
        $ftpPassword = $ftpConfig['password'];
        $ftpPath = $ftpConfig['path'];
        $ftpPort = $ftpConfig['port'];

        // download from to sftp server
        if ($isSftp) {
            return self::getFromSftp($tempDir, $ftpHost, $ftpUsername, $ftpPassword, $ftpPath, $ftpPort);
        }

        // download from ftp server
        return self::getFromFtp($tempDir, $ftpHost, $ftpUsername, $ftpPassword, $ftpPath, $ftpPort);
    }


    /**
     * uploads a file to ftp server
     *
     * @param string $instanceName
     * @param string $backupDir
     * @param string $fileName
     * @param array  $ftpConfigs
     * @return bool
     * @throws \Exception|\Throwable
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
     * @throws \Exception
     * @throws \Throwable
     */
    protected static function deleteFromFtp(string $instanceName, string $ftpHost, string $ftpUsername, string $ftpPassword, string $ftpPath, ?int $ftpPort = null, int $limit = 10): bool {
        try {
            Logger::debug('start delete from ftp server');

            // define default port
            if (!$ftpPort) {
                $ftpPort = 21;
            }

            // set paths
            $sourcePath = "$ftpPath/$instanceName";

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
                    $folderExists = is_dir("ftp://$ftpUsername:$ftpPassword@$ftpHost/" . $sourcePath);
                    if ($folderExists) {

                        // change dir on ftp server
                        if (ftp_chdir($connection, $sourcePath)) {

                            // get list of files form ftp server
                            $fileList = ftp_nlist($connection, '.');
                            if ($fileList) {

                                // sort files by dates
                                usort($fileList, static function ($a, $b) use ($connection) {
                                    return ftp_mdtm($connection, $b) - ftp_mdtm($connection, $a);
                                });

                                // delete files
                                $filesToDelete = array_slice($fileList, $limit);
                                foreach ($filesToDelete as $file) {
                                    Logger::debug('try to delete "' . $file . '" from ftp server');
                                    $success = ftp_delete($connection, $file);

                                    if ($success) {
                                        Logger::debug('successfully deleted "' . $file . '" from ftp server');
                                    } else {
                                        throw new \Exception('could not delete "' . $file . '" from ftp server');
                                    }
                                }
                            } else {
                                throw new \Exception('could not get list of files from folder "' . $sourcePath . '" on ftp server');
                            }

                        } else {
                            throw new \Exception('could not change to folder "' . $sourcePath . '" on ftp server');
                        }

                    } else {
                        throw new \Exception('folder "' . $sourcePath . '" does not exist on ftp server');
                    }

                    // close connection
                    return ftp_close($connection);
                }

                throw new \Exception('unable to authenticate on ftp server: ' . $ftpHost . ':' . $ftpPort);
            }

            throw new \Exception('unable to connect to ftp server: ' . $ftpHost . ':' . $ftpPort);

        } catch (\Throwable $e) {

            // close connection
            if (isset($connection)) {
                ftp_close($connection);
            }

            throw $e;
        }
    }


    /**
     * @throws \Exception
     */
    protected static function deleteFromSftp(string $instanceName, string $ftpHost, string $ftpUsername, string $ftpPassword, string $ftpPath, ?int $ftpPort = null, int $limit = 10): bool {

        Logger::debug('start download from sftp server');

        // define default port
        if (!$ftpPort) {
            $ftpPort = 22;
        }

        // set paths
        $sourcePath = "$ftpPath/$instanceName";

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

                // initialize SFTP subsystem
                $sftp = ssh2_sftp($connection);

                // check if folder exist
                $folderExists = is_dir("ssh2.sftp://$sftp/$sourcePath");
                if ($folderExists) {

                    // get list of files form ftp server
                    $fileList = scandir("ssh2.sftp://$sftp/$sourcePath");
                    if ($fileList) {

                        // cleanup array
                        unset($fileList[0], $fileList[1]); // delete '.' and '..' from array

                        // sort files by dates
                        usort($fileList, static function ($a, $b) use ($sftp, $sourcePath) {
                            $aTimestamp = filemtime("ssh2.sftp://$sftp/$sourcePath/$a");
                            $bTimestamp = filemtime("ssh2.sftp://$sftp/$sourcePath/$b");

                            return $bTimestamp - $aTimestamp;
                        });

                        // delete files
                        $filesToDelete = array_slice($fileList, $limit);
                        foreach ($filesToDelete as $file) {
                            Logger::debug('try to delete "' . $file . '" from sftp server');
                            $success = ssh2_sftp_unlink($sftp, "$sourcePath/$file");

                            if ($success) {
                                Logger::debug('successfully deleted "' . $file . '" from sftp server');
                            } else {
                                throw new \Exception('could not delete "' . $file . '" from sftp server');
                            }
                        }

                        return true;
                    }
                } else {
                    throw new \Exception('folder "' . $sourcePath . '" does not exist on sftp server');
                }
            } else {
                throw new \Exception('unable to authenticate on sftp server: ' . $ftpHost . ':' . $ftpPort);
            }
        } else {
            throw new \Exception('unable to connect to sftp server: ' . $ftpHost . ':' . $ftpPort);
        }

        return false;
    }


    /**
     * @throws \Exception
     */
    protected static function downloadFolderFromFtpRecursively(\FTP\Connection $connection, string $folder, string $subFolder, string $destPath): bool {

        $list = ftp_mlsd($connection, $folder . '/' . $subFolder);
        foreach ($list as $file) {
            if ($file['type'] === 'file') {
                ftp_get($connection, $destPath . DIRECTORY_SEPARATOR . $file['name'], $folder . '/' . $subFolder . '/' . $file['name']);
            } else if ($file['type'] === 'dir') {
                $subFolder = $file['name'];
                $destPath .= DIRECTORY_SEPARATOR . $subFolder;

                // create folder if not exists
                if (!is_dir($destPath) && !mkdir($destPath, 0777, true) && !is_dir($destPath)) {
                    throw new \Exception('Folder could not be created: ' . $destPath);
                }

                // call method recursively
                return self::downloadFolderFromFtpRecursively($connection, $folder, $subFolder, $destPath);
            }
        }

        return true;
    }


    /**
     * @throws \Exception
     */
    protected static function downloadFolderFromSftpRecursively($sftp, string $sourcePath, string $subFolder, string $destPath): bool {

        $remoteFiles = scandir("ssh2.sftp://$sftp/$sourcePath/$subFolder");

        foreach ($remoteFiles as $file) {
            if ($file !== '.' && $file !== '..') {
                $remoteFilePath = "$sourcePath/$subFolder/$file";

                if (is_dir("ssh2.sftp://$sftp/$remoteFilePath")) {
                    $subFolder = $file;
                    $destPath .= DIRECTORY_SEPARATOR . $subFolder;

                    // create folder if not exists
                    if (!is_dir($destPath) && !mkdir($destPath, 0777, true) && !is_dir($destPath)) {
                        throw new \Exception('Folder could not be created: ' . $destPath);
                    }

                    // call method recursively
                    return self::downloadFolderFromSftpRecursively($sftp, $sourcePath, $subFolder, $destPath);
                }

                if (is_file("ssh2.sftp://$sftp/$remoteFilePath")) {

                    $remoteStream = fopen("ssh2.sftp://$sftp/$remoteFilePath", 'rb');
                    $localStream = fopen("$destPath/$file", 'wb');

                    if (!$remoteStream || !$localStream) {
                        throw new \Exception('file stream for file "' . "$subFolder/$file" . '"could not be opened');
                    }

                    // copy file
                    stream_copy_to_stream($remoteStream, $localStream);

                    fclose($remoteStream);
                    fclose($localStream);
                }
            }
        }

        return true;
    }


    /**
     * get files from ftp server
     *
     * @param string   $tempDir
     * @param string   $ftpHost
     * @param string   $ftpUsername
     * @param string   $ftpPassword
     * @param string   $ftpPath
     * @param int|null $ftpPort
     * @return bool
     * @throws \Exception|\Throwable
     */
    protected static function getFromFtp(string $tempDir, string $ftpHost, string $ftpUsername, string $ftpPassword, string $ftpPath, ?int $ftpPort = null): bool {
        try {

            Logger::debug('start download from ftp server');

            // define default port
            if (!$ftpPort) {
                $ftpPort = 21;
            }

            // set paths
            $sourcePath = $ftpPath;
            $destPath = $tempDir;

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
                    $folderExists = is_dir("ftp://$ftpUsername:$ftpPassword@$ftpHost/" . $sourcePath);
                    if ($folderExists) {
                        Logger::debug('try to download from "' . $sourcePath . '" on sftp server');

                        $success = self::downloadFolderFromFtpRecursively($connection, $sourcePath, '/', $destPath);

                        if ($success) {
                            Logger::info('successfully downloaded files from ftp server');
                        } else {
                            throw new \Exception('could not download files from ftp server');
                        }
                    } else {
                        throw new \Exception('folder "' . $sourcePath . '" does not exist on ftp server');
                    }

                    // close connection
                    return ftp_close($connection);
                }

                throw new \Exception('unable to authenticate on ftp server: ' . $ftpHost . ':' . $ftpPort);
            }

            throw new \Exception('unable to connect to ftp server: ' . $ftpHost . ':' . $ftpPort);

        } catch (\Throwable $e) {

            // close connection
            if (isset($connection)) {
                ftp_close($connection);
            }

            throw $e;
        }
    }


    /**
     * get files from sftp server
     *
     * @param string   $tempDir
     * @param string   $ftpHost
     * @param string   $ftpUsername
     * @param string   $ftpPassword
     * @param string   $ftpPath
     * @param int|null $ftpPort
     * @return bool
     * @throws \Exception
     */
    protected static function getFromSftp(string $tempDir, string $ftpHost, string $ftpUsername, string $ftpPassword, string $ftpPath, ?int $ftpPort = null): bool {

        Logger::debug('start download from sftp server');

        // define default port
        if (!$ftpPort) {
            $ftpPort = 22;
        }

        // set paths
        $sourcePath = $ftpPath;
        $destPath = $tempDir;

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

                // initialize SFTP subsystem
                $sftp = ssh2_sftp($connection);

                // check if folder exist
                $folderExists = is_dir("ssh2.sftp://$sftp/$sourcePath");
                if ($folderExists) {
                    Logger::debug('try to download from "' . $sourcePath . '" on sftp server');
                    $success = self::downloadFolderFromSftpRecursively($sftp, $sourcePath, '/', $destPath);

                    if ($success) {
                        Logger::info('successfully downloaded files from sftp server');

                        return true;
                    }

                    throw new \Exception('could not download files from sftp server');
                }

                throw new \Exception('folder "' . $sourcePath . '" does not exist on sftp server');
            }

            throw new \Exception('unable to authenticate on sftp server: ' . $ftpHost . ':' . $ftpPort);
        }

        throw new \Exception('unable to connect to sftp server: ' . $ftpHost . ':' . $ftpPort);
    }


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
     * @throws \Throwable
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
                            throw new \Exception('could not create folder "' . $destPath . '" on ftp server');
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
                        throw new \Exception('upload failed for file "' . $destFile . '" to ftp server');
                    }

                    // close connection
                    return ftp_close($connection);
                }

                throw new \Exception('unable to authenticate on ftp server: ' . $ftpHost . ':' . $ftpPort);
            }

            throw new \Exception('unable to connect to ftp server: ' . $ftpHost . ':' . $ftpPort);

        } catch (\Throwable $e) {

            // close connection
            if (isset($connection)) {
                ftp_close($connection);
            }

            throw $e;
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

                // initialize SFTP subsystem
                $sftp = ssh2_sftp($connection);

                // check if folder exist
                $folderExists = is_dir("ssh2.sftp://$sftp/" . $destPath);
                if (!$folderExists) {
                    Logger::debug('try to create folder "' . $destPath . '" on sftp server');

                    if (ssh2_sftp_mkdir($sftp, $destPath, true)) {
                        Logger::debug('successfully created folder "' . $destPath . '" on sftp server');
                    } else {
                        throw new \Exception('could not create folder "' . $destPath . '" on sftp server');
                    }
                }

                // upload file
                $remFile = fopen("ssh2.sftp://$sftp/" . $destFile, 'wb');
                $srcFile = fopen($sourceFile, 'rb');

                if ($srcFile) {
                    if ($remFile) {

                        // get file size
                        $fileSize = General::getFileSize($sourceFile);

                        Logger::debug('start upload file "' . $destFile . '" to sftp server. file size: ' . $fileSize);

                        if (stream_copy_to_stream($srcFile, $remFile)) {
                            Logger::debug('successfully uploaded file "' . $destFile . '" to sftp server');
                        } else {
                            throw new \Exception('upload failed for file "' . $destFile . '" to sftp server');
                        }

                        // close files
                        fclose($remFile);
                        fclose($srcFile);

                        return true;

                    }

                    throw new \Exception('unable to create file on sftp server: ' . $destFile);
                }

                throw new \Exception('unable to open local file: ' . $sourceFile);
            }

            throw new \Exception('unable to authenticate on sftp server: ' . $ftpHost . ':' . $ftpPort);
        }

        throw new \Exception('unable to connect to sftp server: ' . $ftpHost . ':' . $ftpPort);
    }
}
