<?php

$loader = new Psr4Autoloader();
$loader->register();
$loader->addNamespace('Nick\SecureSpreadsheet', __DIR__);
