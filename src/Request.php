<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\Session\Session;

/**
 * Class Request
 *
 * @package JTL\Onetimelink
 */
class Request
{
    /**
     * @var array
     */
    private $get;

    /**
     * @var array
     */
    private $post;

    /**
     * @var array
     */
    private $server;

    /**
     * @var string
     */
    private $stream;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param bool $pruneGlobals
     *
     * @param InputStream|null $inputStream
     *
     * @return Request
     */
    public static function createFromGlobals($pruneGlobals = true, InputStream $inputStream = null): Request
    {
        $request = new Request($_GET, $_POST, $_SERVER, $inputStream);
        if ($pruneGlobals) {
            $_GET = $_POST = [];
        }

        return $request;
    }

    /**
     * Request constructor.
     * @param array $get
     * @param array $post
     * @param array $server
     * @param InputStream|null $binary
     */
    private function __construct(array $get, array $post, array $server, InputStream $binary = null)
    {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;

        $this->stream = $binary;
        if ($this->stream === null) {
            $this->stream = new InputStream();
        }

        if ($this->isPut()) {
            parse_str($this->stream->readFromStream(), $this->post);
        }

        $this->setPhpAuthInfo();
    }

    /**
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        if (!($this->session instanceof Session)) {
            $activeSessionId = $this->readGet('ses') ?? $this->readPost('ses') ?? null;
            $this->session = Session::start($activeSessionId);
        }
        return $this->session;
    }

    /**
     * @return null|string
     */
    public function getUri()
    {
        return $this->server['REQUEST_URI'] ?? null;
    }

    /**
     * @return null|string
     */
    public function getPath()
    {
        $uri = $this->getUri() ?? '';
        return explode('?', $uri)[0];
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->readServer('REQUEST_SCHEME') . '://' . $this->readServer('HTTP_HOST');
    }

    /**
     * @return null|string
     */
    private function getClientIp()
    {
        return $ip = $this->readServer('HTTP_CLIENT_IP')
            ?? $this->readServer('HTTP_X_FORWARDED_FOR')
            ?? $this->readServer('HTTP_X_FORWARDED')
            ?? $this->readServer('HTTP_FORWARDED_FOR')
            ?? $this->readServer('HTTP_FORWARDED')
            ?? $this->readServer('REMOTE_ADDR')
            ?? '0.0.0.0';
    }

    /**
     * @return string
     */
    public function getBlurredClientIp(): string
    {
        $ip = $this->getClientIp();
        return substr($ip, 0, strrpos($ip, '.')) . '.0';
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->readServer('HTTP_USER_AGENT') ?? 'unknown';
    }

    /**
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isHttpMethod('GET');
    }

    /**
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isHttpMethod('POST');
    }

    /**
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isHttpMethod('PUT');
    }

    /**
     * @return string
     */
    public function getHttpMethod(): string
    {
        return strtoupper($this->readServer('REQUEST_METHOD'));
    }

    /**
     * @return string
     */
    public function getStreamData(): string
    {
        return $this->stream->readFromStream();
    }

    /**
     * @param string $key
     * @return null|string
     */
    public function readGet(string $key)
    {
        if ($this->isValid($this->get, $key)) {
            return $this->get[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @return null|string
     */
    public function readPost(string $key)
    {
        if ($this->isValid($this->post, $key)) {
            return $this->post[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @return null|string
     */
    public function readServer(string $key)
    {
        if ($this->isValid($this->server, $key)) {
            return $this->server[$key];
        }

        return null;
    }

    /**
     * @return array|null
     */
    public function readInputAsJson()
    {
        return json_decode(file_get_contents('php://input'), true);
    }

    /**
     * @param array $data
     * @param string $key
     *
     * @return bool
     */
    private function isValid(array $data, string $key): bool
    {
        return isset($data[$key]);
    }

    /**
     * @param $method
     * @return bool
     */
    private function isHttpMethod($method): bool
    {
        return $this->getHttpMethod() === strtoupper($method);
    }

    /**
     * Set PHP Auth information
     *
     * https://stackoverflow.com/questions/3663520/php-auth-user-not-set
     */
    private function setPhpAuthInfo()
    {
        if (
            isset($this->server['REDIRECT_HTTP_AUTHORIZATION'])
            && !empty($this->server['REDIRECT_HTTP_AUTHORIZATION'])
        ) {

            $_ = base64_decode(substr($this->server['REDIRECT_HTTP_AUTHORIZATION'], 6));
            $_ = explode(':', $_);

            $this->server['PHP_AUTH_USER'] = isset($_[0]) ? $_[0] : null;
            $this->server['PHP_AUTH_PW'] = isset($_[1]) ? $_[1] : null;
        }
    }
}