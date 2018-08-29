<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 22.08.17
 */

namespace JTL\Onetimelink\Notification\Message;

abstract class AbstractMessage
{

    /**
     * @var string;
     */
    protected $recipient;

    /**
     * @var string
     */
    protected $subject = '';

    /**
     * @var string
     */
    protected $message = '';

    /**
     * AbstractMessage constructor.
     *
     * @param string $recipient
     * @param string $subject
     */
    public function __construct(string $recipient, string $subject)
    {
        $this->recipient = $recipient;
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
