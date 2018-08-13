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
 * @covers \JTL\Onetimelink\View\PlainView
 */
class PlainViewTest extends TestCase
{
    public function testCanSetData()
    {
        $view = new PlainView();
        $this->assertInstanceOf(PlainView::class, $view->set('', 'bar'));
        $this->assertEquals('bar', $view->render());
    }

    public function testCanSetArrayData()
    {
        $view = new PlainView();
        $this->assertInstanceOf(PlainView::class, $view->set('', ['bar']));
        $this->assertEquals('["bar"]', $view->render());
    }

    public function testCanSetObjectData()
    {
        $view = new PlainView();
        $this->assertInstanceOf(PlainView::class, $view->set('', new \stdClass()));
        $this->assertEquals('O:8:"stdClass":0:{}', $view->render());
    }

    public function testDefaultContentTypeIsText()
    {
        $view = new PlainView();
        $this->assertEquals('plain/text', $view->getContentType());
    }

    public function testContentTypeCanBeSet()
    {
        $view = new PlainView('foo/bar');
        $this->assertEquals('foo/bar', $view->getContentType());
    }
}
