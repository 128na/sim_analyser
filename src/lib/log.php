<?php

class Log {
    public static $filename;
    private static $init_called = false;

    public static function debug($message, $show = true) {
        static::write('debug', $message, $show);
    }
    public static function info($message, $show = false) {
        static::write('info', $message, $show);
    }
    public static function warn($message, $show = true) {
        static::write('warn', $message, $show);
    }
    public static function error($message, $show = true) {
        static::write('error', $message, $show);
    }

    public static function write($type = 'debug', $message, $show = false) {
        if (!static::$init_called){
            static::init();
        }
        if ($show) {
            echo "$message\n";
        }

        file_put_contents(static::$filename, static::gene_message($type, $message), FILE_APPEND);
    }


    public static function init($message = null) {
        $time = static::time();

        static::$filename = "log-$time.txt";
        file_put_contents(static::$filename, static::gene_message('init', $message));
        static::$init_called = true;
    }

    private static function gene_message($type, $message) {
    $time = static::time();
        if ($message) {
            $message = "$time - $type - $message";

            //行端に開業がなければ改行を追加する
            if (!preg_match('/\n$/', $message)) {
                $message .= "\n";
            }
        }
        return $message;
    }
    private static function time() {
        $time = new DateTime();
        return $time->format('Y-M-d-His');
    }
}