<?php

require_once __DIR__ . '/../../vendor/autoload.php';

echo \JTL\Onetimelink\PasswordHash::createHash($argv[1], $argv[2]) . "\n";



