<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: marius
 * Date: 2/17/22
 */

namespace JTL\Onetimelink\DAO;

use RedBeanPHP\R;

class ChunkDAO
{
    /** @var string */
    private $uploadToken;

    /** @var int */
    private $chunkNumber;

    public function __construct(string $uploadToken, int $chunkNumber)
    {
        $this->uploadToken = $uploadToken;
        $this->chunkNumber = $chunkNumber;
    }

    public function save(): void
    {
        $chunk = R::dispense('chunk');

        $chunk->token = $this->uploadToken;
        $chunk->chunkNumber = $this->chunkNumber;

        R::store($chunk);
    }

    public function hasCompletedAllChunks(string $token, int $chunkCount): bool
    {
        $complete = false;
        $chunkNumberList = [];

        $chunkList = R::find('chunk', 'token = ?', [$token]);

        foreach ($chunkList as $chunk) {
            $chunkNumberList[] = (int)$chunk->chunkNumber;
        }

        $chunkNumberList = array_unique($chunkNumberList);
        sort($chunkNumberList);

        if ($chunkNumberList === range(1, $chunkCount)) {
            $complete = true;
        }

        return $complete;
    }
}
