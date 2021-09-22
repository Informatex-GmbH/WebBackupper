<?php


class Cleanup {
    
    /**
     * cleans up local folder
     * 
     * @return string
     * @throws Throwable
     */
    public static function localFolder(): string {
        $log = '';
        $instanceNames = General::getInstanceNames();

        foreach ($instanceNames as $instanceName) {

            $backupDir = General::getBackupDir($instanceName);
            if (is_dir($backupDir)) {
                $files = scandir($backupDir);
                $sortedFiles = [];

                Logger::info('start to clean up local backup folder from instance "' . $instanceName . '"');

                // get create date from files for deletion
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $createDate = filemtime($backupDir . DIRECTORY_SEPARATOR . $file);
                        $sortedFiles[$createDate] = $file;
                    }
                }

                // sort array by key
                krsort($sortedFiles);
                $filesForDelete = array_slice($sortedFiles, (int)General::getConfig('system, localBackupCopies'));

                // delete files
                foreach ($filesForDelete as $file) {
                    if (unlink($backupDir . DIRECTORY_SEPARATOR . $file)) {
                        Logger::debug('file "' . $backupDir . DIRECTORY_SEPARATOR . $file . '" deleted');
                    } else {
                        Logger::warning('file "' . $backupDir . DIRECTORY_SEPARATOR . $file . '" could not be deleted');
                    }
                }

                Logger::info('local backup folder from instance "' . $instanceName . '" cleaned up successfully');
            }
        }

        return $log;
    }
}
