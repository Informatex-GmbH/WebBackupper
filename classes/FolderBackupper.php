<?php


class FolderBackupper {

    protected array $config;

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param array $config
     */
    public function __construct(array $config) {
        date_default_timezone_set('Europe/Zurich');

        $this->config = $config;
    }


    /**
     * create folder backups foreach folder in config
     *
     * @return string
     * @throws Throwable
     */
    public function createBackup(): string {
        $log = '';
        $folders = $this->config['directories'];

        // loop folders in db
        foreach ($folders as $instanceName => $folder) {

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

            // create zip from folder
            $fileName = $this->createFileBackup($instanceName, $tempDir, $backupDir, $folder);

            // on success
            if ($fileName) {

                // set log msg
                $log .= date('d.m.Y H:i:s') . ' Folder "' . $instanceName . '" backuped successfully' . "\n";

                // upload file to ftp server
                $ftp = new FTP($this->config);
                $uploaded = $ftp->upload($instanceName, $backupDir, $fileName);
                unset($ftp);

                if ($uploaded) {
                    $log .= date('d.m.Y H:i:s') . ' Folder Backup "' . $instanceName . '" uploaded to FTP successfully' . "\n";
                } else {
                    $log .= date('d.m.Y H:i:s') . ' Folder Backup "' . $instanceName . '" uploaded to FTP failed' . "\n";
                }
            } else {

                // set log msg
                $log .= date('d.m.Y H:i:s') . ' Folder "' . $instanceName . '" backup failed' . "\n";
            }
        }

        return $log;
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
    public function createFileBackup(string $instanceName, string $tempDir, string $backupDir, string $path, $folders = []): string {

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
                $this->copyFolder($fromFolder, $toFolder);
            }
        }

        // create zip from copied folder
        $fileName = $this->zipFolder($tempDir, $backupDir, $instanceName);

        // delete temp folder
        $this->deleteFolder($tempDir);

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
    protected function copyFolder(string $fromFolder, string $toFolder) {

        // check if folder exists
        if (!is_dir($fromFolder)) {
            throw new Exception("Folder $fromFolder doesn't exist");
        }

        // create folder if not exists
        if (!is_dir($toFolder)) {
            if (!mkdir($toFolder, 0777, true)) {
                throw new Exception('Folder could not be created: ' . $toFolder);
            }
        }

        // open from folder
        $dir = opendir($fromFolder);

        // loop trough files in source folder
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {

                // if file is a folder, call this function recursive
                if (is_dir($fromFolder . DIRECTORY_SEPARATOR . $file)) {
                    $this->copyFolder($fromFolder . DIRECTORY_SEPARATOR . $file, $toFolder . DIRECTORY_SEPARATOR . $file);

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
    protected function deleteFolder(string $path): bool {
        $return = true;

        // if path is a dir, delete everything inside
        if (is_dir($path)) {

            // open folder
            $dh = opendir($path);

            // loop through all files an folders
            while (($fileName = readdir($dh)) !== false) {
                if ($fileName !== '.' && $fileName !== '..') {
                    $this->deleteFolder($path . DIRECTORY_SEPARATOR . $fileName);
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


    protected function zipFolder(string $sourceDir, string $destinationDir, string $instanceName): string {
        $sourceDir = realpath($sourceDir);
        $destinationDir = realpath($destinationDir);
        $fileName = date('Y-m-d-H-i-s_') . $instanceName . '.zip';

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($destinationDir . DIRECTORY_SEPARATOR . $fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();

        return $fileName;
    }
}
