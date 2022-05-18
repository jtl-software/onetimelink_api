<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 13/08/18
 */

use JTL\Onetimelink\DAO\AttachmentDAO;
use JTL\Onetimelink\DAO\LinkDAO;
use JTL\Onetimelink\Exception\DataNotFoundException;
use JTL\Onetimelink\LinkHash;
use JTL\Onetimelink\Payload;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\LocationDirectory;
use JTL\Onetimelink\Storage\MetaData;
use JTL\Onetimelink\User;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\OODBBean;
use RedBeanPHP\R;

/**
 * Class DatabaseStorageTest
 * @covers \JTL\Onetimelink\Storage\DatabaseStorage
 *
 * @uses \JTL\Onetimelink\DAO\AttachmentDAO
 * @uses \JTL\Onetimelink\Storage\LocationDirectory
 * @uses \JTL\Onetimelink\PasswordHash
 * @uses \JTL\Onetimelink\Payload
 * @uses \JTL\Onetimelink\Storage\MetaData
 * @uses \JTL\Onetimelink\User
 * @uses \JTL\Onetimelink\LinkHash
 * @uses \JTL\Onetimelink\DAO\LinkDAO
 */
class DatabaseStorageTest extends TestCase
{
    /** @var string */
    private $directory;

    /** @var DatabaseStorage */
    private $storage;

    private function recursiveRmdir($dir)
    {
        if (is_dir($dir)) {
            $files = scandir($dir, SCANDIR_SORT_NONE);

            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    if (is_dir($dir . $file)) {
                        $this->recursiveRmdir($dir . $file . '/');
                    } else {
                        unlink($dir . $file);
                    }
                }
            }

