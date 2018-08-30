<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 03.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\View\JsonView;
use JTL\Onetimelink\View\PlainView;
use JTL\Onetimelink\View\ViewInterface;

/**
 * Class Response
 *
 * @package JTL\Onetimelink
 */
class Response
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var Header|null
     */
    private $header;

    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @param ViewInterface $view
     * @param Header|null $header
     * @return Response
     */
    public static function createSuccessful(ViewInterface $view, Header $header = null): Response
    {
        return new Response($view, 200, $header);
    }

    /**
     * @param ViewInterface $view
     * @param Header|null $header
     * @return Response
     */
    public static function createSuccessfulCreated(ViewInterface $view, Header $header = null): Response
    {
        return new Response($view, 201, $header);
    }

    /**
     * @param Header|null $header
     * @return Response
     */
    public static function createNotFound(Header $header = null): Response
    {
        return new Response(new PlainView(), 404, $header);
    }

    /**
     * @param Header|null $header
     * @return Response
     */
    public static function createForbidden(Header $header = null): Response
    {
        return new Response(new JsonView(), 403, $header);
    }

    /**
     * Response constructor.
     *
     * @param ViewInterface $view
     * @param int $responseCode
     * @param Header|null $header
     */
    public function __construct(ViewInterface $view, int $responseCode, Header $header = null)
    {
        $this->view = $view;
        $this->code = $responseCode;

        $this->header = $header;
        if ($this->header === null) {
            $this->header = new Header();
        }
    }

    /**
     * @return bool
     */
    public function isSuccessfulCreated(): bool
    {
        return $this->code === 201;
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->code === 200;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return Response
     */
    public function addHeader(string $key, string $value): Response
    {
        $this->header->set($key, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function sendResponse(): string
    {
        $this->header->set("Content-Type", $this->view->getContentType());
        $this->header->send();

        http_response_code($this->code);

        return $this->view->render();
    }
}
