<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\View;

use JTL\Onetimelink\Header;
use JTL\Onetimelink\Response;

class JsonView implements ViewInterface
{

    /**
     * @var array
     */
    private $data;

    /**
     * JsonView constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @param $value
     * @return ViewInterface
     */
    public function set(string $key, $value): ViewInterface
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return 'application/json';
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return json_encode($this->data);
    }


}