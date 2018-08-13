<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\Storage\MetaData;
use PHPUnit\Framework\TestCase;

/**
 * Class MetaDataTest
 * @covers \JTL\Onetimelink\Storage\MetaData
 *
 * @uses   \JTL\Onetimelink\User
 * @uses   \JTL\Onetimelink\PasswordHash
 */
class MetaDataTest extends TestCase
{

    public function testCanCreatedFromArray()
    {
        $data = [
            MetaData::IDX_FILE_TYPE => 'plain/text',
            MetaData::IDX_CREATED_BY_MAIL => 'ronny@foo',
            MetaData::IDX_CREATED => (new \DateTimeImmutable())->format('c'),
        ];
        $this->assertInstanceOf(MetaData::class, MetaData::createFromExistingMetaData($data));
    }

    public function testFailWhenMissingContentType()
    {
        $data = [
            // missing -> MetaData::IDX_FILE_TYPE => 'plain/text',
            MetaData::IDX_CREATED_BY_MAIL => 'ronny@foo',
            MetaData::IDX_CREATED => (new \DateTimeImmutable())->format('c')
        ];

        $this->expectException(\RuntimeException::class);

        $this->assertInstanceOf(MetaData::class, MetaData::createFromExistingMetaData($data));
    }

    public function testFailWhenMissingCreatedByMail()
    {
        $data = [
            MetaData::IDX_FILE_TYPE => 'plain/text',
            // missing -> MetaData::IDX_CREATED_BY_MAIL => 'ronny@foo',
            MetaData::IDX_CREATED => (new \DateTimeImmutable())->format('c')
        ];

        $this->expectException(\RuntimeException::class);

        $this->assertInstanceOf(MetaData::class, MetaData::createFromExistingMetaData($data));
    }


    public function testFailWhenMissingCreated()
    {
        $data = [
            MetaData::IDX_FILE_TYPE => 'plain/text',
            MetaData::IDX_CREATED_BY_MAIL => 'ronny@foo',
            // missing -> MetaData::IDX_CREATED => (new \DateTimeImmutable())->format('c')
        ];

        $this->expectException(\RuntimeException::class);

        $this->assertInstanceOf(MetaData::class, MetaData::createFromExistingMetaData($data));
    }

    public function testCanBeConvertedToJson()
    {
        $data = [
            MetaData::IDX_FILE_TYPE => 'plain/text',
            MetaData::IDX_CREATED_BY_MAIL => 'ronny@foo',
            MetaData::IDX_ORIGINAL_FILE_NAME => 'filename',
            MetaData::IDX_CREATED => (new \DateTimeImmutable())->format('c'),
        ];

        $this->assertEquals(
            json_encode($data, JSON_PRETTY_PRINT),
            (MetaData::createFromExistingMetaData($data))->toJson()
        );
    }

    public function testCanReadContentType()
    {
        $metaData = new MetaData('plain/text', User::createAnonymousUser(), 'filename');
        $this->assertEquals(
            'plain/text',
            $metaData->getFileType()
        );
    }

    public function testCanReadUser()
    {
        $metaData = new MetaData(
            'dingens',
            User::createUserFromString('ronny'),
            'filename'
        );

        $this->assertEquals(
            User::createUserFromString('ronny'),
            $metaData->getUser()
        );
    }

    public function testCanReadCreationDate()
    {
        $date = new \DateTimeImmutable();
        $metaData = new MetaData(
            'dingens',
            User::createAnonymousUser(),
            'filename',
            $date
        );

        $this->assertEquals(
            $date,
            $metaData->getCreated()
        );
    }
}
