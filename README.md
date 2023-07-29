[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/lbuchs/WebAuthn/blob/master/LICENSE)
[![Requires PHP 7.4.0](https://img.shields.io/badge/PHP-7.4.0-green.svg)](https://php.net)

# WebBackupper
*A simple PHP WebBackupper for Wordpress Instances, databases, folders and FTP/SFTP-Folders local and to a FTP/SFTP Server*

Goal of this project is to provide a small Web Backupper for backup webpages, or projects to a FTP/SFTP Server 

## Manual
### 1. Copy files to webserver
### 2. Copy ```config_sample.php``` and rename it to ```config.php```
### 3. Edit ```config.php``` file 
1. Wordpress Instances (if not needed let array empty - ```'wordpress' => []```)
    ```
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
        // one folder
        'TestFolder' => '/home/var/www/folder',
        // multiple folders
        'TestMultipleFolders' => [
            '/home/var/www/folder1',
            '/home/var/www/folder2'
        ]
    ]
   ```
5. FTP-Files (if not needed let array empty - ```'ftps' => []```)
    ```
    'ftps' => [
   
        // FTP-Config 1
        'TestFtp' => [
            'isSftp' => false,
            'host' => 'sftp.mydomain.com',
            'port' => '21',
            'username' => 'backup',
            'password' => '***',
            'path' => 'my/folder/'
        ]
    ]
   ```
6. System
    ```
    'system' => [
        'debug' => $debug_mode,        // is debug mode on
        'localBackupCopies' => 10,     // number of local backups before delete
        'timezone' => 'Europe/Zurich', // timezone
        'logToFile' => true,           // write log to file
        'sendLogEmail' => true,        // send email to webmaster
        'webmasterEmailAddress' => 'webmaster@mydomain.com'
    ]
   ```
7. Systemdirectorys
    ```
    'sysDirectories' => [
        'backup' => 'backup', // path to backup folder
        'log' => 'log'        // path to log folder
    ],
   ```
8. Paths
    ```
    'paths' => [
        'mysqldump' => '/usr/local/bin' // Path to mysqldump
   ]
   ```
9. FTP-Upload Settings
   1. Only one FTP configuration
       ```
       'backupFtp' => [
           'enabled' => false,
           'connections' => [
               'isSftp' => true,
               'host' => 'sftp.mydomain.com',
               'port' => '22',
               'username' => 'backup',
               'password' => '***',
               'path' => 'backup/web/'
           ]
       ]
      ```
   2. Multiple FTP configurations
       ```
       'backupFtp' => [
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
      ```

### 4. Create cli task
Create a cli task that runs cli.php with PHP8.2. Everytime the task is running, the backup job will be done.
