<?php

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH.'/vendor/autoload.php';

spl_autoload_register(function ($class_name) {
    if(str_starts_with($class_name,'tests\\')) {
        $class_name = ltrim(str_replace('tests\\','tests\\src\\', $class_name), '/\\');
        $class_name = str_replace('\\', '/', $class_name);
        include BASE_PATH.'/'.$class_name.'.php';
    }
});