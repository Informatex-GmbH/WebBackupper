<?php
// set working directory for cli
chdir(dirname(__FILE__));

require 'classes/FTP.php';
require 'classes/Logger.php';
require 'classes/General.php';
require 'classes/Cleanup.php';
require 'classes/DbBackupper.php';
require 'classes/FolderBackupper.php';
require 'classes/WebappBackupper.php';
require 'classes/WordpressBackupper.php';

try {
    date_default_timezone_set(General::getConfig('system, timezone'));

    Logger::$debug = General::getConfig('system, debug');

    if (General::getConfig('system, logToFile')) {
        Logger::$logToFile = true;
        Logger::$logFolder = General::getConfig('sysDirectories, log');
    }

    // backup databases
    $databases = General::getConfig('databases');
    if (isset($databases) && is_array($databases)) {
        DbBackupper::createBackup();
    }

    // backup directories
    $directories = General::getConfig('directories');
    if (isset($directories) && is_array($directories)) {
        FolderBackupper::createBackup();
    }

    // backup wordpress instances
    $wpDirectories = General::getConfig('wpDirectories');
    if (isset($wpDirectories) && is_array($wpDirectories)) {
        WordpressBackupper::createBackup();
    }

    // backup folders and database to one file
    $webapps = General::getConfig('webapps');
    if (isset($webapps) && is_array($webapps)) {
        WebappBackupper::createBackup();
    }

    // Cleanup local folder
    Cleanup::localFolder();

    // write logfile
    $log = Logger::getLogAsString();

    // send email to webmaster
    if (General::getConfig('system, sendLogEmail')) {
        $toEmailAddress = General::getConfig('system, webmasterEmailAddress');
        $send = mail($toEmailAddress, 'WebBackupper', $log);

        if (!$send) {
            $error = error_get_last();
            throw new Exception($error['message']);
        }
    }

} catch (Throwable $e) {

    // read message
    $msg = $e->getMessage();

    // log error
    Logger::error($msg);

    // define exception file
    $file = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'exceptions.txt';

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
