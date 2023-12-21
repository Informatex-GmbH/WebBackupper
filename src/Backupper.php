<?php

namespace ifmx\WebBackupper;

use ifmx\WebBackupper\classes;

require_once 'classes/FTP.php';
require_once 'classes/Logger.php';
require_once 'classes/General.php';
require_once 'classes/Cleanup.php';
require_once 'classes/backupper/Db.php';
require_once 'classes/backupper/Ftp.php';
require_once 'classes/backupper/Folder.php';
require_once 'classes/backupper/Webapp.php';
require_once 'classes/backupper/Wordpress.php';

class Backupper {

    /**
     * constructor
     *
     * @throws \Exception
     */
    public function __construct(array $config = null) {

        if (!$config || !is_array($config)) {
            throw new \Exception('No config given');
        }
        classes\General::$config = $config;

        classes\Logger::$debug = classes\General::getConfig('system, debug') ?: false;
        classes\Logger::$logFolder = classes\General::getLogDir();

        if (classes\General::getConfig('system, logToFile')) {
            classes\Logger::$logToFile = true;
        }

        date_default_timezone_set(classes\General::getConfig('system, timezone'));
    }


    /**
     * make backups from the given instances
     *
     * @param array $instances
     * @param array $ftpConfig
     * @param bool  $cleanUpLocalFolder
     * @param bool  $cleanUpRemoteFolder
     * @return array|false
     * @throws \Exception
     */
    public function createBackup(array $instances, array $ftpConfig = [], bool $cleanUpLocalFolder = true, bool $cleanUpRemoteFolder = true): false|array {
        try {

            $files = [];

            // backup wordpress instances
            if (array_key_exists('wordpress', $instances) && is_array($instances['wordpress'])) {
                $files['wordpress'] = classes\backupper\Wordpress::createBackup($instances['wordpress'], $ftpConfig);
            }

            // backup folders and database to one file
            if (array_key_exists('webapps', $instances) && is_array($instances['webapps'])) {
                $files['webapps'] = classes\backupper\Webapp::createBackup($instances['webapps'], $ftpConfig);
            }

            // backup databases
            if (array_key_exists('databases', $instances) && is_array($instances['databases'])) {
                $files['databases'] = classes\backupper\Db::createBackup($instances['databases'], $ftpConfig);
            }

            // backup directories
            if (array_key_exists('directories', $instances) && is_array($instances['directories'])) {
                $files['directories'] = classes\backupper\Folder::createBackup($instances['directories'], $ftpConfig);
            }

            // backup ftp
            if (array_key_exists('ftps', $instances) && is_array($instances['ftps'])) {
                $files['ftps'] = classes\backupper\Ftp::createBackup($instances['ftps'], $ftpConfig);
            }

            // cleanup local folder
            if ($cleanUpLocalFolder) {
                classes\Cleanup::localFolder($instances);
            }

            // cleanup remote folder
            if ($cleanUpRemoteFolder && $ftpConfig) {
                classes\Cleanup::remoteFolder($instances, $ftpConfig);
            }

            return $files;

        } catch (\Throwable $e) {

            $this->handleException($e);
        }
    }


    /**
     * Returns the Log
     *
     * @return array
     */
    public function getLog(): array {
        return classes\Logger::getLogAsArray();
    }


    /**
     * Returns the Log-String
     *
     * @return string
     */
    public function getLogString(): string {
        return classes\Logger::getLogAsString();
    }


    /**
     * Sends an email with the log
     *
     * @param string $toEmailAddress
     * @param string $log
     * @throws \Exception
     */
    public function sendLogMail(string $toEmailAddress, string $log): void {
        try {

            if (function_exists('mail')) {
                classes\Logger::debug('mail function is enabled');

                if ($toEmailAddress) {
                    $send = mail($toEmailAddress, 'WebBackupper', $log);

                    if ($send) {
                        classes\Logger::debug('mail successfully sent');
                    } else {
                        $error = error_get_last();
                        throw new \Exception($error['message']);
                    }
                } else {
                    classes\Logger::warning('no mail address given');
                }
            } else {
                classes\Logger::debug('mail function is not enabled');
            }
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }


    /**
     * Sets the callback function for the logger
     *
     * @param array $function array contains ['className', 'functionName', [array with args]]
     */
    public function setLogCallbackFunction(array $function): void {
        classes\Logger::setCallbackFunction($function);
    }


    // -------------------------------------------------------------------
    // Protected Functions
    // -------------------------------------------------------------------

    /**
     * Handles an Error
     *
     * @param $e
     * @throws \Exception
     */
    protected function handleException($e): void {

        // read message
        $msg = $e->getMessage();

        // log entry
        classes\Logger::error($msg, false);

        // get log folder
        $logDir = classes\Logger::$logFolder;

        // define exception file
        $file = $logDir . DIRECTORY_SEPARATOR . 'WebBackupperExceptions.log';

        // write exception to logfile
        if (!is_file($file)) {
            file_put_contents($file, '');
        }

        if ($file && is_writable($file)) {
            $errNo = (int)$e->getCode();
            $errStr = $e->getMessage();
            $errFile = $e->getFile();
            $errLine = $e->getLine();

            $message = date('d.m.Y H:i:s') . ' ';

            $message .= "Code: $errNo ";
            $message .= "File: $errFile ";
            $message .= "Row: $errLine ";
            $message .= $errStr . "\n";

            error_log($message, 3, $file);
        }

        throw $e;
    }
}
