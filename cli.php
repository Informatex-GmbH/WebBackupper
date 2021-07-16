<?php
$config = null;

require 'config/config.php';
require 'backupper/FTP.php';
require 'backupper/DbBackupper.php';
require 'backupper/FolderBackupper.php';
require 'backupper/WebappBackupper.php';
require 'backupper/WpBackupper.php';

// Check Config
if (!isset($config) || !is_array($config)) {
    throw new Exception('Could not read Config');
}

try {

    $log = '';

    if (isset($config['wpDirectories']) && is_array($config['wpDirectories'])) {
        $wpBackuper = new WpBackupper($config, $log);
        $result = $wpBackuper->createBackup();
        unset($wpBackuper);
    }

    if (isset($config['dbs']) && is_array($config['dbs'])) {
        $dbBackuper = new DbBackupper($config, $log);
        $result = $dbBackuper->createBackup();
        unset($dbBackuper);
    }

    if (isset($config['directories']) && is_array($config['directories'])) {
        $folderBackuper = new FolderBackupper($config, $log);
        $result = $folderBackuper->createBackup();
        unset($folderBackuper);
    }

    if (isset($config['webapps']) && is_array($config['webapps'])) {
        $webappBackuper = new WebappBackupper($config, $log);
        $result = $webappBackuper->createBackup();
        unset($webappBackuper);
    }

    // write log file
    $file = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'log.txt';
    file_put_contents($file, $log, FILE_APPEND);

    if ($config['system']['sendSuccessMessage']) {
        var_dump($log);
    }

} catch (Throwable $e) {
    $file = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'exceptions.txt';
    $msg = $e->getMessage();

    if (!is_file($file)) {
        file_put_contents($file, '');
    }

    if ($file && is_writable($file)) {
        $message = date("d.m.Y H:i:s") . " ";
        $message .= "Msg:" . $msg . "\n";

        error_log($message, 3, $file);
    }
}
