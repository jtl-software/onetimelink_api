<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 03.08.17
 */

namespace JTL\Onetimelink\Storage;

class LocationDirectory
{

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $directory
     *
     * @return LocationDirectory
     */
    public static function createFromExistingPath(string $directory)
    {
        if (!is_dir($directory)) {
            throw new \RuntimeException("Location '{$directory}' not exists or is no directory.");
        }

        return new LocationDirectory($directory);
    }

    /**
     * LocationDirectory constructor.
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->path = $directory;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (substr($this->path, strlen($this->path) - 1) !== '/') {
            $this->path .= '/';
        }
        return $this->path;
    }
}
