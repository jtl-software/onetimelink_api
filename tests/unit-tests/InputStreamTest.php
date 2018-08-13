<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\InputStream
 */
class InputStreamTest extends TestCase
{

    public function testReadFromInputStream()
    {
        $this->assertEquals('', (new InputStream())->readFromStream());
    }
}
