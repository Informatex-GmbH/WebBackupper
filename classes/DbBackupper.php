<?php


class DbBackupper {

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

    public function createBackup() {
        $databases = $this->config['databases'];

        foreach ($databases as $instanceName => $db) {

            $backupDir = $this->config['system']['backupDirectory'] . DIRECTORY_SEPARATOR . $instanceName;

            // Create Backup Folder if not exists
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0777, true)) {
                    throw new Exception('Folder could not be created: ' . $backupDir);
                }
            }

            $fileName = $this->createDbBackup($instanceName, $backupDir, $db['host'], $db['port'], $db['name'], $db['username'], $db['password']);

            $this->log .= date('d.m.Y H:i:s') . ' Database "' . $instanceName . '" successfully backuped' . "\n";

            $ftp = new FTP($this->config);
            $uploaded = $ftp->upload($instanceName, $backupDir, $fileName);
            unset($ftp);

            if ($uploaded) {
                $this->log .= date('d.m.Y H:i:s') . ' Database Backup "' . $instanceName . '" successfully uploaded to FTP' . "\n";
            }
        }
    }


    public function createDbBackup(string $instanceName, string $backupDir, string $dbHost, int $dbPort = null, string $dbName, string $dbUser, string $dbPassword): string {

        // SQL Daten definieren
        $sqlName = $instanceName . '_DB_Backup_' . date('Y-m-d-H-i-s') . '.sql';
        $sqlPath = $backupDir . DIRECTORY_SEPARATOR . $sqlName;

        if ($dbPort) {
            $dbPort = "\nport=" . $dbPort;
        } else {
            // Check if port on host
            preg_match('/(:\d+)/', $dbHost, $matches);
            if ($matches && $matches[1]) {
                $dbPort = "\nport=" . substr($matches[1], 1,);
                $dbHost = str_replace($matches[1], '', $dbHost);
            }
        }

        // Content for Access File
        $fileContent = "[client]\nhost=$dbHost$dbPort\nuser=$dbUser\npassword=$dbPassword";

        // Access File schreiben
        if (!file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf', $fileContent)) {
            throw new Exception('DB-Access Datei konnte nicht erstellt werden');
        }

        $variables = '--skip-opt --single-transaction --create-options --add-drop-table --set-charset --disable-keys --extended-insert --quick';

        if ($_SERVER['REMOTE_ADDR'] === '::1') {
            $variables .= ' --column-statistics=0';
        }

        // Create DB-Dump
        $command = $this->config['paths']['mysqldump'] . DIRECTORY_SEPARATOR . 'mysqldump --defaults-file=' . $backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf ' . $variables . ' ' . $dbName . ' > ' . $sqlPath;

        // Befehl ausführen
        $response = [];
        $status = false;
        exec($command, $response, $status);

        unlink($backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf');

        // Fehler ausgeben
        if ($status) {
            throw new Exception('Create DB Dump failed');
        }

        return $sqlName;
    }
}