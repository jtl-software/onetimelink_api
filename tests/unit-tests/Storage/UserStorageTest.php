<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 12.04.18
 */

namespace JTL\Onetimelink\Storage;

use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Storage\UserStorage
 */
class UserStorageTest extends TestCase
{

    public function testCanWrite()
    {
        $storage = new UserStorage('/tmp/userstorage.json');
        $this->assertTrue($storage->write(['foo' => 'bar']));
    }

    public function testCanRead()
    {
        $storage = new UserStorage('/tmp/userstorage.json');
        $this->assertTrue($storage->write(['foo' => 'bar']));

        $this->assertEquals(['foo' => 'bar'], $storage->read());
    }

    public function testFailWhenWriteInvalidFile()
    {
        $storage = new UserStorage('/home/root/should-fail');

        $this->assertFalse(@$storage->write(['foo' => 'bar']));

        $this->expectException(\RuntimeException::class);
        $storage->read();
    }
}