            reset($files);
            rmdir($dir);
        }
    }

    protected function setUp(): void
    {
        $dsn = 'sqlite:' . __DIR__ . '/../../../var/db/test.db';
        if (!R::hasDatabase('test')) {
            R::addDatabase('test', $dsn);
        }

        R::selectDatabase('test');

        $this->directory = __DIR__ . '/../../../var/testdata/';

        if (!is_dir($this->directory)) {
            mkdir($this->directory);
        }

        $this->storage = new DatabaseStorage(new LocationDirectory($this->directory));
    }

    protected function tearDown(): void
    {
        R::nuke();
        R::close();

        $directory = __DIR__ . '/../../../var/testdata/';
        $this->recursiveRmdir($directory);
    }

    public function testCanMergeChunks()
    {
        $hash = uniqid('hash', true);

        $chunkDir = $this->directory . substr($hash, 0, 2) . '/';
        if (!is_dir($chunkDir)) {
            mkdir($chunkDir);
        }

        $testUser = User::createUserFromString('otl-tester@jtl-software.com');

        $metaData = new MetaData('text/plain', $testUser);
        $payload1 = new Payload('test', $metaData);
        $payload2 = new Payload('1234', $metaData);

        $this->assertFalse($this->storage->isMergeDone($hash));
        $this->storage->writeChunk($hash, 1, $payload1);

        $this->assertFalse($this->storage->isMergeDone($hash));
        $this->storage->writeChunk($hash, 2, $payload2);

        $this->storage->mergeChunks($hash);

        $this->assertFileNotExists($chunkDir . $hash . '1');
        $this->assertFileNotExists($chunkDir . $hash . '2');
        $this->assertFileExists($chunkDir . $hash);
        $this->assertStringEqualsFile($chunkDir . $hash, 'test1234');

        $attachment = R::findOne('attachment', 'hash = ?', [$hash]);
        $this->assertInstanceOf(OODBBean::class, $attachment);
        $this->assertTrue($this->storage->isMergeDone($hash));
    }

    public function testCanCreateAttachment()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $randomData = base64_encode(bin2hex(random_bytes(128)));
        $hash = uniqid('hash', true);

        $metaData = new MetaData('text/plain', $testUser, 'testFile.txt');
        $payload = new Payload($randomData, $metaData);
        $assumedFileName = $this->directory . substr($hash, 0, 2) . '/' . $hash;

        $this->assertTrue($this->storage->write($hash, $payload));
        $this->assertFileExists($assumedFileName);
        $this->assertStringEqualsFile($assumedFileName, $randomData);

        $attachment = AttachmentDAO::getAttachmentFromHash($hash);
        $this->assertNotNull($attachment);
        $this->assertEquals('testFile.txt', $attachment->getFileName());
        $this->assertEquals($assumedFileName, $this->storage->getAttachmentLocation($hash));
    }

    public function testCanCreateLink()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $randomData = base64_encode(bin2hex(random_bytes(128)));
        $attachmentHash = uniqid('attachmentHash', true);

        $metaData = new MetaData('text/plain', $testUser, 'testFile.txt');
        $payload = new Payload($randomData, $metaData);
        $assumedFileName = $this->directory . substr($attachmentHash, 0, 2) . '/' . $attachmentHash;

        $this->assertTrue($this->storage->write($attachmentHash, $payload));
        $this->assertFileExists($assumedFileName);
        $this->assertStringEqualsFile($assumedFileName, $randomData);

        $attachment = AttachmentDAO::getAttachmentFromHash($attachmentHash);
        $this->assertNotNull($attachment);
        $this->assertEquals('testFile.txt', $attachment->getFileName());

        $linkHash = LinkHash::createUnique();
        $this->assertTrue($this->storage->writeLink(
            $linkHash,
            [$attachment],
            $testUser
        ));

        $link = LinkDAO::getLinkFromHash($linkHash);
        $this->assertInstanceOf(LinkDAO::class, $link);
        $this->assertCount(1, $link->getAttachments());
    }

    public function testCanCreateGuestLink()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $linkHash = LinkHash::createUnique();
        $tags = ['testtag', 'tag2'];

        $this->assertTrue($this->storage->writeGuestLink($linkHash, $testUser, $tags));

        $linkDAO = LinkDAO::getLinkFromHash($linkHash);
        $this->assertInstanceOf(LinkDAO::class, $linkDAO);
        $this->assertEquals($tags, $linkDAO->getTags());
    }

    public function testCanReadLinkData()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $randomData = base64_encode(bin2hex(random_bytes(128)));
        $attachmentHash = uniqid('attachmentHash', true);
        $linkTags = ['testtag', 'tag2'];

        $metaData = new MetaData('text/plain', $testUser, 'testFile.txt');
        $payload = new Payload($randomData, $metaData);
        $assumedFileName = $this->directory . substr($attachmentHash, 0, 2) . '/' . $attachmentHash;

        $this->assertTrue($this->storage->write($attachmentHash, $payload));
        $this->assertFileExists($assumedFileName);
        $this->assertStringEqualsFile($assumedFileName, $randomData);

        $attachment = AttachmentDAO::getAttachmentFromHash($attachmentHash);
        $this->assertNotNull($attachment);
        $this->assertEquals('testFile.txt', $attachment->getFileName());

        $linkHash = LinkHash::createUnique();
        $this->assertTrue($this->storage->writeLink(
            $linkHash,
            [$attachment],
            $testUser,
            $linkTags
        ));

        $linkBean = $this->storage->readLinkAsBean($linkHash);
        $this->assertInstanceOf(OODBBean::class, $linkBean);
        $linkDAO = LinkDAO::constructFromDB($linkBean);
        $this->assertEquals($linkTags, $linkDAO->getTags());

        $attachmentPayload = $this->storage->readAttachment($attachmentHash);
        $this->assertEquals('', $attachmentPayload->getData());
        $this->assertEquals(
            (string)$testUser,
            $attachmentPayload->getMetaData()->getUser()
        );
    }

    public function testCanDeleteLink()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $randomData = base64_encode(bin2hex(random_bytes(128)));
        $attachmentHash = uniqid('attachmentHash', true);

        $metaData = new MetaData('text/plain', $testUser, 'testFile.txt');
        $payload = new Payload($randomData, $metaData);
        $assumedFileName = $this->directory . substr($attachmentHash, 0, 2) . '/' . $attachmentHash;

        $this->assertTrue($this->storage->write($attachmentHash, $payload));
        $this->assertFileExists($assumedFileName);
        $this->assertStringEqualsFile($assumedFileName, $randomData);

        $attachment = AttachmentDAO::getAttachmentFromHash($attachmentHash);
        $this->assertNotNull($attachment);
        $this->assertEquals('testFile.txt', $attachment->getFileName());

        $linkHash = LinkHash::createUnique();
        $this->assertTrue($this->storage->writeLink(
            $linkHash,
            [$attachment],
            $testUser
        ));

        $link = LinkDAO::getLinkFromHash($linkHash);
        $this->assertInstanceOf(LinkDAO::class, $link);
        $this->assertCount(1, $link->getAttachments());

        $this->storage->deleteLink($linkHash);

        // Comparing using the actual precision can lead to errors where the deleted time can be off by one second.
        // Thus only the days are compared
        $now = (new \DateTimeImmutable())->format('Y-m-d');
        $newLink = R::findOne('link', 'hash = ?', [$linkHash]);
        $newLinkDAO = LinkDAO::constructFromDB($newLink);
        $this->assertEquals($now, date('Y-m-d', strtotime($newLinkDAO->getDeleted())));
    }

    public function testWontDeleteNonExistentLink()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $randomData = base64_encode(bin2hex(random_bytes(128)));
        $attachmentHash = uniqid('attachmentHash', true);

        $metaData = new MetaData('text/plain', $testUser, 'testFile.txt');
        $payload = new Payload($randomData, $metaData);
        $assumedFileName = $this->directory . substr($attachmentHash, 0, 2) . '/' . $attachmentHash;

        $this->assertTrue($this->storage->write($attachmentHash, $payload));
        $this->assertFileExists($assumedFileName);
        $this->assertStringEqualsFile($assumedFileName, $randomData);

        $attachment = AttachmentDAO::getAttachmentFromHash($attachmentHash);
        $this->assertNotNull($attachment);
        $this->assertEquals('testFile.txt', $attachment->getFileName());

        $linkHash = LinkHash::createUnique();
        $this->assertTrue($this->storage->writeLink(
            $linkHash,
            [$attachment],
            $testUser
        ));

        $link = LinkDAO::getLinkFromHash($linkHash);
        $this->assertInstanceOf(LinkDAO::class, $link);
        $this->assertCount(1, $link->getAttachments());

        $this->storage->deleteLink(LinkHash::createUnique());
        $newLink = R::findOne('link', 'hash = ?', [$linkHash]);
        $newLinkDAO = LinkDAO::constructFromDB($newLink);
        $this->assertEquals(null, $newLinkDAO->getDeleted());
    }

    public function testThrowsExceptionIfRequestedAttachmentLocationDoesNotExist()
    {
        $testUser = User::createUserFromString('otl-tester@jtl-software.com');
        $randomData = base64_encode(bin2hex(random_bytes(128)));
        $hash = uniqid('hash', true);

        $metaData = new MetaData('text/plain', $testUser, 'testFile.txt');
        $payload = new Payload($randomData, $metaData);
        $assumedFileName = $this->directory . substr($hash, 0, 2) . '/' . $hash;

        $this->assertTrue($this->storage->write($hash, $payload));
        $this->assertFileExists($assumedFileName);
        $this->assertStringEqualsFile($assumedFileName, $randomData);

        $attachment = AttachmentDAO::getAttachmentFromHash($hash);
        $this->assertNotNull($attachment);
        $this->assertEquals('testFile.txt', $attachment->getFileName());
        $this->expectException(DataNotFoundException::class);
        $this->storage->getAttachmentLocation(uniqid('wrong', true));
    }

    public function testReturnsNullIfReadLinkDoesNotExist()
    {
        $linkBean = $this->storage->readLinkAsBean(uniqid('hash', true));
        $this->assertNull($linkBean);
    }

    public function testWontMergeNonExistentChunks()
    {
        $this->assertFalse($this->storage->mergeChunks(uniqid('hash', true)));
    }

}
