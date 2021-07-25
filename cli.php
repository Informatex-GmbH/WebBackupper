<?php
$config = null;

require 'config/config.php';
require 'classes/FTP.php';
require 'classes/DbBackupper.php';
require 'classes/FolderBackupper.php';
require 'classes/WebappBackupper.php';
require 'classes/WordpressBackupper.php';

// check config
if (!isset($config) || !is_array($config)) {
    throw new Exception('could not read config');
}

try {
    $log = '';

    // backup databases
    if (isset($config['databases']) && is_array($config['databases'])) {
        $dbBackuper = new DbBackupper($config);
        $result = $dbBackuper->createBackup();
        unset($dbBackuper);

        // write result to logfile
        $log .= $result;
        writeMsgToLogFile($result);
    }

    // backup directories
    if (isset($config['directories']) && is_array($config['directories'])) {
        $folderBackuper = new FolderBackupper($config);
        $result = $folderBackuper->createBackup();
        unset($folderBackuper);

        // write result to logfile
        $log .= $result;
        writeMsgToLogFile($result);
    }

    // backup wordpress instances
    if (isset($config['wpDirectories']) && is_array($config['wpDirectories'])) {
        $wpBackuper = new WordpressBackupper($config);
        $result = $wpBackuper->createBackup();
        unset($wpBackuper);

        // write result to logfile
        $log .= $result;
        writeMsgToLogFile($result);
    }

    // backup folders and database to one file
    if (isset($config['webapps']) && is_array($config['webapps'])) {
        $webappBackuper = new WebappBackupper($config);
        $result = $webappBackuper->createBackup();
        unset($webappBackuper);

        // write result to logfile
        $log .= $result;
        writeMsgToLogFile($result);
    }

    // echo msg will generate an crontab mail to web administrator
    if ($config['system']['sendSuccessMessage']) {
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
