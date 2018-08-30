<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 22.08.17
 */

namespace JTL\Onetimelink\Notification\Message;

class HTMLMessage extends AbstractMessage
{

    /**
     * @var string
     */
    private $templateFile;

    /**
     * @var \stdClass
     */
    private $templateData;

    /**
     * HTMLMessage constructor.
     * @param string $recipient
     * @param string $subject
     * @param string $templateFile
     * @param \stdClass $templateData
     */
    public function __construct(string $recipient, string $subject, string $templateFile, \stdClass $templateData)
    {
        $this->templateFile = $templateFile;
        $this->templateData = $templateData;

        parent::__construct($recipient, $subject);
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        $this->generateMessage();
        return parent::getMessage();
    }

    /**
     *
     */
    private function generateMessage()
    {
        if (!is_file($this->templateFile)) {
            throw new \RuntimeException("$this->templateFile not exists");
        }

        ob_start();
        $data = $this->templateData;
        require $this->templateFile;
        $this->message = ob_get_contents();
        ob_end_clean();
    }
}
