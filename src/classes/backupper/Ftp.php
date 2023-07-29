<?php

namespace ifmx\WebBackupper\classes\backupper;

use ifmx\WebBackupper\classes;

class Ftp {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------


    /**
     * create folder backups foreach ftp-config
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
            $backupDir = classes\General::getBackupDir($instanceName);
            $tempDir = classes\General::getTempDir($instanceName);

            // download files from ftp server
            $success = classes\FTP::download($tempDir, $backupFtpConfig);

            if ($success) {
                classes\Logger::info('files from downloaded from FTP server successfully');

                // create zip from copied folder
                classes\Logger::debug('start to zip folder "' . $tempDir . '"');
                $fileName = classes\General::zipFolder($tempDir, $backupDir, $instanceName);

                // get file size
                $fileSize = classes\General::getFileSize($backupDir . DIRECTORY_SEPARATOR . $fileName);
                classes\Logger::debug('finished zipping folder "' . $tempDir . '". file size: ' . $fileSize);

                // delete temp folder
                classes\Logger::debug('start to delete temp folder "' . $tempDir . '"');
                classes\General::deleteFolder($tempDir);
                classes\Logger::debug('finished deleting temp folder "' . $tempDir . '"');

                // set log msg
                classes\Logger::info('folder "' . $instanceName . '" backuped successfully');

                // upload file to ftp server
                if ($ftpConfig) {
                    $uploaded = classes\FTP::upload($instanceName, $backupDir, $fileName, $ftpConfig);

                    if ($uploaded) {
                        classes\Logger::info('folder Backup "' . $instanceName . '" uploaded to FTP server successfully');
                    } else {
                        classes\Logger::warning('folder Backup "' . $instanceName . '" uploaded to FTP server failed');
                    }
                }

            } else {
                classes\Logger::error('files from downloaded from FTP server failed');
            }
        }

        return $files;
    }
}
