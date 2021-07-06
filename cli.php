<?php
$config = null;

require 'config/config.php';
require 'Backup.php';


// Check Config
if (!isset($config) || !is_array($config)) {
    throw new Exception('Could not read Config');
}

// Check given Directories
if (!isset($config['wpDirectories']) || !is_array($config['wpDirectories'])) {
    throw new Exception('No Wordpress Directories in Config');
}

try {
    $backup = new Backup($config);
    $result = $backup->createBackup();
} catch (Throwable $e) {
    $msg = $e->getMessage() . "\n";
    file_put_contents('log.txt', $msg, FILE_APPEND);
}
