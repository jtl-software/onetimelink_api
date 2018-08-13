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
 * @covers \JTL\Onetimelink\UserList
 *
 * @uses   \JTL\Onetimelink\PasswordHash
 * @uses   \JTL\Onetimelink\User
 */
class UserListTest extends TestCase
{

    public function testUserCanRetrieved()
    {
        $list = [
            'foo' => [
                'password' => 'hash',
                'email' => 'foo@bar.de'
            ]
        ];

        $userList = new UserList($list);
        $user = $userList->getUser('foo');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('foo', (string)$user);
    }

    public function testRetrieveAnonymous()
    {
        $list = [
            'foo' => [
                'password' => 'hash',
                'email' => 'foo@bar.de'
            ]
        ];
        $userList = new UserList($list);
        $user = $userList->getUser('bar');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(User::USER_ANONYMOUS, (string)$user);
    }

    public function testRetrieveUserList()
    {
        $list = [
            'foo@bar.de' => [
                'password' => 'hash',
                'active' => false,
                'created_at' => '2018-01-01'
            ],
            'bar@foo.de' => [
                'password' => 'hash',
            ]
        ];
        $userList = new UserList($list, ['bar@foo.de']);
        $_ = $userList->getUsers();
        $this->assertEquals([
            ['email' => 'foo@bar.de', 'isAdmin' => false, 'active' => false, 'created_at' => '2018-01-01'],
            ['email' => 'bar@foo.de', 'isAdmin' => true, 'active' => true, 'created_at' => null],
        ], $_);
    }
}
