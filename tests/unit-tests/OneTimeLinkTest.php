<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 08.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\View\ViewInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers  \JTL\Onetimelink\OneTimeLink
 *
 * @uses    \JTL\Onetimelink\LinkHash
 * @uses    \JTL\Onetimelink\User
 * @uses    \JTL\Onetimelink\PasswordHash
 * @uses    \JTL\Onetimelink\Storage\MetaData
 * @uses    \JTL\Onetimelink\Payload
 */
class OneTimeLinkTest extends TestCase
{

    public function testCanCreateLinkUri()
    {
        $link = new OneTimeLink(LinkHash::createUnique(), User::createUserFromString('tester'));
        $this->assertRegExp('/read\/(\w{9,})$/', $link->createLinkUri());
    }

    public function testCanAppendDataToView()
    {
        $hash = LinkHash::createUnique();
        $user = User::createUserFromString('tester');
        $link = new OneTimeLink($hash, $user);

        /** @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(ViewInterface::class);

        $viewMock->expects($this->at(0))
            ->method('set')
            ->with('onetimelink')
            ->willReturn($viewMock);

        $viewMock->expects($this->at(1))
            ->method('set')
            ->with('hash', $hash)
            ->willReturn($viewMock);

        $viewMock->expects($this->at(2))
            ->method('set')
            ->with('user', (string)$user)
            ->willReturn($viewMock);

        $link->appendDataToView($viewMock);
    }

    public function testCanReadHash()
    {
        $hash = LinkHash::createUnique();
        $link = new OneTimeLink($hash, User::createUserFromString('tester'));

        $this->assertEquals($hash, $link->getHash());
    }

    public function testCanReadUser()
    {
        $user = User::createUserFromString('tester');
        $link = new OneTimeLink(LinkHash::createUnique(), $user);

        $this->assertEquals((string)$user, (string)$link->getUser());
    }

    public function testCanCovertedToArray()
    {
        $userMock = $this->createMock(User::class);
        $otl = new OneTimeLink("fooDerBAr", $userMock);

        $this->assertEquals([
            'onetimelink' => '/read/fooDerBAr',
            'hash' => 'fooDerBAr',
        ], $otl->toArray());
    }
}
