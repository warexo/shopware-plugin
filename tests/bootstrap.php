<?php declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$autoloaders = [
    dirname(__DIR__, 4) . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (!is_file($autoloader)) {
        continue;
    }

    /** @var ClassLoader $loader */
    $loader = require $autoloader;
    $loader->addPsr4('Warexo\\', dirname(__DIR__) . '/src');
    $loader->addPsr4('Warexo\\Tests\\', __DIR__);

    return;
}

throw new RuntimeException('Unable to locate the Composer autoloader.');
