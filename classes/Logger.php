<?php

class Logger {

    public static bool $debug = false;
    public static bool $logToFile = false;
    public static string $logFolder = '';

    protected static array $logEntries = [];

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * adds a debug message to the log
     *
     * @param string $message
     */
    public static function debug(string $message): void {
        self::addLogEntry($message, 'debug');
    }


    /**
     * adds an error message to the log
     *
     * @param string $message
     */
    public static function error(string $message): void {
        self::addLogEntry($message, 'error');
    }


    /**
     * adds an info message to the log
     *
     * @param string $message
     */
    public static function info(string $message): void {
        self::addLogEntry($message, 'info');
    }


    /**
     * adds a warning message to the log
     *
     * @param string $message
     */
    public static function warning(string $message): void {
        self::addLogEntry($message, 'warning');
    }


    /**
     * returns log as an array
     *
     * @return array
     */
    public static function getLogAsArray(): array {
        return self::$logEntries;
    }


    /**
     * returns log or a single entry as a formatted string
     *
     * @param object|null $entry
     * @return string
     */
    public static function getLogAsString(?object $entry = null): string {
        $logString = '';
        $entries = self::$logEntries;

        // format only one entry as string
        if ($entry) {
            $entries = [$entry];
        }

        foreach ($entries as $entry) {

            // log debug entries only if debug mode is on or entry is not debug
            if ($entry->level !== 'debug' || self::$debug) {
                $logString .= $entry->timestamp . "\t" . strtoupper($entry->level) . "\t" . $entry->msg . "\n";
            }
        }

        return $logString;
    }


    // -------------------------------------------------------------------
    // Protected Functions
    // -------------------------------------------------------------------

    /**
     * adds a log entry to the log-array
     *
     * @param string $message
     * @param string $level
     */
    protected static function addLogEntry(string $message, string $level = 'debug'): void {

        // get trace
        $bt = debug_backtrace();

        $trace = [];
        $trace['file'] = $bt[1]['file'];
        $trace['line'] = $bt[1]['line'];

        $entry = new stdClass();
        $entry->msg = $message;
        $entry->level = $level;
        $entry->timestamp = date('d.m.Y H:i:s');
        $entry->trace = $trace;

        // add entry to log array
        self::$logEntries[] = $entry;

        // log entry to file
        if (self::$logToFile) {
            self::writeToFile($entry);
        }
    }


    /**
     * writes one log entry to the logfile
     *
     * @param object $entry
     */
    protected static function writeToFile(object $entry) {
        if (self::$logToFile) {

            // define log file
            $logFile = self::$logFolder . DIRECTORY_SEPARATOR . 'log.txt';

            // log debug entries to file only if debug mode is on or entry is not debug
            if ($entry->level !== 'debug' || self::$debug) {
                file_put_contents($logFile, self::getLogAsString($entry), FILE_APPEND);
            }
        } else {
            self::warning('Logfile path not defined');
        }
    }
}
