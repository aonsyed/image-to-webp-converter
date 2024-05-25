<?php

class Logger {
    private static $log_file;

    public static function init() {
        $log_dir = plugin_dir_path(__FILE__) . '../logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        self::$log_file = $log_dir . '/conversion.log';
        if (!file_exists(self::$log_file)) {
            touch(self::$log_file);
        }
    }

    public static function log($message) {
        $date = date('Y-m-d H:i:s');
        $log_message = "[$date] $message\n";
        file_put_contents(self::$log_file, $log_message, FILE_APPEND);
    }
}

Logger::init();
