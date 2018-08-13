<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 12.04.18
 */

namespace JTL\Onetimelink\Storage;

class UserStorage
{
    /**
     * @var string
     */
    private $filename;

    /**
     * UserStorage constructor.
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function write(array $data): bool
    {
        if (false === file_put_contents($this->filename, json_encode($data, JSON_PRETTY_PRINT))) {
            return false;
        }

        return true;
    }

    public function read(): array
    {
        if (!file_exists($this->filename)) {
            throw new \RuntimeException('Could not load user list');
        }

        return json_decode(file_get_contents($this->filename), true);
    }
}