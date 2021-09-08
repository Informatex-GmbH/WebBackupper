<?php


class FolderBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------


    /**
     * create folder backups foreach folder
     *
     * @param array|null $folders
     * @return bool
     * @throws Exception
     */
    public static function createBackup(?array $folders = null): bool {

        if (!$folders) {
            $folders = General::getConfig('directories');
        }

        // loop folders
        foreach ($folders as $instanceName => $folder) {

            // define backup and temp folder name for instance
            $backupDir = General::getBackupDir($instanceName);
            $tempDir = General::getTempDir($instanceName);

            // create zip from folder
            $fileName = self::createFileBackup($instanceName, $tempDir, $backupDir, $folder);

            // on success
            if ($fileName) {

                // set log msg
                Logger::info('folder "' . $instanceName . '" backuped successfully');

                // upload file to ftp server
                $uploaded = FTP::upload($instanceName, $backupDir, $fileName);

                if ($uploaded) {
                    Logger::info('folder Backup "' . $instanceName . '" uploaded to (s)ftp server successfully');
                } else {
                    Logger::warning('folder Backup "' . $instanceName . '" uploaded to (s)ftp server failed');
                }
            } else {

                // set log msg
                Logger::error('folder "' . $instanceName . '" backup failed');
            }
        }

        return true;
    }


    /**
     * creates a backup from a folder
     *
     * @param string $instanceName
     * @param string $tempDir
     * @param string $backupDir
     * @param string $path
     * @param array $folders
     * @return string
     * @throws Exception
     */
    public static function createFileBackup(string $instanceName, string $tempDir, string $backupDir, string $path, array $folders = []): string {

        // read last folder from path
        if (!$folders) {
            $folders = basename($path);
            $path = dirname($path);
        }

        // make array
        if (!is_array($folders)) {
            $folders = [$folders];
        }

        // loop folders
        foreach ($folders as $folder) {
            $fromFolder = $path . DIRECTORY_SEPARATOR . $folder;
            $toFolder = $tempDir . DIRECTORY_SEPARATOR . $folder;

            // copy folder to temp folder
            if (is_dir($fromFolder)) {
                self::copyFolder($fromFolder, $toFolder);
            } else {
                Logger::warning(' Folder "' . $fromFolder . '" does not exist');
            }
        }

        // create zip from copied folder
        $fileName = self::zipFolder($tempDir, $backupDir, $instanceName);

        // delete temp folder
        self::deleteFolder($tempDir);

        // return name from zip file
        return $fileName;
    }

    // -------------------------------------------------------------------
    // Protected Functions
    // -------------------------------------------------------------------

    /**
     * create a copy from a folder recursively
     *
     * @param string $fromFolder
     * @param string $toFolder
     * @throws Exception
     */
    protected static function copyFolder(string $fromFolder, string $toFolder) {

        // check if folder exists
        if (!is_dir($fromFolder)) {
            Logger::warning('Folder "' . $fromFolder . '" does not exist');
        }

        // create folder if not exists
        if (!is_dir($toFolder)) {
            if (!mkdir($toFolder, 0777, true)) {
                Logger::warning('Folder "' . $toFolder . '" could not be created');
            }
        }

        // open from folder
        $dir = opendir($fromFolder);

        // loop trough files in source folder
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {

                // if file is a folder, call this function recursive
                if (is_dir($fromFolder . DIRECTORY_SEPARATOR . $file)) {
                    self::copyFolder($fromFolder . DIRECTORY_SEPARATOR . $file, $toFolder . DIRECTORY_SEPARATOR . $file);

                // copy file to new folder
                } else {
                    copy($fromFolder . DIRECTORY_SEPARATOR . $file, $toFolder . DIRECTORY_SEPARATOR . $file);
                }
            }
        }

        // close folder
        closedir($dir);
    }


    /**
     * deletes a folder recursively
     *
     * @param string $path
     * @return bool
     */
    protected static function deleteFolder(string $path): bool {
        $return = true;

        // if path is a dir, delete everything inside
        if (is_dir($path)) {

            // open folder
            $dh = opendir($path);

            // loop through all files an folders
            while (($fileName = readdir($dh)) !== false) {
                if ($fileName !== '.' && $fileName !== '..') {
                    self::deleteFolder($path . DIRECTORY_SEPARATOR . $fileName);
                }
            }

            // close folder
            closedir($dh);

            // delete folder
            if (!rmdir($path)) {
                $return = false;
            }

        // delete file
        } else {
            if (!unlink($path)) {
                $return = false;
            }
        }

        return $return;
    }


    /**
     * create zip from folder
     *
     * @param string $sourceDir
     * @param string $destinationDir
     * @param string $instanceName
     * @return string
     */
    protected static function zipFolder(string $sourceDir, string $destinationDir, string $instanceName): string {
        $sourceDir = realpath($sourceDir);
        $destinationDir = realpath($destinationDir);
        $fileName = date('Y-m-d-H-i-s_') . $instanceName . '.zip';

        // initialize archive object
        $zip = new ZipArchive();
        $zip->open($destinationDir . DIRECTORY_SEPARATOR . $fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // create recursive directory iterator
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            // skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);

                // add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // zip archive will be created only after closing object
        $zip->close();

        return $fileName;
    }
}
