<?php

namespace ifmx\WebBackupper\classes;

class FolderBackupper {

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
            $backupDir = General::getBackupDir($instanceName);
            $tempDir = General::getTempDir($instanceName);

            // create zip from folder
            $fileName = self::createFileBackup($instanceName, $tempDir, $backupDir, $subfolders);

            // on success
            if ($fileName) {
                $files[$instanceName] = $fileName;

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

                // set log msg
                Logger::error('folder "' . $instanceName . '" backup failed');
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

                Logger::debug('start to copy folder "' . $fromFolder . '"');
                General::copyFolder($fromFolder, $toFolder);
                Logger::debug('finished copying folder "' . $fromFolder . '"');
            } else {
                Logger::error('folder "' . $folder . '" does not exist');
            }
        }

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

        // return name from zip file
        return $fileName;
    }
}
