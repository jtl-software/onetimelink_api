<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 03.08.17
 */

namespace JTL\Onetimelink\View;


class PlainView implements ViewInterface
{

    /**
     * @var string
     */
    private $data;

    /**
     * @var string
     */
    private $type;

    /**
     * PlainView constructor.
     * @param string $contentType
     */
    public function __construct(string $contentType = null)
    {
        $this->type = $contentType;
        if ($this->type === null) {
            $this->type = 'plain/text';
        }
    }

    public function set(string $key, $value): ViewInterface
    {
        $key = null;
        if (is_array($value)) {
            $value = json_encode($value);
        }

        if (is_object($value)) {
            $value = serialize($value);
        }

        $this->data = (string)$value;
        return $this;
    }

    public function getContentType(): string
    {
        return $this->type;
    }

    public function render(): string
    {
        return (string)$this->data;
    }


}