<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\Storage\LocationDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Storage\LocationDirectory
 */
class LocationDirectoryTest extends TestCase
{
    public function testCanCreatedFromExistingDirectory()
    {
        $this->assertInstanceOf(
            LocationDirectory::class,
            LocationDirectory::createFromExistingPath(__DIR__)
        );
    }

    public function testFailWhenDirectoryNotExists()
    {
        $this->expectException(\RuntimeException::class);
        LocationDirectory::createFromExistingPath(__DIR__ . 'dingens');
    }

    public function testCanConvertedToString()
    {
        $directory = LocationDirectory::createFromExistingPath(__DIR__);
        $this->assertContains('tests/Storage', (string)$directory);
    }

    public function testPathHasAlwaysASlashAtTheEnd()
    {
        $directory = new LocationDirectory('path/without/slash/at/the/end');
        $this->assertContains(
            'path/without/slash/at/the/end/',
            (string)$directory
        );
    }
}
