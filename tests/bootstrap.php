<?php declare(strict_types=1);

use Composer\Autoload\ClassLoader;

/** @var ClassLoader $loader */
$loader = require dirname(__DIR__, 4) . '/vendor/autoload.php';
$loader->addPsr4('Warexo\\', dirname(__DIR__) . '/src');
