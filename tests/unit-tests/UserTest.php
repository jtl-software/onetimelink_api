<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\OneTimeLink\User
 *
 * @uses   \JTL\Onetimelink\PasswordHash
 */
class UserTest extends TestCase
{

    public function testCanBeCreatedFromString()
    {
        $this->assertInstanceOf(User::class, User::createUserFromString('arno'));
    }

    public function testCanCreateAnonymousUser()
    {
        $user = User::createAnonymousUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isAnonymous());
    }

    public function testCanCreateWithCredentials()
    {
        $user = User::createFromCredentials('foo', PasswordHash::createFromHash('hash'));
        $this->assertInstanceOf(User::class, $user);
    }

    public function testCanVerifyUser()
    {
        $username = 'foo';
        $password = 'pass';
        $passwordHash = PasswordHash::createHash($username, $password);
        $user = User::createFromCredentials('foo', PasswordHash::createFromHash($passwordHash));

        $this->assertTrue($user->verify($password));
        $this->assertTrue($user->isAuthenticated());
    }

    public function testUserWithUsernameIsNotAnonymous()
    {
        $user = User::createUserFromString('wellknownUser');
        $this->assertFalse($user->isAnonymous());
    }

    public function testUserObjectCanByCompared()
    {
        $userA = User::createUserFromString('userA');
        $userB = User::createUserFromString('userB');

        $this->assertFalse($userA->equals($userB));
        $this->assertTrue($userA->equals($userA));
        $this->assertTrue($userA->equals(clone $userA));
    }

    public function testCanSetAndReadMail()
    {
        $user = User::createUserFromString('userA');
        $user->setEmail('foo@bar.de');

        $this->assertEquals('foo@bar.de', $user->getEmail());
    }

    public function testCanSetAndReadAdminFlag()
    {
        $user = User::createUserFromString('userA');
        $user->setIsAdmin();
        $this->assertTrue($user->isAdmin());

        $user->setIsAdmin(false);
        $this->assertFalse($user->isAdmin());
    }

    public function testCanSetAndReadActiveFlag()
    {
        $user = User::createUserFromString('userA');
        $user->setIsActive();
        $this->assertTrue($user->isActive());

        $user->setIsActive(false);
        $this->assertFalse($user->isActive());
    }
}
