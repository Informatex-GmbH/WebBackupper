<?php


class DbBackupper {

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
     * create databases backups foreach database in config
     * 
     * @return string
     * @throws Throwable
     */
    public function createBackup(): string {
        $log = '';
        $databases = $this->config['databases'];

        // loop databases in config
        foreach ($databases as $instanceName => $db) {

            // define backup folder name for instance
            $backupDir = $this->config['system']['backupDirectory'] . DIRECTORY_SEPARATOR . $instanceName;

            // create backup folder if not exists
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0777, true)) {
                    throw new Exception('Folder could not be created: ' . $backupDir);
                }
            }

            // create database dump
            $fileName = $this->createDbBackup($instanceName, $backupDir, $db['host'], $db['port'], $db['name'], $db['username'], $db['password']);

            // on success
            if ($fileName) {

                // set log msg
                $log .= date('d.m.Y H:i:s') . ' Database "' . $instanceName . '" backuped successfully' . "\n";

                // upload file to ftp server
                $ftp = new FTP($this->config);
                $uploaded = $ftp->upload($instanceName, $backupDir, $fileName);
                unset($ftp);

                if ($uploaded) {
                    $log .= date('d.m.Y H:i:s') . ' Database Backup "' . $instanceName . '" uploaded to FTP successfully' . "\n";
                } else {
                    $log .= date('d.m.Y H:i:s') . ' Database Backup "' . $instanceName . '" uploaded to FTP failed' . "\n";
                }
            } else {

                // set log msg
                $log .= date('d.m.Y H:i:s') . ' Database "' . $instanceName . '" backup failed' . "\n";
            }
        }

        return $log;
    }


    /**
     * creates a database dump
     *
     * @param string $instanceName
     * @param string $backupDir
     * @param string $dbHost
     * @param int|null $dbPort
     * @param string $dbName
     * @param string $dbUser
     * @param string $dbPassword
     * @return string
     * @throws Exception
     */
    public function createDbBackup(string $instanceName, string $backupDir, string $dbHost, int $dbPort = null, string $dbName, string $dbUser, string $dbPassword): string {

        // set path and filename
        $sqlName = $instanceName . '_DB_Backup_' . date('Y-m-d-H-i-s') . '.sql';
        $sqlPath = $backupDir . DIRECTORY_SEPARATOR . $sqlName;

        // check if a port is given
        if ($dbPort) {
            $dbPort = "\nport=" . $dbPort;

        // perhaps the port is attached to the hostname
        } else {
            preg_match('/(:\d+)/', $dbHost, $matches);
            if ($matches && $matches[1]) {
                $dbPort = "\nport=" . substr($matches[1], 1,);
                $dbHost = str_replace($matches[1], '', $dbHost);
            }
        }

        // define content for access file
        $fileContent = "[client]\nhost=$dbHost$dbPort\nuser=$dbUser\npassword=$dbPassword";

        // write temp access file
        if (!file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf', $fileContent)) {
            throw new Exception('DB-Access Datei konnte nicht erstellt werden');
        }

        // set variables for dump
        $variables = '--skip-opt --single-transaction --create-options --add-drop-table --set-charset --disable-keys --extended-insert --quick';

        // on localhost add other variable for testing
        if ($_SERVER['REMOTE_ADDR'] === '::1') {
            $variables .= ' --column-statistics=0';
        }

        // command for create databse dump
        $command = $this->config['paths']['mysqldump'] . DIRECTORY_SEPARATOR . 'mysqldump --defaults-file=' . $backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf ' . $variables . ' ' . $dbName . ' > ' . $sqlPath;

        // execute command
        $response = [];
        $status = false;
        exec($command, $response, $status);

        // remove temp access file
        unlink($backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf');

        // throw exception when failed
        if ($status) {
            throw new Exception('Create DB Dump failed');
        }

        // return filename
        return $sqlName;
    }
}