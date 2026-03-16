<?php

$autoload = __DIR__ . '/../../../vendor/autoload.php';
if (file_exists($autoload)) {
    $loader = require_once $autoload;
    // Register test namespace for PSR-4 autoloading
    $loader->addPsr4('Horde\\Image\\', __DIR__);
}
