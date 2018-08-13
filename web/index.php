<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 01.08.17
 */

use JTL\Onetimelink\Config;
use JTL\Onetimelink\Exception\AuthenticationException;
use JTL\Onetimelink\Exception\DataNotFoundException;
use JTL\Onetimelink\Exception\InvalidRouteException;
use JTL\Onetimelink\Factory;
use RedBeanPHP\R;

require_once __DIR__ . '/../vendor/autoload.php';

enableCors();

try {
    $factory = new Factory(
        Config::createFromFilePath(
            Config::getConfigPathFromEnvironment()
        )
    );
    $config = $factory->getConfig();

    if ($config->getTimeZone() !== null) {
        ini_set('date.timezone', $config->getTimeZone());
    }

    R::setup($config->getDatabaseDSN());
    $controller = $factory->createController();
    echo $controller->run()->sendResponse();
    R::close();
} catch (InvalidRouteException $ire) {
    error_log($ire->getMessage());
    http_response_code(400);

} catch (InvalidArgumentException $iae) {
    error_log($iae->getMessage());
    header('X-Error: ' . $iae->getMessage());
    http_response_code(400);

} catch (DataNotFoundException $dnfe) {
    header('X-OTL-Message: ' . $dnfe->getMessage());
    http_response_code(404);

} catch (AuthenticationException $ae) {
    header('X-Error: ' . $ae->getMessage());
    http_response_code(403);

} catch (Exception $e) {
    header('X-Error: Unknown'.$e->getMessage());
    error_log($e->getMessage());
    http_response_code(500);

    $factory->createLogger()->error("Exception: " . $e->getMessage());
}

function enableCors()
{

    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Expose-Headers: X-Error');
        header('Access-Control-Max-Age: 3600');    // cache for 1 hour
        header("Content-Security-Policy: connect-src 'self' https://{$_SERVER['HTTP_HOST']}");
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) // may also be using PUT, PATCH, HEAD etc
        {
            header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
        }

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }

        exit(0);
    }
}
