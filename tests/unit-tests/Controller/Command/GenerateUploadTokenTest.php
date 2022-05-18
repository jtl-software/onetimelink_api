<?php
/**
 * Created by PhpStorm.
 * User: patric
 * Date: 27.08.18
 * Time: 14:23
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Storage\DatabaseStorage;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\R;

/**
 * Class GenerateUploadTokenTest
 * @package JTL\Onetimelink\Controller\Command
 * @covers \JTL\Onetimelink\Controller\Command\GenerateUploadToken
 * @uses \JTL\Onetimelink\DAO\UploadDAO
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\LinkHash
 * @uses \JTL\Onetimelink\Response
 * @uses \JTL\Onetimelink\View\JsonView
 */
class GenerateUploadTokenTest extends TestCase
{
    protected function setUp(): void
    {
        $dsn = 'sqlite:' . __DIR__ . '/../../../../var/db/test.db';
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

    public function testCanGenerateUploadToken(){
        $mockDatabaseStorege = $this->createMock(DatabaseStorage::class);
        $mockFactory = $this->createMock(Factory::class);
        $isGuest = true;
        $maxUploadSize = random_int(100,100000);
        $identifier = uniqid('identifier', true);

        $generateUploadTokenCommand = new GenerateUploadToken($mockDatabaseStorege, $isGuest,
            $maxUploadSize, $identifier);

        $result = $generateUploadTokenCommand->execute();
        $this->assertTrue($result->isSuccessful());
    }
}