<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 13/08/18
 */

use JTL\Onetimelink\DAO\AttachmentDAO;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\OODBBean;
use RedBeanPHP\R;

/**
 * Class AttachmentDAOTest
 * @covers \JTL\Onetimelink\DAO\AttachmentDAO
 */
class AttachmentDAOTest extends TestCase
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

        $attachment = $attachmentDAO->loadDBObject();

        $this->assertInstanceOf(OODBBean::class, $attachment);
        $this->assertEquals('ab@cd.example', $attachment->userEmail);
        $this->assertEquals('2018-01-01', $attachment->created);
        $this->assertEquals(null, $attachment->deleted);
        $this->assertEquals('text/plain', $attachment->filetype);
        $this->assertEquals('-#-TEXTINPUT-#-', $attachment->name);
        $this->assertEquals($attachmentHash, $attachment->hash);
        $this->assertEquals(true, $attachment->isMerged);
    }

    public function testCanLoadAttachmentFromHash()
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

        $attachment = AttachmentDAO::getAttachmentFromHash($attachmentHash);

        $this->assertInstanceOf(AttachmentDAO::class, $attachment);
        $this->assertEquals('ab@cd.example', $attachment->getUserEmail());
        $this->assertEquals('2018-01-01', $attachment->getCreated());
        $this->assertEquals(null, $attachment->getDeleted());
        $this->assertEquals('text/plain', $attachment->getFileType());
        $this->assertEquals('-#-TEXTINPUT-#-', $attachment->getFileName());
        $this->assertEquals($attachmentHash, $attachment->getHash());
        $this->assertEquals(true, $attachment->isMerged());
    }

    public function testReturnsNullIfAttachmentNotFound()
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

        $attachment = $attachmentDAO->loadDBObject();
        $attachment2 = AttachmentDAO::getAttachmentFromHash($attachmentHash);

        $this->assertNull($attachment);
        $this->assertNull($attachment2);
    }

    public function testCanConvertAttachmentToArray()
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

        $attachment = $attachmentDAO->toArray();

        $this->assertEquals('ab@cd.example', $attachment['user_email']);
        $this->assertEquals('2018-01-01', $attachment['created']);
        $this->assertEquals(null, $attachment['deleted']);
        $this->assertEquals('text/plain', $attachment['filetype']);
        $this->assertEquals('-#-TEXTINPUT-#-', $attachment['name']);
        $this->assertEquals($attachmentHash, $attachment['hash']);
        $this->assertEquals(true, $attachment['merged']);
    }

    public function testHasWorkingSetters()
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

        $attachmentDAO->setUserEmail('asdf');
        $attachmentDAO->setCreated('2019');
        $attachmentDAO->setDeleted('2020');
        $attachmentDAO->setFileType('application/json');
        $attachmentDAO->setFileName('test.json');
        $attachmentDAO->setIsMerged(false);
        $attachmentDAO->setHash('ff00');
        $attachment = $attachmentDAO->toArray();

        $this->assertEquals('asdf', $attachment['user_email']);
        $this->assertEquals('2019', $attachment['created']);
        $this->assertEquals('2020', $attachment['deleted']);
        $this->assertEquals('application/json', $attachment['filetype']);
        $this->assertEquals('test.json', $attachment['name']);
        $this->assertEquals('ff00', $attachment['hash']);
        $this->assertEquals(false, $attachment['merged']);
    }
}
