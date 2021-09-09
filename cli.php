<?php

// set working directory for cli
chdir(dirname(__FILE__));

$config = [];

require 'Backupper.php';
require 'config/config.php';

// initialize backupper
$backupper = new Backupper($config);

// declare instances from config
$instances = [];
$instances['databases'] = General::getConfig('databases');
$instances['directories'] = General::getConfig('directories');
$instances['wpDirectories'] = General::getConfig('wpDirectories');
$instances['webapps'] = General::getConfig('webapps');

// create backups
$backupper->createBackup($instances);

// send email to webmaster
if (General::getConfig('system, sendLogEmail')) {

    // read email address of webmaster
    $toEmailAddress = General::getConfig('system, webmasterEmailAddress');

    // read log
    $log = Logger::getLogAsString();

    $backupper->sendLogMail($toEmailAddress, $log);
}
