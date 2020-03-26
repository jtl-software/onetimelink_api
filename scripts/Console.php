<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 23/04/18
 */

require __DIR__ . '/../vendor/autoload.php';

use JTL\Onetimelink\Config;
use JTL\Onetimelink\Factory;
use Monolog\Processor\UidProcessor;
use RedBeanPHP\R;
use Symfony\Component\Console\Application;

$factory = new Factory(
    Config::createFromFilePath(
        Config::getConfigPathFromEnvironment()
    ),
    new UidProcessor()
);

if ($factory->getConfig()->getTimeZone() !== null) {
    ini_set('date.timezone', $factory->getConfig()->getTimeZone());
}

$dataPath = $factory->getConfig()->getConsoleStoragePath();
$downloadsPath = $factory->getConfig()->getConsoleTempPath();
$userList = $factory->getConfig()->getUserListPath();

$app = new Application();
$app->add(new \JTL\Onetimelink\CLI\Command\GarbageCollectCommand($dataPath, $downloadsPath));
$app->add(new \JTL\Onetimelink\CLI\Command\DeleteResetHashesCommand($userList));

try {
    R::setup($factory->getConfig()->getConsoleDatabaseDSN());
    $app->run();
} catch (Exception $e) {
    throw new RuntimeException('Error while executing console.');
} finally {
    R::close();
}