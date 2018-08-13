<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 24.08.17
 */

namespace JTL\Onetimelink\Storage;

use JTL\Onetimelink\Payload;
use JTL\Onetimelink\User;

/**
 * Class UserMetaStorage
 * @package JTL\Onetimelink\Storage
 */
class UserMetaStorage
{

    /**
     * @var User
     */
    private $user;

    /**
     * @var LocationDirectory
     */
    private $directory;

    /**
     * @var array
     */
    private $otlLinks = [];

    /**
     * @var array
     */
    private $topTags = [];

    /**
     * UserMetaStorage constructor.
     *
     * @param User $user
     * @param LocationDirectory $userFileDirectory
     */
    public function __construct(User $user, LocationDirectory $userFileDirectory)
    {
        $this->user = $user;
        $this->directory = $userFileDirectory;
        $this->load();
    }

    public function __destruct()
    {
        $this->persist();
    }

    /**
     * @param string $hash
     * @param array $payloads
     */
    public function appendLink(string $hash, array $payloads)
    {
        $metaData = [];

        /** @var Payload $payload */
        foreach ($payloads as $payload) {
            $metaData[] = $payload->getMetaData();
        }

        $this->otlLinks[$hash] = $metaData;
    }

    /**
     * @param string $hash
     */
    public function setToDeleted(string $hash)
    {
        if (isset($this->otlLinks[$hash]) && \is_array($this->otlLinks[$hash])) {
            /** @var MetaData $otlLink */
            foreach ($this->otlLinks[$hash] as $otlLink) {
                $otlLink->setDeleted();
            }
        }
    }

    public function getLinks(): array
    {
        $_ = [];
        /** @var array $metaArray */
        foreach ($this->otlLinks as $hash => $metaArray) {
            $metaDataArray = [];

            /** @var MetaData $meta */
            foreach ($metaArray as $meta) {
                $metaDataArray[] = $meta->toArray();
            }

            $_[] = ['hash' => $hash] + $metaDataArray;
        }
        return $_;
    }

    public function getTopTags(): array
    {
        return array_keys($this->topTags);
    }

    /**
     * Write User Meta Data
     */
    public function persist()
    {
        $links = $tagList = [];

        /** @var array $metaArray */
        foreach ($this->otlLinks as $hash => $metaArray) {
            $hasTags = false;

            /** @var MetaData $metaData */
            foreach ($metaArray as $metaData) {
                //skip old links
                if ($metaData->getCreated()->getTimestamp() < (time() - 3600 * 24 * 30)) {
                    continue;
                }

                if (!$hasTags) {
                    // group and count used tags
                    foreach ($metaData->getTags() as $tag) {
                        if (!isset($tagList[$tag])) {
                            $tagList[$tag] = 0;
                        }
                        $tagList[$tag]++;
                    }

                    $hasTags = true;
                }

                $links[$hash][] = $metaData->toArray();
            }
        }

        arsort($tagList);
        $topTags = \array_slice($tagList, 0, 50);

        file_put_contents($this->getUserFilename(), json_encode([
            'links' => $links,
            'tags' => $topTags,
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Load User Meta Data
     */
    private function load()
    {
        $this->otlLinks = [];
        $userfile = $this->getUserFilename();
        if (file_exists($userfile)) {
            $content = json_decode(file_get_contents($userfile), true);

            foreach ($content['links'] as $hash => $rawDataArray) {
                $links = [];

                /** @var array $rawData */
                foreach ($rawDataArray as $rawData) {
                    $links[] = MetaData::createFromExistingMetaData($rawData);
                }

                $this->otlLinks[$hash] = $links;
            }

            $this->topTags = $content['tags'] ?? [];
        }
    }

    /**
     * @return string
     */
    private function getUserFilename(): string
    {
        return (string)$this->directory . '/' . sha1((string)$this->user) . '.json';
    }
}