<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink\View;

use PHPUnit\Framework\TestCase;

/**
 * @covers  \JTL\Onetimelink\View\JsonView
 */
class JsonViewTest extends TestCase
{

    public function testCanSetData()
    {
        $view = new JsonView();
        $this->assertInstanceOf(JsonView::class, $view->set('foo', 'bar'));

        $this->assertEquals('{"foo":"bar"}', $view->render());
    }

    public function testContentTypeIsJson()
    {
        $view = new JsonView();
        $this->assertEquals('application/json', $view->getContentType());
    }
}
