<?php

namespace ifmx\WebBackupper\classes;

class FtpBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------


    /**
     * create folder backups foreach folder
     *
     * @param array $instances
     * @param array $ftpConfig
     * @return array
     * @throws \Exception
     */
    public static function createBackup(array $instances = [], array $ftpConfig = []): array {
        $files = [];

        // loop folders
        foreach ($instances as $instanceName => $backupFtpConfig) {

            // define backup and temp folder name for instance
            $backupDir = General::getBackupDir($instanceName);
            $tempDir = General::getTempDir($instanceName);

            // download files from ftp server
            $success = FTP::download($tempDir, $backupFtpConfig);

            if ($success) {
                Logger::info('files from downloaded from FTP server successfully');

                // create zip from copied folder
                Logger::debug('start to zip folder "' . $tempDir . '"');
                $fileName = General::zipFolder($tempDir, $backupDir, $instanceName);

                // get file size
                $fileSize = General::getFileSize($backupDir . DIRECTORY_SEPARATOR . $fileName);
                Logger::debug('finished zipping folder "' . $tempDir . '". file size: ' . $fileSize);

                // delete temp folder
                Logger::debug('start to delete temp folder "' . $tempDir . '"');
                General::deleteFolder($tempDir);
                Logger::debug('finished deleting temp folder "' . $tempDir . '"');

                // set log msg
                Logger::info('folder "' . $instanceName . '" backuped successfully');

                // upload file to ftp server
                if ($ftpConfig) {
                    $uploaded = FTP::upload($instanceName, $backupDir, $fileName, $ftpConfig);

                    if ($uploaded) {
                        Logger::info('folder Backup "' . $instanceName . '" uploaded to FTP server successfully');
                    } else {
                        Logger::warning('folder Backup "' . $instanceName . '" uploaded to FTP server failed');
                    }
                }

            } else {
                Logger::error('files from downloaded from FTP server failed');
            }
        }

        return $files;
    }
}
