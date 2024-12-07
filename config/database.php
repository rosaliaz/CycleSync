<?php
    //meta data
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Methods: POST, GET, PATCH");
    header("Access-Control-Max-Age: 3600");
    date_default_timezone_set("Asia/Manila");

    define("SERVER", "localhost");
    define("DATABASE", "cycle_sync");
    define("USER", "root");
    define("PASSWORD", "");

    class Connection{
        protected $connectionString = "mysql:host=" . SERVER . ";dbname=" .DATABASE. ";charset=utf8";
        protected $options = [
            \PDO::ATTR_ERRMODE =>\PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false
        ];

        public function connect(){
            return new \PDO($this->connectionString,USER,PASSWORD,$this->options);
        }
    }

?>