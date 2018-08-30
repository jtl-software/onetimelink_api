<?php
/**
 * Created by PhpStorm.
 * User: patric
 * Date: 27.08.18
 * Time: 14:12
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\LinkHash;
use JTL\Onetimelink\Storage\DatabaseStorage;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\R;

/**
 * Class DeleteUploadTest
 * @package JTL\Onetimelink\Controller\Command
 * @covers \JTL\Onetimelink\Controller\Command\DeleteUpload
 * @uses \JTL\Onetimelink\DAO\AttachmentDAO
 * @uses \JTL\Onetimelink\DAO\UploadDAO
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\LinkHash
 * @uses \JTL\Onetimelink\Response
 * @uses \JTL\Onetimelink\View\JsonView
 */
class DeleteUploadTest extends TestCase
{
    protected function setUp()
    {
        $dsn = 'sqlite:' . __DIR__ . '/../../../../var/db/test.db';
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

    public function testCanDeleteUpload(){
        $token = uniqid('token', true);
        $hash = LinkHash::create($token);
        $upload = R::dispense('upload');
        $upload->token = $token;
        $upload->done = true;
        R::store($upload);

        $attachment = R::dispense('attachment');
        $attachment->hash = $hash;
        R::store($attachment);

        $mockStorage = $this->createMock(DatabaseStorage::class);
        $deleteUploadAction = new DeleteUpload($mockStorage, $token);
        $result = $deleteUploadAction->execute();
        $this->assertTrue($result->isSuccessful());
    }
}