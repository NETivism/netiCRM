<?php

namespace IDS\Autoloader;

require_once 'Psr4Autoloader.php';

/**
 * @var string $srcBaseDirectory
 * Full path to "src/Spout" which is what we want "Box\Spout" to map to.
 */
$srcBaseDirectory = dirname(__FILE__);

$loader = new Psr4Autoloader();
$loader->register();
$loader->addNamespace('IDS', $srcBaseDirectory);
