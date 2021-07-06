<?php

// Debug mode
$debug_mode = true;

$config = [

    // ------------------------------------------
    // Directories for backup
    // 'Name of Instance' => 'Path to Directory'
    // ------------------------------------------
    'wpDirectories' => [
        // WP-Directory 1
        'TestWordpress' => '/home/var/www/site1',
        // WP-Directory 2
        'TestWordpress' => '/home/var/www/site2'
    ],

    // ------------------------------------------
    // Directory
    // -----------------------------------------
    'sysDirectories' => [
        'backup' => 'backup'
    ],

    // ------------------------------------------
    // Paths
    // ------------------------------------------
    'paths' => [
        // Path to mysqldump
        'mysqldump' => '/usr/local/bin'
    ],

    // ------------------------------------------
    // FTP
    // -----------------------------------------
    'ftp' => [
        'isSftp' => true,
        'host' => 'sftp.mydomain.com',
        'port' => '22',
        'username' => 'backup',
        'password' => '5sZI^etC&',
        'folder' => 'backup/web/'
    ]
];
