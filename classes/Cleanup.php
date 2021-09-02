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

                // get crete date from file for delete
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $createDate = filectime($backupDir . DIRECTORY_SEPARATOR . $file);
                        $sortedFiles[$createDate] = $file;
                    }
                }

                // sort array by key
                ksort($sortedFiles);
                $filesForDelete = array_slice($sortedFiles, (int)General::getConfig('system, localBackupCopies'));

                // delete files
                foreach ($filesForDelete as $file) {
                    if (unlink($backupDir . DIRECTORY_SEPARATOR . $file)) {
                        Logger::debug('file "' . $backupDir . DIRECTORY_SEPARATOR . $file . '" deleted');
                    } else {
                        Logger::debug('file "' . $backupDir . DIRECTORY_SEPARATOR . $file . '" could not be deleted');
                    }
                }

                Logger::info('Local backupfolder from instance "' . $instanceName . '" cleaned up successfully');
            }
        }

        return $log;
    }
}