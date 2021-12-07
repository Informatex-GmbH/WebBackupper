<?php

// Debug mode
$debug_mode = true;

$config = [

    // ------------------------------------------
    // Wordpress Instances for backup
    // 'Name for Backupfile' => 'Path to Directory'
    // ------------------------------------------
    'wordpress' => [
        // WP-Directory 1 with default wp-content folder
        'TestWordpress' => '/home/var/www/site1',
        // WP-Directory 2 with custom folders
        'TestWordpress' => [
            'rootDirectory' => '/home/var/www/site2',
            'directories' => [
                // Folder 1
                'wp-data',
                // Folder 2
                'wp-admin'
            ],
        ]
    ],

    // ------------------------------------------
    // Folder and DB Backup for backup
    // 'Name for Backupfile' => 'Infos'
    // ------------------------------------------
    'webapps' => [
        // Webapp 1
        'TestDb' => [
            'directories' => [
                // Folder
                '/home/var/www/folder',
                '/home/var/www/folder1'
            ],
            'db' => [
                'name' => 'db_name',
                'host' => 'https://db.host.com',
                'port' => '3306', // optional
                'username' => 'username',
                'password' => 'password'
            ]
        ]
    ],

    // ------------------------------------------
    // Database for backup
    // 'Name for Backupfile' => [Login Infos]
    // ------------------------------------------
    'databases' => [
        // DB 1
        'TestDb' => [
            'name' => 'db_name',
            'host' => 'https://db.host.com',
            'port' => '3306', // optional
            'username' => 'username',
            'password' => 'password'
        ]
    ],

    // ------------------------------------------
    // Folder for backup
    // 'Name for Backupfile' => 'Path to Directory'
    // ------------------------------------------
    'directories' => [
        // one folder
        'TestFolder' => '/home/var/www/folder',
        // multiple folders
        'TestMultipleFolders' => [
            '/home/var/www/folder1',
            '/home/var/www/folder2'
        ]
    ],

    // ------------------------------------------
    // System
    // ------------------------------------------
    'system' => [
        'debug' => $debug_mode,        // is debug mode on
        'localBackupCopies' => 10,     // number of local backups before delete
        'timezone' => 'Europe/Zurich', // timezone
        'logToFile' => true,           // write log to file
        'sendLogEmail' => true,        // send email to webmaster
        'webmasterEmailAddress' => 'webmaster@mydomain.com'
    ],

    // ------------------------------------------
    // Directory
    // -----------------------------------------
    'sysDirectories' => [
        'backup' => 'backup', // path to backup folder
        'log' => 'log'        // path to log folder
    ],

    // ------------------------------------------
    // Paths
    // ------------------------------------------
    'paths' => [
        'mysqldump' => '/usr/local/bin' // Path to mysqldump
    ],

    // ------------------------------------------
    // FTP
    // -----------------------------------------
    'ftp' => [
        'enabled' => false,
        'connections' => [
            'NAS' => [
                'isSftp' => true,
                'host' => 'sftp.mydomain.com',
                'port' => '22',
                'username' => 'backup',
                'password' => '***',
                'path' => 'backup/web/'
            ]
        ]
    ]
];
