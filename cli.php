<?php

require 'classes/FTP.php';
require 'classes/General.php';
require 'classes/Cleanup.php';
require 'classes/DbBackupper.php';
require 'classes/FolderBackupper.php';
require 'classes/WebappBackupper.php';
require 'classes/WordpressBackupper.php';

try {
    $log = '';

    // backup databases
    $databases = General::getConfig('databases');
    if (isset($databases) && is_array($databases)) {
        $dbBackuper = new DbBackupper();
        $result = $dbBackuper->createBackup();
        unset($dbBackuper);

        // write result to logfile
        $log .= $result;
        writeMsgToLogFile($result);
    }

    // backup directories
    $directories= General::getConfig('directories');
    if (isset($directories) && is_array($directories)) {
        $result = FolderBackupper::createBackup();

        // write result to logfile
        $log .= $result;
        writeMsgToLogFile($result);
    }

    // backup wordpress instances
    $wpDirectories = General::getConfig('wpDirectories');
    if (isset($wpDirectories) && is_array($wpDirectories)) {
        $result = WordpressBackupper::createBackup();

        // write result to logfile
        $log .= $result;
        writeMsgToLogFile($result);
    }

    // backup folders and database to one file
    $webapps= General::getConfig('webapps');
    if (isset($webapps) && is_array($webapps)) {
        $result = WebappBackupper::createBackup();

        // write result to logfile
        $log .= $result;
        writeMsgToLogFile($result);
    }

    $result = Cleanup::localFolder();

    // write result to logfile
    $log .= $result;


    // echo msg will generate an crontab mail to web administrator
    if (General::getConfig('system, sendSuccessMessage')) {
        echo $log;
    }

} catch (Throwable $e) {
    $file = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'exceptions.txt';
    $msg = $e->getMessage();

    // echo msg will generate an crontab mail to web administrator
    echo $msg;

    // write exception to logfile
    if (!is_file($file)) {
        file_put_contents($file, '');
    }

    if ($file && is_writable($file)) {
        $message = date("d.m.Y H:i:s") . " ";
        $message .= "Msg:" . $msg . "\n";

        error_log($message, 3, $file);
    }
}

/**
 * write logfile
 *
 * @param $msg
 */
function writeMsgToLogFile($msg): void {
    $file = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'log.txt';
    file_put_contents($file, $msg, FILE_APPEND);
}
