<?php

$loader = new Psr4Autoloader();
$loader->register();
$loader->addNamespace('SPFLib', __DIR__);

$loader->addNamespace('IPLib', __DIR__.'/IPLib');
$loader->addNamespace('MLocati\IDNA', __DIR__.'/IDNA');
