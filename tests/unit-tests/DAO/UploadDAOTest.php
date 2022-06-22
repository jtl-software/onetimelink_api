<?php
/**
 * Created by PhpStorm.
 * User: patric
 * Date: 27.08.18
 * Time: 13:54
 */

namespace JTL\Onetimelink\DAO;

use RedBeanPHP\R;

/**
 * Class UploadDAOTest
 * @package JTL\Onetimelink\DAO
 * @covers \JTL\Onetimelink\DAO\UploadDAO
 */
class UploadDAOTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $dsn = 'sqlite:' . __DIR__ . '/../../../var/db/test.db';
        if (!R::hasDatabase('test')) {
            R::addDatabase('test', $dsn);
        }

        R::selectDatabase('test');
    }

    protected function tearDown(): void
    {
        R::nuke();
        R::close();
    }

    public function testCanBeSavedAndLoaded(){
        $token = uniqid('token', true);
        $receivedBytes = random_int(100,1000000);
        $maxUploadSize = random_int(100,1000000);
        $receivedChunks = random_int(100,1000000);
        $done = true;
        $identifier = uniqid('identifier', true);
        $created = uniqid('created', true);
        $uploadDAO = new UploadDAO($token, $receivedChunks, $receivedBytes, $maxUploadSize, $done, $identifier, $created);

        $this->assertTrue($uploadDAO->save());

        $upload = UploadDAO::getUploadFromToken($token);

        $this->assertEquals($token, $upload->getToken());
        $this->assertEquals($receivedChunks, $upload->getReceivedChunks());
        $this->assertEquals($receivedBytes, $upload->getReceivedBytes());
        $this->assertEquals($maxUploadSize, $upload->getMaxUploadSize());
        $this->assertEquals($done, $upload->isDone());
        $this->assertEquals($identifier, $upload->getIdentifier());
        $this->assertEquals($created, $upload->getCreated());
    }

    public function testCanBeSavedAndLoadedFromIdentifier(){
        $token = uniqid('token', true);
        $receivedBytes = random_int(100,1000000);
        $maxUploadSize = random_int(100,1000000);
        $receivedChunks = random_int(100,1000000);
        $done = true;
        $identifier = uniqid('identifier', true);
        $created = uniqid('created', true);
        $uploadDAO = new UploadDAO($token);

        $uploadDAO->setToken($token);
        $uploadDAO->setReceivedChunks($receivedChunks);
        $uploadDAO->setReceivedBytes($receivedBytes);
        $uploadDAO->setMaxUploadSize($maxUploadSize);
        $uploadDAO->setDone($done);
        $uploadDAO->setIdentifier($identifier);
        $uploadDAO->setCreated($created);


        $this->assertTrue($uploadDAO->save());

        $upload = UploadDAO::getUploadFromIdentifier($identifier);

        $this->assertEquals($token, $upload->getToken());
        $this->assertEquals($receivedChunks, $upload->getReceivedChunks());
        $this->assertEquals($receivedBytes, $upload->getReceivedBytes());
        $this->assertEquals($maxUploadSize, $upload->getMaxUploadSize());
        $this->assertEquals($done, $upload->isDone());
        $this->assertEquals($identifier, $upload->getIdentifier());
        $this->assertEquals($created, $upload->getCreated());
    }

    public function testCanNotBeLoadedFromIdentifier(){
        $identifier = uniqid('NOidentifier', true);

        $upload = UploadDAO::getUploadFromIdentifier($identifier);
        $this->assertEquals(null, $upload);
    }

    public function testCanNotBeLoadedFromToken(){
        $token = uniqid('NOtoken', true);

        $upload = UploadDAO::getUploadFromToken($token);
        $this->assertEquals(null, $upload);
    }

    public function testCanBeSavedAndLoadedAsDBObject(){
        $token = uniqid('token', true);
        $receivedBytes = random_int(100,1000000);
        $maxUploadSize = random_int(100,1000000);
        $receivedChunks = random_int(100,1000000);
        $done = true;
        $identifier = uniqid('identifier', true);
        $created = uniqid('created', true);
        $uploadDAO = new UploadDAO($token, $receivedChunks, $receivedBytes, $maxUploadSize, $done, $identifier, $created);

        $this->assertTrue($uploadDAO->save());

        $upload = $uploadDAO->loadDBObject();

        $this->assertEquals($token, $upload->token);
        $this->assertEquals($receivedChunks, $upload->receivedChunks);
        $this->assertEquals($receivedBytes, $upload->receivedBytes);
        $this->assertEquals($maxUploadSize, $upload->maxUploadSize);
        $this->assertEquals($done, $upload->done);
        $this->assertEquals($identifier, $upload->identifier);
        $this->assertEquals($created, $upload->created);
    }
}