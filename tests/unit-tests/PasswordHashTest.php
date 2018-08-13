<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 14.08.17
 */

namespace JTL\Onetimelink;

use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\PasswordHash
 */
class PasswordHashTest extends TestCase
{

    public function testCanBeCreatedFromHash()
    {
        $this->assertInstanceOf(PasswordHash::class, PasswordHash::createFromHash('foo'));
    }

    public function testCreateAndVerifyHash()
    {
        $user = 'foo';
        $pass = 'bar';
        $passwordHash = PasswordHash::createHash($user, $pass);
        $hash = PasswordHash::createFromHash($passwordHash);

        $this->assertTrue($hash->verify($user, $pass));
    }
}
