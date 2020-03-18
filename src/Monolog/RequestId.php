<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2020/03/17
 */

namespace JTL\Onetimelink\Monolog;


class RequestId
{
    /**
     * @var RequestId
     */
    private static $instance;

    /**
     * @var string
     */
    private $requestId;

    /**
     * RequestId constructor.
     * @param int $length
     */
    public function __construct(int $length = 10)
    {
        try {
            $randomness = bin2hex(random_bytes(64));
        } catch (\Exception $e) {
            $randomness = uniqid('', true);
        }

        $this->requestId = substr(sha1($randomness), 0, $length);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->requestId;
    }

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
