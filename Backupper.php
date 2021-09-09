<?php

require 'classes/FTP.php';
require 'classes/Logger.php';
require 'classes/General.php';
require 'classes/Cleanup.php';
require 'classes/DbBackupper.php';
require 'classes/FolderBackupper.php';
require 'classes/WebappBackupper.php';
require 'classes/WordpressBackupper.php';

class Backupper {

    /**
     * constructor
     *
     * @throws Exception
     */
    public function __construct(array $config = null) {

        if (!$config || !is_array($config)) {
            throw new Exception('No config given');
        }
        General::$config = $config;

        Logger::$debug = General::getConfig('system, debug');
        Logger::$logFolder = General::getLogDir();

        if (General::getConfig('system, logToFile')) {
            Logger::$logToFile = true;
        }

        date_default_timezone_set(General::getConfig('system, timezone'));
    }



    /**
     * make backups from the given instances
     *
     * @param array $instances
     */
    public function createBackup(array $instances) {
        try {

            // backup databases
            if (array_key_exists('databases', $instances) && is_array($instances['databases'])) {
                DbBackupper::createBackup($instances['databases']);
            }

            // backup directories
            if (array_key_exists('directories', $instances) && is_array($instances['directories'])) {
                FolderBackupper::createBackup($instances['directories']);
            }

            // backup wordpress instances
            if (array_key_exists('wpDirectories', $instances) && is_array($instances['wpDirectories'])) {
                WordpressBackupper::createBackup($instances['wpDirectories']);
            }

            // backup folders and database to one file
            if (array_key_exists('webapps', $instances) && is_array($instances['webapps'])) {
                WebappBackupper::createBackup($instances['webapps']);
            }

            // cleanup local folder
            Cleanup::localFolder();

        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }


    /**
     * Sends an email with the log
     *
     * @param string $toEmailAddress
     * @param string $log
     * @throws Exception
     */
    public function sendLogMail(string $toEmailAddress, string $log): void {
        try {

            if ($toEmailAddress) {
                $send = mail($toEmailAddress, 'WebBackupper', $log);

                if (!$send) {
                    $error = error_get_last();
                    throw new Exception($error['message']);
                }
            }
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }


    // -------------------------------------------------------------------
    // Protected Functions
    // -------------------------------------------------------------------

    /**
     * Handles an Error
     *
     * @param $e
     */
    protected function handleException($e) {

        // read message
        $msg = $e->getMessage();

        // log error
        Logger::error($msg);

        // get log folder
        $logDir = Logger::$logFolder;

        // define exception file
        $file = realpath(__DIR__) . DIRECTORY_SEPARATOR . $logDir . DIRECTORY_SEPARATOR . 'exceptions.txt';

        // write exception to logfile
        if (!is_file($file)) {
            file_put_contents($file, '');
        }

        if ($file && is_writable($file)) {
            $message = date('d.m.Y H:i:s') . ' ';
            $message .= 'Msg: ' . $msg . "\n";

            error_log($message, 3, $file);
        }
    }

}
