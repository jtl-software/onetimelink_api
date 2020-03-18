<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2020/03/17
 */

namespace JTL\Onetimelink\Monolog;


class RequestIdProcessor
{
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        $record['extra']['request_id'] = RequestId::getInstance()->getId();
        return $record;
    }
}
