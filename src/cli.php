<?php

// set working directory for cli
use ifmx\WebBackupper;
use ifmx\WebBackupper\classes;

chdir(dirname(__DIR__));

try {

    $config = require __DIR__ . '/../config/config.php';
    require 'Backupper.php';

// initialize backupper
    $backupper = new WebBackupper\Backupper($config);

// declare instances from config
    $instances = [];
    $instances['wordpress'] = classes\General::getConfig('wordpress');
    $instances['webapps'] = classes\General::getConfig('webapps');
    $instances['databases'] = classes\General::getConfig('databases');
    $instances['directories'] = classes\General::getConfig('directories');
    $instances['ftps'] = classes\General::getConfig('ftps');

    $ftpConfig = [];
    if (classes\General::getConfig('ftp, enabled')) {
        $ftpConfig = classes\General::getConfig('ftp, connections');
    }

// create backups
    $backupper->createBackup($instances, $ftpConfig);

// send email to webmaster
    if (classes\General::getConfig('system, sendLogEmail')) {

        // read email address of webmaster
        $toEmailAddress = classes\General::getConfig('system, webmasterEmailAddress');

        // read log
        $log = classes\Logger::getLogAsString();

        $backupper->sendLogMail($toEmailAddress, $log);
    }
} catch (Throwable $e) {
    echo $e->getMessage();

    throw $e;
}
