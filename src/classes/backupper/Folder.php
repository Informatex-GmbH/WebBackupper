<?php

namespace ifmx\WebBackupper\classes\backupper;

use ifmx\WebBackupper\classes;

class Folder {

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
        foreach ($instances as $instanceName => $folderConfig) {

            $subfolders = $folderConfig;
            if (!is_array($subfolders)) {
                $subfolders = [$subfolders];
            }

            // define backup and temp folder name for instance
            $backupDir = classes\General::getBackupDir($instanceName);
            $tempDir = classes\General::getTempDir($instanceName);

            // create zip from folder
            $fileName = self::createFileBackup($instanceName, $tempDir, $backupDir, $subfolders);

            // on success
            if ($fileName) {
                $files[$instanceName] = $fileName;

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

                // set log msg
                classes\Logger::error('folder "' . $instanceName . '" backup failed');
            }
        }

        return $files;
    }


    /**
     * creates a backup from a folder
     *
     * @param string $instanceName
     * @param string $tempDir
     * @param string $backupDir
     * @param        $folders
     * @return string
     * @throws \Exception
     */
    public static function createFileBackup(string $instanceName, string $tempDir, string $backupDir, $folders): string {

        // make array
        if (!is_array($folders)) {
            $folders = [$folders];
        }

        // loop folders
        foreach ($folders as $folder) {
            $fromFolder = realpath($folder);

            // copy folder to temp folder
            if (is_dir($fromFolder)) {
                $folderName = basename($fromFolder);
                $toFolder = $tempDir . DIRECTORY_SEPARATOR . $folderName;

                classes\Logger::debug('start to copy folder "' . $fromFolder . '"');
                classes\General::copyFolder($fromFolder, $toFolder);
                classes\Logger::debug('finished copying folder "' . $fromFolder . '"');
            } else {
                classes\Logger::error('folder "' . $folder . '" does not exist');
            }
        }

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

        // return name from zip file
        return $fileName;
    }
}
