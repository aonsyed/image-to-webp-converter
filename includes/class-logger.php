<?php

class Logger {
    private static $log_file;

    public static function init() {
        self::$log_file = plugin_dir_path(__FILE__) . '../logs/conversion_log.txt';
    }

    public static function log($message) {
        $date = date('Y-m-d H:i:s');
        $log_message = "[$date] $message\n";
        file_put_contents(self::$log_file, $log_message, FILE_APPEND);
    }
}

Logger::init();
