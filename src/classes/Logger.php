<?php

namespace ifmx\WebBackupper\classes;

class Logger {

    public static bool   $debug     = false;
    public static bool   $logToFile = false;
    public static string $logFolder = '';

    protected static array $logEntries       = [];
    protected static array $callbackFunction = []; // ['className', 'functionName', [array with args]]

    // -------------------------------------------------------------------
    // Public Functions
    // -------------------------------------------------------------------

    /**
     * adds a debug message to the log
     *
     * @param string $message
     */
    public static function debug(string $message): void {
        self::addLogEntry($message);
    }


    /**
     * adds an error message to the log
     *
     * @param string $message
     * @param bool   $throwError
     * @throws \Exception
     */
    public static function error(string $message, bool $throwError = true): void {
        self::addLogEntry($message, 'error');

        if ($throwError) {
            throw new \Exception($message);
        }
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
     * @param array $levels
     * @return array
     */
    public static function getLogAsArray(array $levels = []): array {
        $entries = [];

        if ($levels) {
            foreach ($levels as &$level) {
                $level = mb_strtolower($level);
            }
        }

        foreach (self::$logEntries as $entry) {

            // return entries only if in array and when is debug, check if debug mode is on
            if (in_array($entry->level, $levels) || (!$levels && ($entry->level !== 'debug' || self::$debug))) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }


    /**
     * returns log or a single entry as a formatted string
     *
     * @param array $levels
     * @return string
     */
    public static function getLogAsString(array $levels = []): string {
        $logString = '';

        if ($levels) {
            foreach ($levels as &$level) {
                $level = mb_strtolower($level);
            }
        }

        foreach (self::$logEntries as $entry) {

            // write entries only if in array and when is debug, check if debug mode is on
            if (in_array($entry->level, $levels) || (!$levels && ($entry->level !== 'debug' || self::$debug))) {
                $logString .= self::getEntryAsString($entry);
            }
        }

        return $logString;
    }


    /**
     * set a callback function witch is called every time a log entry ist written
     *
     * @param array $function
     */
    public static function setCallbackFunction(array $function): void {
        self::$callbackFunction = $function;
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

        $entry = new  \stdClass();
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

        // call callback function with args
        if (self::$callbackFunction) {
            $functionArray = self::$callbackFunction;

            $class = $functionArray[0];
            $function = $functionArray[1];

            $functionArgs = array_slice($functionArray, 2);
            $args = array_merge(['entry' => $entry], $functionArgs[0]);

            if (is_callable([$class, $function])) {
                call_user_func([$class, $function], $args);
            }
        }
    }


    /**
     * returns an entry as a log string
     *
     * @param \stdClass $entry
     * @return string
     */
    protected static function getEntryAsString(\stdClass $entry): string {
        return $entry->timestamp . "\t" . mb_strtoupper($entry->level) . "\t" . $entry->msg . "\n";
    }


    /**
     * writes a log entry to the logfile
     *
     * @param \stdClass $entry
     */
    protected static function writeToFile(\stdClass $entry): void {
        if (self::$logFolder) {

            // define log file
            $logFile = self::$logFolder . DIRECTORY_SEPARATOR . 'web_backupper_log.txt';

            // log debug entries to file only if debug mode is on or entry is not debug
            if ($entry->level !== 'debug' || self::$debug) {
                file_put_contents($logFile, self::getEntryAsString($entry), FILE_APPEND);
            }
        } else {
            self::warning('Logfile path not defined');
        }
    }
}
