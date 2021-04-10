<?php
require_once __DIR__ . '/vendor/autoload.php';

use rrzj\commom\Service;

$msg = Service::hello();
var_dump($msg);