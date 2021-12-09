<?php

namespace ifmx\WebBackupper\classes;

class DbBackupper {

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * create databases backups foreach database in config
     *
     * @param array $instances
     * @param array $ftpConfig
     * @return array
     * @throws \Exception
     */
    public static function createBackup(array $instances = [], array $ftpConfig = []): array {
        $files = [];

        // loop databases in config
        foreach ($instances as $instanceName => $db) {
            Logger::debug('start backup database: ' . $instanceName);

            // define backup folder name for instance
            $backupDir = General::getBackupDir($instanceName);

            // create database dump
            $fileName = self::createDbBackup($instanceName, $backupDir, $db['host'], $db['port'], $db['name'], $db['username'], $db['password']);

            // on success
            if ($fileName) {
                $files[$instanceName] = $fileName;

                // set log msg
                Logger::info('Database "' . $instanceName . '" backuped successfully');

                // upload file to ftp server
                if ($ftpConfig) {
                    $uploaded = FTP::upload($instanceName, $backupDir, $fileName, $ftpConfig);

                    if ($uploaded) {
                        Logger::info('Database Backup "' . $instanceName . '" uploaded to FTP successfully');
                    } else {
                        Logger::warning('Database Backup "' . $instanceName . '" uploaded to FTP failed');
                    }
                }
            } else {

                // set log msg
                Logger::error('Database "' . $instanceName . '" backup failed');
            }
        }

        return $files;
    }


    /**
     * creates a database dump
     *
     * @param string   $instanceName
     * @param string   $backupDir
     * @param string   $dbHost
     * @param int|null $dbPort
     * @param string   $dbName
     * @param string   $dbUser
     * @param string   $dbPassword
     * @return string
     * @throws \Exception
     */
    public static function createDbBackup(string $instanceName, string $backupDir, string $dbHost, ?int $dbPort, string $dbName, string $dbUser, string $dbPassword): string {

        // set path and filename
        $sqlName = $instanceName . '_DB_Backup_' . date('Y-m-d-H-i-s') . '.sql';
        $sqlPath = $backupDir . DIRECTORY_SEPARATOR . $sqlName;

        // check if a port is given
        if ($dbPort) {
            $dbPort = $dbPort;

            // perhaps the port is attached to the hostname
        } else {
            preg_match('/(:\d+)/', $dbHost, $matches);
            if ($matches && $matches[1]) {
                $dbPort = substr($matches[1], 1,);
                $dbHost = str_replace($matches[1], '', $dbHost);
            }

            // default Port
            if (!$dbPort) {
                $dbPort = 3306;
            }
        }

        // define content for access file
        $fileContent = "[client]\nhost=$dbHost\nport=$dbPort\nuser=$dbUser\npassword=$dbPassword";

        Logger::debug('try to create db-access file for instance "' . $instanceName . '"');

        // write temp access file
        if (file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf', $fileContent)) {
            Logger::debug('successfully created db-access file for instance "' . $instanceName . '"');
        } else {
            Logger::error('db-access file for instance "' . $instanceName . '" could not be created');
        }

        // set variables for dump
        $variables = '--skip-opt --single-transaction --create-options --add-drop-table --set-charset --disable-keys --extended-insert --quick';

        // on localhost add other variable for testing
//        if ($_SERVER['REMOTE_ADDR'] === '::1') {
//           $variables .= ' --column-statistics=0';
//        }

        // command for create databse dump
        $command = General::getConfig('paths, mysqldump') . DIRECTORY_SEPARATOR . 'mysqldump --defaults-file="' . $backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf" ' . $variables . ' ' . $dbName . ' > "' . $sqlPath . '"';

        // execute command
        $response = [];
        $status = false;
        exec($command, $response, $status);

        Logger::debug('try to delete db-access file for instance "' . $instanceName . '"');

        // remove temp access file
        if (unlink($backupDir . DIRECTORY_SEPARATOR . 'dbAccess.conf')) {
            Logger::debug('successfully deleted db-access file for instance "' . $instanceName . '"');
        } else {
            Logger::warning('could not delete db-access file for instance "' . $instanceName . '"');
        }

        // log error when failed
        if ($status) {
            Logger::error('create DB-dump from instance "' . $instanceName . '" failed');

            return '';
        }

        // get file size
        $fileSize = General::getFileSize($sqlPath);

        // debug log
        Logger::debug('created DB-dump from instance "' . $instanceName . '". file size: ' . $fileSize);

        // return filename
        return $sqlName;
    }
}
