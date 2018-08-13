<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 08.08.17
 */

namespace JTL\Onetimelink;

use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\LinkHash
 */
class LinkHashTest extends TestCase
{

    public function testCanCreateSHA512Hash(): void
    {
        $_ = LinkHash::create('test');
        $this->assertInternalType('string', $_);
        $this->assertEquals(24, \strlen($_));
    }

    public function testCanCreateUniqueSHA512Hashes(): void
    {
        $hashes = [];

        for ($i = 0; $i < 100; ++$i) {
            $hashes[] = LinkHash::createUnique();
        }

        $uniqueHashes = array_unique($hashes);

        $this->assertEquals($uniqueHashes, $hashes);
    }

    public function testCanReceiveCustomLength(): void
    {
        $this->assertEquals(24, \strlen(LinkHash::createUnique()));
        $this->assertEquals(10, \strlen(LinkHash::createUnique(10)));
        $this->assertEquals(40, \strlen(LinkHash::createUnique(40)));
        $this->assertEquals(100, \strlen(LinkHash::createUnique(100)));
        $this->assertEquals(128, \strlen(LinkHash::createUnique(5000)));
    }
}
