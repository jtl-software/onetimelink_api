<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 11.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\Authentication\AuthenticationInterface;
use JTL\Onetimelink\Storage\DatabaseStorage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Config
 *
 * @uses   \JTL\Onetimelink\Storage\DatabaseStorage
 * @uses   \JTL\Onetimelink\Storage\LocationDirectory
 */
class ConfigTest extends TestCase
{

    public function testCanReadLocationFromEnvironment()
    {
        $expectedPath = '/my/path/config.php';

        putenv("ENVIRONMENT_CONFIG_PATH=$expectedPath");
        $this->assertEquals($expectedPath, Config::getConfigPathFromEnvironment());
    }

    public function testCanReadDefaultConfig()
    {
        putenv("ENVIRONMENT_CONFIG_PATH=");
        $this->assertStringContainsString('config/config.dist.php', Config::getConfigPathFromEnvironment());
    }

    public function testCanCreateFileFromPath()
    {
        $this->assertInstanceOf(
            Config::class,
            Config::createFromFilePath(__DIR__ . '/config.unittest.php', false)
        );
    }

    public function testFailWhenConfigFileNotExists()
    {
        $this->expectException(\RuntimeException::class);
        $this->assertInstanceOf(
            Config::class,
            Config::createFromFilePath(__DIR__ . '/there-is-no-config.php', false)
        );
    }

    public function testFailWhenConfigIsNotArray()
    {
        $this->expectException(\RuntimeException::class);
        $this->assertInstanceOf(
            Config::class,
            Config::createFromFilePath(__DIR__ . '/config.invalid.php', false)
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanCreateRequest()
    {
        $config = Config::createFromFilePath(__DIR__ . '/config.unittest.php', false);

        $a = $config->createRequest();
        $this->assertInstanceOf(Request::class, $a);

        // run twice to test caching
        $b = $config->createRequest();
        $this->assertInstanceOf(Request::class, $b);

        $this->assertEquals($a, $b);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanCreateAuthenticationMethod()
    {
        $config = Config::createFromFilePath(__DIR__ . '/config.unittest.php', false);

        $a = $config->createAuthenticationMethod();
        $this->assertInstanceOf(AuthenticationInterface::class, $a);

        // run twice to test caching
        $b = $config->createAuthenticationMethod();
        $this->assertInstanceOf(AuthenticationInterface::class, $b);

        $this->assertEquals($a, $b);
    }

    public function testCanCreateStorage()
    {
        $config = Config::createFromFilePath(__DIR__ . '/config.unittest.php', false);

        $a = $config->createStorage();
        $this->assertInstanceOf(DatabaseStorage::class, $a);

        // run twice to test caching
        $b = $config->createStorage();
        $this->assertInstanceOf(DatabaseStorage::class, $b);

        $this->assertEquals($a, $b);
    }
}
