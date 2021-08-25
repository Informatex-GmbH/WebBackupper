[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/lbuchs/WebAuthn/blob/master/LICENSE)
[![Requires PHP 7.4.0](https://img.shields.io/badge/PHP-7.4.0-green.svg)](https://php.net)

# WebBackupper
*A simple PHP WebBackupper for Wordpress Instances, databases and folders to a FTP/SFTP Server*

Goal of this project is to provide a small Web Backupper for backup webpages, or projects to a FTP/SFTP Server 

## Manual
### 1. Copy files to webserver
### 2. Copy config_sample.php and name it config.php
### 3. Edit config.php file 
1. Wordpress Instances (if not needed let array empty - ```'wpDirectories' => []```)
    ```
    'wpDirectories' => [
        
        // WP-Directory 1
        'TestWordpress' => '/home/var/www/site1',
        
        // WP-Directory 2
        'TestWordpress' => '/home/var/www/site2'
    ]
    ```
2. Webapps (database and folders) (if not needed let array empty - ```'webapps' => []```)
    ```
    'webapps' => [
   
        // Webapp 1
        'TestDb' => [
            'directories' => [
            
                // Folder
                '/home/var/www/folder',
                '/home/var/www/folder1'
            ],
   
            // Database informations
            'db' => [
                'name' => 'db_name',
                'host' => 'https://db.host.com',
                'port' => '3306', // optional
                'username' => 'username',
                'password' => 'password'
            ]
        ]
   ]
   ```
3. Databases (if not needed let array empty - ```'databases' => []```)
    ```
    'databases' => [
    
        // Database 1
        'TestDb' => [
            'name' => 'db_name',
            'host' => 'https://db.host.com',
            'port' => '3306', // optional
            'username' => 'username',
            'password' => 'password'
        ]
   ]
   ```
4. Directories (if not needed let array empty - ```'directories' => []```)
    ```
    'directories' => [
        
        // Folder 1
        'TestFolder' => '/home/var/www/folder'
    ]
   ```
5. System
    ```
    'system' => [
   
        // Local Backup Folder
        'backupDirectory' => 'backup',
        'sendSuccessMessage' => true    // echo a message when finished   
    ]
   ```
6. Paths
    ```
    'paths' => [
   
        // Path to mysqldump
        'mysqldump' => '/usr/local/bin'
   ]
   ```
7. FTP information
    ```
    'ftp' => [
        'isSftp' => true,
        'host' => 'sftp.mydomain.com',
        'port' => '22',
        'username' => 'backup',
        'password' => '5sZI^etC&',
        'folder' => 'backup/web/'
   ]
   ```

### 4. Create cli task
Create a cli task that runs cli.php with PHP7.4. Everytime the task is running, the backup job will be done.