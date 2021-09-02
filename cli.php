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

    // backup databases
    $databases = General::getConfig('databases');
    if (isset($databases) && is_array($databases)) {
        DbBackupper::createBackup();
    }

    // backup directories
    $directories= General::getConfig('directories');
    if (isset($directories) && is_array($directories)) {
        FolderBackupper::createBackup();
    }

    // backup wordpress instances
    $wpDirectories = General::getConfig('wpDirectories');
    if (isset($wpDirectories) && is_array($wpDirectories)) {
        WordpressBackupper::createBackup();
    }

    // backup folders and database to one file
    $webapps= General::getConfig('webapps');
    if (isset($webapps) && is_array($webapps)) {
        WebappBackupper::createBackup();
    }

    // Cleanup local folder
    Cleanup::localFolder();

    // write logfile
    $log = Logger::getLogAsString();
    writeMsgToLogFile($log);

    // send email to webmaster
    if (General::getConfig('system, sendLogEmail')) {
        $toEmailAddress = General::getConfig('system, webmasterEmailAddress');
        $send = mail($toEmailAddress, 'WebBackupper', $log);
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
 * @param string $log
 */
function writeMsgToLogFile(string $log): void {
    $file = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'log.txt';
    file_put_contents($file, $log, FILE_APPEND);
}
