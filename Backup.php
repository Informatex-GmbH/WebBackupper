<?php


class Backup {

    protected array $config;
    protected array $backupFolders = ['wp-content'];

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * App constructor.
     *
     * @param array $config
     * @throws \Throwable
     */
    public function __construct(array $config) {
        $this->config = $config;
    }


    public function createBackup(): string {
        $wpDirectories = $this->config['wpDirectories'];

        foreach ($wpDirectories as $instanceName => $wpDirectory) {
            $directory = realpath($wpDirectory);

            if (is_dir($directory)) {

                $wpConfigFile = $directory . DIRECTORY_SEPARATOR . 'wp-config.php';
                if (file_exists($wpConfigFile)) {
                    require $wpConfigFile;

                    $tempDir = sys_get_temp_dir();
                    $tempDir .= DIRECTORY_SEPARATOR . $instanceName . DIRECTORY_SEPARATOR;

                    // Create Temp Folder if not exists
                    if (!is_dir($tempDir)) {
                        if (!mkdir($tempDir, 0777, true)) {
                            throw new Exception('Folder could not be created');
                        }
                    }

                    $dbBackupResult = $this->createDbBackup($instanceName, $tempDir, DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
                    $fileBackupResult = $this->createFileBackup($tempDir, $wpDirectory);
                    $zipFileName = $this->zipFolder($instanceName, $tempDir);

                    if ($this->config['ftp']['isSftp']) {
                        $send = $this->sendToSftp($instanceName, $zipFileName, $this->config['ftp']['host'], $this->config['ftp']['port'], $this->config['ftp']['username'], $this->config['ftp']['password']);
                    } else {
                        $send = $this->sendToFtp($instanceName, $zipFileName, $this->config['ftp']['host'], $this->config['ftp']['port'], $this->config['ftp']['username'], $this->config['ftp']['password']);
                    }

                    $this->deleteFolder($tempDir);

                } else {
                    throw new Exception('Wordpress Config File does not exist');
                }

            } else {
                throw new Exception("Dir $wpDirectory does not exist");
            }
        }

        return 'success';
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
                throw new Exception('Folder could not be created');
            }
        }

        $dir = opendir($fromFolder);

        if ($childFolder) {
            $childFolder = $toFolder . DIRECTORY_SEPARATOR . $childFolder;

            if (!mkdir($childFolder, 0777, true)) {
                throw new Exception('Folder could not be created');
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


    protected function createDbBackup(string $instanceName, string $tempDir, string $dbHost, string $dbName, string $dbUser, string $dbPassword): bool {
        $dbPort = '';

        // SQL Daten definieren
        $sqlName = $instanceName . '_DB_Backup_' . date('Y-m-d-H-i-s') . '.sql';
        $sqlPath = $tempDir . $sqlName;

        // Check if port on host
        preg_match('/(:\d+)/', $dbHost, $matches);
        if ($matches && $matches[1]) {
            $dbPort = "\nport=" . substr($matches[1], 1, );
            $dbHost = str_replace($matches[1], '', $dbHost);
        }

        // Content for Access File
        $fileContent = "[client]\nhost=$dbHost$dbPort\nuser=$dbUser\npassword=$dbPassword";

        // Access File schreiben
        if (!file_put_contents($tempDir . 'dbAccess.conf', $fileContent)) {
            throw new Exception('DB-Access Datei konnte nicht erstellt werden');
        }

        $variables = '--skip-opt --single-transaction --create-options --add-drop-table --set-charset --disable-keys --extended-insert --quick';

        if ($_SERVER['REMOTE_ADDR'] === '::1') {
            $variables .= ' --column-statistics=0';
        }

        // Create DB-Dump
        $command = $this->config['paths']['mysqldump'] . \DIRECTORY_SEPARATOR . 'mysqldump --defaults-file=' . $tempDir . 'dbAccess.conf ' . $variables . ' ' . $dbName . ' > ' . $sqlPath;

        // Befehl ausführen
        $response = [];
        $status = false;
        exec($command, $response, $status);

        unlink($tempDir . 'dbAccess.conf');

        // Fehler ausgeben
        if ($status) {
            throw new Exception('Create DB Dump failed');
        }

        return true;
    }


    protected function createFileBackup(string $tempDir, string $wpDirectory): bool {

        foreach ($this->backupFolders as $backupFolder) {
            $backupDir = $tempDir . $backupFolder;
            $folderForBackup = realpath($wpDirectory . DIRECTORY_SEPARATOR . $backupFolder);

            if (is_dir($folderForBackup)) {
                $this->copyFolder($folderForBackup, $backupDir);

            }
        }

        return true;
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


    protected function sendToFtp(string $instanceName, string $fileName, string $ftpHost, int $ftpPort, string $ftpUsername, string $ftpPassword): bool {

        $sourceFile = $this->config['sysDirectories']['backup'] . DIRECTORY_SEPARATOR . $instanceName . DIRECTORY_SEPARATOR . $fileName;
        $destPath = $this->config['ftp']['folder'] . '/' . $instanceName;
        $destFile = $destPath . '/' . $fileName;

        // Verbindung aufbauen
        $ftpConnection = ftp_connect($ftpHost, $ftpPort);

        // Login
        ftp_login($ftpConnection, $ftpUsername, $ftpPassword);

        // check if folder exist
        $folderExists = is_dir("ftp://{$ftpUsername}:{$ftpPassword}@$ftpHost/" . $destPath);
        if (!$folderExists) {
            ftp_mkdir($ftpConnection, $destPath);
        }
        ftp_pasv($ftpConnection, true);

        // Upload File
        ftp_put($ftpConnection, $destFile, $sourceFile);

        // Close connection
        return ftp_close($ftpConnection);
    }


    protected function sendToSftp(string $instanceName, string $fileName, string $ftpHost, int $ftpPort, string $ftpUsername, string $ftpPassword): bool {

        $sourceFile = $this->config['sysDirectories']['backup'] . DIRECTORY_SEPARATOR . $instanceName . DIRECTORY_SEPARATOR . $fileName;
        $destPath = $this->config['ftp']['folder'] . '/' . $instanceName;
        $destFile = $destPath . '/' . $fileName;

        //connect to server
        $resConnection = ssh2_connect($ftpHost, $ftpPort);

        if (ssh2_auth_password($resConnection, $ftpUsername, $ftpPassword)) {

            // Initialize SFTP subsystem
            $resSFTP = ssh2_sftp($resConnection);

            // check if folder exist
            $folderExists = is_dir("ssh2.sftp://{$resSFTP}/" . $destPath);

            if (!$folderExists) {
                ssh2_sftp_mkdir($resSFTP, $destPath, true);
            }

            $resFile = fopen("ssh2.sftp://{$resSFTP}/" . $destFile, 'w');
            $srcFile = fopen($sourceFile, 'r');
            stream_copy_to_stream($srcFile, $resFile);
            fclose($resFile);
            fclose($srcFile);

        } else {
            throw new Exception('Unable to authenticate on server');
        }

        return false;
    }


    protected function zipFolder(string $instanceName, string $tempDir): string {
        $backupFolder = $this->config['sysDirectories']['backup'] . DIRECTORY_SEPARATOR . $instanceName;

        // Create Folder if not exists
        if (!is_dir($backupFolder)) {
            if (!mkdir($backupFolder, 0777, true)) {
                throw new Exception('Folder could not be created');
            }
        }

        $backupFolder = realpath($backupFolder);
        $fileName = date('Y-m-d-H-i-s_') . $instanceName . '.zip';

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($backupFolder . DIRECTORY_SEPARATOR . $fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($tempDir));

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();

        return $fileName;
    }
}