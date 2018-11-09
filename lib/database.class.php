<?php
class DBConn extends SQLite3{
    function __construct(){
        $this->open(__DIR__.'/../data/mypidomo.db');
    }
}
?>
