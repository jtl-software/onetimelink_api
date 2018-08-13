<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

declare(strict_types=1);

namespace JTL\Onetimelink;

use JTL\Onetimelink\Storage\MetaData;

class Payload
{
    /**
     * @var string
     */
    private $data;

    /**
     * @var MetaData
     */
    private $metaData;

    /**
     * Payload constructor.
     * @param string $data
     * @param MetaData $metaData
     */
    public function __construct(string $data, MetaData $metaData)
    {
        $this->data = $data;
        $this->metaData = $metaData;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return MetaData
     */
    public function getMetaData(): MetaData
    {
        return $this->metaData;
    }
}