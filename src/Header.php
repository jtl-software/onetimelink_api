<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 03.08.17
 */

namespace JTL\Onetimelink;


class Header
{

    /**
     * @var array
     */
    private $header = [];

    /**
     * @var HeaderSend|null
     */
    private $headerSend;

    /**
     * Header constructor.
     *
     * @param HeaderSend|null $headerSend
     */
    public function __construct(HeaderSend $headerSend = null)
    {
        $this->headerSend = $headerSend;
        if ($this->headerSend === null) {
            $this->headerSend = new HeaderSend();
        }
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return Header
     */
    public function set(string $key, string $value): Header
    {
        $this->header[$key] = $value;
        return $this;
    }

    /**
     * @param string $location
     * @return Header
     */
    public function setLocation(string $location)
    {
        return $this->set('Location', $location);
    }

    /**
     *
     */
    public function send()
    {
        foreach ($this->header as $key => $value) {
            $this->headerSend->send($key, $value);
        }
    }
}