<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 13/08/18
 */

use JTL\Onetimelink\DAO\AttachmentDAO;
use JTL\Onetimelink\DAO\LinkDAO;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\OODBBean;
use RedBeanPHP\R;

/**
 * Class LinkDAOTest
 * @covers \JTL\Onetimelink\DAO\LinkDAO
 *
 * @uses \JTL\Onetimelink\DAO\AttachmentDAO
 */
class LinkDAOTest extends TestCase
{
    protected function setUp()
    {
        $dsn = 'sqlite:' . __DIR__ . '/../../../var/db/test.db';
        if (!R::hasDatabase('test')) {
            R::addDatabase('test', $dsn);
        }

        R::selectDatabase('test');
    }

    protected function tearDown()
    {
        R::nuke();
        R::close();
    }

    public function testCanSaveAndLoadAttachment()
    {
        $attachmentHash = uniqid('attachment', true);
        $attachmentDAO = new AttachmentDAO(
            'ab@cd.example',
            '2018-01-01',
            null,
            'text/plain',
            '-#-TEXTINPUT-#-',
            $attachmentHash,
            true
        );

        $this->assertTrue($attachmentDAO->save());

        $linkHash = uniqid('link', true);
        $linkDAO = new LinkDAO(
            'tester',
            $linkHash,
            0,
            ['test', 'tag2'],
            '2018-01-01',
            [$attachmentDAO->loadDBObject()]
        );

        $this->assertTrue($linkDAO->save());

        $link = $linkDAO->loadDBObject();
        $tags = array_values(array_filter(explode(',', $link->tags), '\strlen'));

        $this->assertInstanceOf(OODBBean::class, $link);
        $this->assertEquals('tester', $link->user);
        $this->assertEquals($linkHash, $link->hash);
        $this->assertEquals('test', $tags[0]);
        $this->assertEquals('tag2', $tags[1]);
        $this->assertEquals(0, $link->isGuestLink);
        $this->assertEquals('2018-01-01', $link->created);
        $this->assertEquals(null, $link->deleted);
        $this->assertCount(1, $link->sharedAttachmentList);
    }

    public function testCanConstructDAOFromBean()
    {
        $linkHash = uniqid('link', true);
        $linkDAO = new LinkDAO(
            'tester',
            $linkHash,
            0,
            ['test', 'tag2'],
            '2018-01-01',
            []
        );

        $this->assertTrue($linkDAO->save());

        $link = $linkDAO->loadDBObject();
        $linkDAO2 = LinkDAO::constructFromDB($link);

        $this->assertEquals($linkDAO, $linkDAO2);
    }

    public function testCanLoadLinkFromHash()
    {
        $linkHash = uniqid('link', true);
        $linkDAO = new LinkDAO(
            'tester',
            $linkHash,
            0,
            ['test', 'tag2'],
            '2018-01-01',
            []
        );

        $this->assertTrue($linkDAO->save());
        $linkDAO2 = LinkDAO::getLinkFromHash($linkHash);
        $this->assertEquals($linkDAO, $linkDAO2);
    }

    public function testReturnsNullIfLinkNotFound()
    {
        $linkDAO = LinkDAO::getLinkFromHash(uniqid('link', true));
        $linkDAO2 = new LinkDAO(
            'tester',
            '',
            0,
            ['test', 'tag2'],
            '2018-01-01',
            []
        );

        $this->assertNull($linkDAO);
        $this->assertNull($linkDAO2->loadDBObject());
    }

    public function testCanReturnLinkAsArray()
    {
        $linkHash = uniqid('link', true);
        $linkDAO = new LinkDAO(
            'tester',
            $linkHash,
            0,
            ['test', 'tag2'],
            '2018-01-01',
            []
        );

        $this->assertTrue($linkDAO->save());

        $link = $linkDAO->toArray();

        $this->assertEquals('tester', $link['user']);
        $this->assertEquals($linkHash, $link['hash']);
        $this->assertEquals(0, $link['is_guest_link']);
        $this->assertEquals(['test', 'tag2'], $link['tags']);
        $this->assertEquals('2018-01-01', $link['created']);
        $this->assertEquals([], $link['attachments']);
        $this->assertEquals(null, $link['deleted']);
    }

    public function testHasWorkingSetters()
    {
        $linkHash = uniqid('link', true);
        $linkDAO = new LinkDAO(
            'tester',
            $linkHash,
            0,
            ['test', 'tag2'],
            '2018-01-01',
            []
        );

        $this->assertTrue($linkDAO->save());

        $linkDAO->setUser('abcdef');
        $linkDAO->setHash('hash1234');
        $linkDAO->setIsGuestLink(true);
        $linkDAO->setTags(['abcdef', 'fedcba']);
        $linkDAO->setCreated('2020');
        $linkDAO->setAttachments([0,1,2]);
        $linkDAO->setDeleted('2025');

        $this->assertEquals('abcdef', $linkDAO->getUser());
        $this->assertEquals('hash1234', $linkDAO->getHash());
        $this->assertEquals(true, $linkDAO->isGuestLink());
        $this->assertEquals(['abcdef', 'fedcba'], $linkDAO->getTags());
        $this->assertEquals('2020', $linkDAO->getCreated());
        $this->assertEquals([0,1,2], $linkDAO->getAttachments());
        $this->assertEquals('2025', $linkDAO->getDeleted());
    }
}
