<?php


class FolderBackupper {

    protected array $config;
    protected string $log;

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param array $config
     * @param string $log
     */
    public function __construct(array $config, string &$log) {
        date_default_timezone_set('Europe/Zurich');

        $this->config = $config;
        $this->log = &$log;
    }


    public function createBackup(): string {
        $folders = $this->config['directories'];

        foreach ($folders as $instanceName => $folder) {

            $backupDir = $this->config['system']['backupDirectory'] . DIRECTORY_SEPARATOR . $instanceName;
            $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backupper' . DIRECTORY_SEPARATOR. $instanceName;

            // Create Backup Folder if not exists
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0777, true)) {
                    throw new Exception('Folder could not be created: ' . $backupDir);
                }
            }

            // Create Temp Folder if not exists
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0777, true)) {
                    throw new Exception('Folder could not be created: ' . $tempDir);
                }
            }

            $zipFileName = $this->createFileBackup($instanceName, $tempDir, $backupDir, $folder);

            $this->log .= date('d.m.Y H:i:s') . ' Folders "' . $instanceName . '" successfully backuped' . "\n";

            $ftp = new FTP($this->config);
            $uploaded = $ftp->upload($instanceName, $backupDir, $zipFileName);
            unset($ftp);

            if ($uploaded) {
                $this->log .= date('d.m.Y H:i:s') . ' Folder Backup "' . $instanceName . '" successfully uploaded to FTP' . "\n";
            }
        }

        return 'success';
    }


    public function createFileBackup(string $instanceName, string $tempDir, string $backupDir, string $path, $folders = []): string {

        if (!$folders) {
            $folders = basename($path);
            $path = dirname($path);
        }

        if (!is_array($folders)) {
            $folders = [$folders];
        }

        foreach ($folders as $folder) {
            $fromFolder = $path . DIRECTORY_SEPARATOR . $folder;
            $toFolder = $tempDir . DIRECTORY_SEPARATOR . $folder;

            if (is_dir($fromFolder)) {
                $this->copyFolder($fromFolder, $toFolder);
            }
        }

        // zip folder
        $fileName = $this->zipFolder($tempDir, $backupDir, $instanceName);
        $this->deleteFolder($tempDir);

        return $fileName;
    }

    // -------------------------------------------------------------------
    // Private Functions
    // -------------------------------------------------------------------

    protected function copyFolder(string $fromFolder, string $toFolder, string $childFolder = null) {

        // Check if Dir exists
        if (!is_dir($fromFolder)) {
            throw new Exception("Folder $fromFolder doesn't exist");
        }

        // Create Folder if not exists
        if (!is_dir($toFolder)) {
            if (!mkdir($toFolder, 0777, true)) {
                throw new Exception('Folder could not be created: ' . $toFolder);
            }
        }

        $dir = opendir($fromFolder);

        if ($childFolder) {
            $childFolder = $toFolder . DIRECTORY_SEPARATOR . $childFolder;

            if (!mkdir($childFolder, 0777, true)) {
                throw new Exception('Folder could not be created: ' . $childFolder);
            }

            while (false !== ($file = readdir($dir))) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($fromFolder . DIRECTORY_SEPARATOR . $file)) {
                        $this->copyFolder($fromFolder . DIRECTORY_SEPARATOR . $file, $childFolder . $file);
                    } else {
                        copy($fromFolder . DIRECTORY_SEPARATOR . $file, $childFolder . DIRECTORY_SEPARATOR . $file);
                    }
                }
            }
        } else {
            // return $cc;
            while (false !== ($file = readdir($dir))) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($fromFolder . DIRECTORY_SEPARATOR . $file)) {
                        $this->copyFolder($fromFolder . DIRECTORY_SEPARATOR . $file, $toFolder . DIRECTORY_SEPARATOR . $file);
                    } else {
                        copy($fromFolder . DIRECTORY_SEPARATOR . $file, $toFolder . DIRECTORY_SEPARATOR . $file);
                    }
                }
            }
        }

        closedir($dir);
    }


    protected function deleteFolder(string $path): bool {
        $return = true;
        if (is_dir($path)) {
            $dh = opendir($path);
            while (($fileName = readdir($dh)) !== false) {
                if ($fileName !== '.' && $fileName !== '..') {
                    $this->deleteFolder($path . DIRECTORY_SEPARATOR . $fileName);
                }
            }
            closedir($dh);
            if (!rmdir($path)) {
                $return = false;
            }
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
