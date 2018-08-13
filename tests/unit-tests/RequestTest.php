<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\Session\Session;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestTest
 *
 * @covers \JTL\Onetimelink\Request
 */
class RequestTest extends TestCase
{

    /**
     * @runInSeparateProcess
     */
    public function testCanBeCreatedFromGlobals()
    {
        $this->assertInstanceOf(Request::class, Request::createFromGlobals());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanBeCreatedFromGlobalsWithoutPruneGlobals()
    {
        $_SERVER['foo'] = 'bar';
        $_GET = $_POST = $_SERVER;

        $this->assertInstanceOf(Request::class, Request::createFromGlobals(false));
        $this->assertEquals($_SERVER['foo'], 'bar');
    }

    public function testCanReadUri()
    {
        $_SERVER['REQUEST_URI'] = '/foo?bar=1';
        $request = Request::createFromGlobals(false);

        $this->assertEquals($request->getUri(), $_SERVER['REQUEST_URI']);
    }

    public function testCanGetPath()
    {
        $_SERVER['REQUEST_URI'] = '/foo?bar=1';
        $request = Request::createFromGlobals(false);

        $this->assertEquals($request->getPath(), '/foo');
    }

    public function testCanReadHost()
    {
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'myhost.com';
        $expectation = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];

        $request = Request::createFromGlobals(false);
        $this->assertEquals($request->getHost(), $expectation);
    }

    public function testCanCheckHttpMethodGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = Request::createFromGlobals(false);
        $this->assertTrue($request->isGet());
    }

    public function testCanCheckHttpMethodPost()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Request::createFromGlobals(false);
        $this->assertTrue($request->isPost());
    }

    public function testCanCheckHttpMethodPut()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $request = Request::createFromGlobals(false, $this->createMock(InputStream::class));
        $this->assertTrue($request->isPut());
    }

    public function testCanCheckAnyHttpMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'FOO';

        $request = Request::createFromGlobals(false);
        $this->assertEquals($request->getHttpMethod(), $_SERVER['REQUEST_METHOD']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanReadStreamData()
    {

        $expectation = 'I bims dr Strem';
        $streamMock = $this->createMock(InputStream::class);
        $streamMock->expects($this->once())
            ->method('readFromStream')
            ->willReturn($expectation);

        $request = Request::createFromGlobals(true, $streamMock);
        $this->assertEquals($request->getStreamData(), $expectation);
    }

    public function testCanReadFromGet()
    {
        $_GET['foo'] = 'bar';
        $request = Request::createFromGlobals(false);
        $this->assertEquals($request->readGet('foo'), 'bar');
    }

    public function testNullWhenReadNonExistingDataFromGet()
    {
        $request = Request::createFromGlobals(false);
        $this->assertNull($request->readGet('non-existing-key'));
    }

    public function testCanReadFromPost()
    {
        $_POST['foo'] = 'bar';
        $request = Request::createFromGlobals(false);
        $this->assertEquals($request->readPost('foo'), 'bar');
    }

    public function testNullWhenReadNonExistingDataFromPost()
    {
        $request = Request::createFromGlobals(false);
        $this->assertNull($request->readPost('non-existing-key'));
    }

    public function testCanReadFromServer()
    {
        $_SERVER['foo'] = 'bar';
        $request = Request::createFromGlobals(false);
        $this->assertEquals($request->readServer('foo'), 'bar');
    }

    public function testNullWhenReadNonExistingDataFromServer()
    {
        $request = Request::createFromGlobals(false);
        $this->assertNull($request->readServer('non-existing-key'));
    }

    public function testCanGetSessionFromRequest()
    {
        $request = Request::createFromGlobals(false);
        $request->setSession($this->createMock(Session::class));

        $this->assertInstanceOf(Session::class, $request->getSession());
    }

    public function clientIpTestCases()
    {
        return [
            // key, value, expectation
            ['HTTP_CLIENT_IP', '1.2.3.4', '1.2.3.0'],
            ['HTTP_X_FORWARDED_FOR', '1.2.3.4', '1.2.3.0'],
            ['HTTP_X_FORWARDED', '1.2.3.4', '1.2.3.0'],
            ['HTTP_FORWARDED_FOR', '1.2.3.4', '1.2.3.0'],
            ['HTTP_FORWARDED', '1.2.3.4', '1.2.3.0'],
            ['REMOTE_ADDR', '1.2.3.4', '1.2.3.0'],
            ['unknown', '', '0.0.0.0'],
        ];
    }

    /**
     * @dataProvider clientIpTestCases
     */
    public function testCanReceiveBlurredClientIp($serverKey, $serverValue, $expectation)
    {
        $serverBackup = $_SERVER;
        $_SERVER[$serverKey] = $serverValue;
        $request = Request::createFromGlobals(false);
        $this->assertEquals($expectation, $request->getBlurredClientIp());

        $_SERVER = $serverBackup;
    }

    public function testCanReadUserAgent(){

        $serverBackup = $_SERVER;
        $expectation = uniqid('user-agent');
        $_SERVER['HTTP_USER_AGENT'] = $expectation;
        $request = Request::createFromGlobals(false);
        $this->assertEquals($expectation, $request->getUserAgent());

        $_SERVER = $serverBackup;
    }

    public function testUserAgentIsUnknownIfEmpty(){

        $serverBackup = $_SERVER;
        unset($_SERVER['HTTP_USER_AGENT']);
        $request = Request::createFromGlobals(false);
        $this->assertEquals('unknown', $request->getUserAgent());

        $_SERVER = $serverBackup;
    }

    public function testCanGetAuthInfo(){

        $serverBackup = $_SERVER;
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = '123456' . base64_encode('foo:geh heim');
        $request = Request::createFromGlobals(false);

        $this->assertEquals('foo', $request->readServer('PHP_AUTH_USER'));
        $this->assertEquals('geh heim', $request->readServer('PHP_AUTH_PW'));

        $_SERVER = $serverBackup;
    }
}
