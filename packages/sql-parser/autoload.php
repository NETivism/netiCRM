<?php

$srcBaseDirectory = dirname(__FILE__);

$loader = new Psr4Autoloader();
$loader->register();
$loader->addNamespace('PhpMyAdmin\SqlParser', $srcBaseDirectory);
