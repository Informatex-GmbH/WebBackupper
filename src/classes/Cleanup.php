<?php

namespace ifmx\WebBackupper\classes;

class Cleanup {

    /**
     * cleans up local folder
     *
     * @param array $instances
     * @return bool
     * @throws \Exception
     */
    public static function localFolder(array $instances): bool {

        // loop through all instanceTypes
        foreach ($instances as $instanceType) {

            // loop through all instances
            if ($instanceType && is_array($instanceType)) {
                foreach ($instanceType as $instanceName => $instance) {

                    // looking for the backup dir
                    $backupDir = General::getBackupDir($instanceName);
                    if (is_dir($backupDir)) {

                        $files = scandir($backupDir);
                        $sortedFiles = [];

                        Logger::debug('start to clean up local backup folder from instance "' . $instanceName . '"');

                        // get create date from files for deletion
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..') {
                                $createDate = filemtime($backupDir . DIRECTORY_SEPARATOR . $file);
                                $sortedFiles[$createDate] = $file;
                            }
                        }

                        // limit for local backup copies
                        $limit = (int)General::getConfig('system, localBackupCopies') ?: 10;

                        // sort array by key
                        krsort($sortedFiles);
                        $filesForDelete = array_slice($sortedFiles, $limit);

                        // delete files
                        foreach ($filesForDelete as $file) {
                            if (unlink($backupDir . DIRECTORY_SEPARATOR . $file)) {
                                Logger::debug('file "' . $backupDir . DIRECTORY_SEPARATOR . $file . '" deleted');
                            } else {
                                Logger::warning('file "' . $backupDir . DIRECTORY_SEPARATOR . $file . '" could not be deleted');
                            }
                        }

                        Logger::info('local backup folder from instance "' . $instanceName . '" cleaned up successfully');
                    } else {
                        Logger::error('local backup folder from instance "' . $instanceName . '" does not exist');
                    }
                }
            }
        }

        return true;
    }
}
