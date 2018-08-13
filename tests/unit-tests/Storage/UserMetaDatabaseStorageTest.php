<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 13/08/18
 */

use JTL\Onetimelink\DAO\AttachmentDAO;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\LocationDirectory;
use JTL\Onetimelink\Storage\UserMetaDatabaseStorage;
use JTL\Onetimelink\User;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\R;

/**
 * Class UserMetaDatabaseStorageTest
 * @covers \JTL\Onetimelink\Storage\UserMetaDatabaseStorage
 *
 * @uses \JTL\Onetimelink\PasswordHash
 * @uses \JTL\Onetimelink\User
 * @uses \JTL\Onetimelink\DAO\AttachmentDAO
 * @uses \JTL\Onetimelink\DAO\LinkDAO
 * @uses \JTL\Onetimelink\Storage\DatabaseStorage
 * @uses \JTL\Onetimelink\Storage\LocationDirectory
 */
class UserMetaDatabaseStorageTest extends TestCase
{
    protected function setUp()
    {
        $dsn = 'sqlite:' . __DIR__ . '/../../../var/db/test.db';
        if (!R::hasDatabase('test')) {
            R::addDatabase('test', $dsn);
        }

        R::selectDatabase('test');

        $this->directory = __DIR__ . '/../../../var/testdata/';
    }

    protected function tearDown()
    {
        R::nuke();
        R::close();
    }

    public function testCanAppendLink()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $storage = new UserMetaDatabaseStorage($testUser);
        $linkHash = uniqid('link', true);

        $storage->appendLink($linkHash);

        $userMeta = R::findAll('usermeta');
        $this->assertCount(1, $userMeta);

        $firstMeta = reset($userMeta);
        $this->assertEquals($linkHash, $firstMeta->hash);
    }

    public function testCanGetLinks()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $storage = new UserMetaDatabaseStorage($testUser);
        $dbStorage = new DatabaseStorage(new LocationDirectory($this->directory));
        $linkHash = uniqid('link', true);
        $linkHash2 = uniqid('link', true);
        $attachmentHash = uniqid('attachment', true);

        $attachmentDAO = new AttachmentDAO(
            'otl-tester@jtl-software.com',
            '2018',
            null,
            'text/plain',
            'testfile',
            $attachmentHash,
            true
        );

        $attachmentDAO->save();

        $dbStorage->writeLink($linkHash, [$attachmentDAO], $testUser);
        $dbStorage->writeLink($linkHash2, [$attachmentDAO], $testUser);

        $storage->appendLink($linkHash);
        $storage->appendLink($linkHash2);

        $userMeta = $storage->getLinks();
        $this->assertCount(2, $userMeta);
    }

    public function testCanDeleteLink()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $storage = new UserMetaDatabaseStorage($testUser);
        $linkHash = uniqid('link', true);
        $now = (new \DateTimeImmutable())->format('Y-m-d');

        $storage->appendLink($linkHash);
        $storage->setToDeleted($linkHash);

        $userMeta = R::findAll('usermeta');
        $this->assertCount(1, $userMeta);

        $firstMeta = reset($userMeta);
        $this->assertEquals($linkHash, $firstMeta->hash);
        $this->assertEquals($now, date('Y-m-d', strtotime($firstMeta->deleted)));
    }
}
