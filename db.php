<?php
final class db {
    private static $id;
    static function send($sql) {return mysqli_query(db::$id, $sql);}
    static function fetch($resource) {
        if ($resource) {return(mysqli_fetch_assoc($resource));}
        echo mysqli_error(db::$id);
        return null;
    }
    static function quick_fetch($sql) {
        $res = mysqli_query(db::$id, $sql);
        if ($res) {return mysqli_fetch_assoc(($res));}
        echo $sql . '<br>' . mysqli_error(db::$id);
        return null;
    }
    static function connect() {
        db::$id = mysqli_connect('localhost', 'root', '', 'cardgame');
        if (!db::$id) {echo mysqli_error(db::$id);}
    }
    static function disconnect() {mysqli_close(db::$id);}
    private function __construct() {}
} db::connect();
