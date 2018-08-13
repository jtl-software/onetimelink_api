<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Controller\Query;

use JTL\Onetimelink\Controller\QueryInterface;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\View\JsonView;
use Monolog\Logger;
use RedBeanPHP\OODBBean;

class CheckOneTimeLink implements QueryInterface
{

    private $hash;

    /**
     * @var DatabaseStorage
     */
    private $storage;

    /**
     * @var JsonView
     */
    private $view;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * ReadOTL constructor.
     * @param DatabaseStorage $storage
     * @param string $hash
     * @param JsonView $view
     * @param Logger $logger
     */
    public function __construct(DatabaseStorage $storage, string $hash, JsonView $view, Logger $logger)
    {
        $this->storage = $storage;
        $this->hash = $hash;
        $this->view = $view;
        $this->logger = $logger;
    }

    /**
     * @return Response
     */
    public function run(): Response
    {
        try {
            /** @var OODBBean $link */
            $link = $this->storage->readLinkAsBean($this->hash);

            if ($link === null
                || isset($link->deleted)
            ) {
                $this->view->set('alive', false);
                $this->logger->info("OTL/{$this->hash} not exists or already picked up");
                return Response::createSuccessful($this->view);
            }

            $data = [];
            $text = '';
            $protected = (bool)$link->protected;

            if ((int)$link->is_guest_link === 1) {
                $this->logger->info("OTL/{$this->hash} alive and is a guest link", ["link" => $link]);

                $this->view->set('alive', true);
                $this->view->set('user', $link->user);
                $this->view->set('tags', array_values(array_filter(explode(',', $link->tags), '\strlen')));
            } else {
                $this->logger->info("OTL/{$this->hash} is alive", ["link" => $link]);
                $alive = true;

                /**
                 * @var int $i
                 * @var OODBBean $attachment
                 */
                foreach ($link->sharedAttachmentList as $i => $attachment) {
                    $payloadData = [
                        'contentType' => $attachment->filetype,
                        'user' => $attachment->user,
                        'name' => $attachment->name,
                    ];

                    if ($attachment->filetype === 'text/plain'
                        && $attachment->name === '-#-TEXTINPUT-#-'
                        && !$attachment->deleted) {
                        $text = file_get_contents(
                            $this->storage->getAttachmentLocation($attachment->hash)
                        );
                    } else {
                        $data['file' . $i] = $payloadData;
                    }

                    if ($attachment->deleted) {
                        $alive = false;
                    }
                }

                $this->view->set('alive', $alive);
                $this->view->set('files', $data);
                $this->view->set('text', $text);
                $this->view->set('protected', $protected);
                $this->view->set('tags', array_values(array_filter(explode(',', $link->tags), '\strlen')));
            }
        }
        catch(\Exception $e) {
            error_log($e->getMessage());
            $this->view->set('alive', false);
        }

        return Response::createSuccessful($this->view);
    }

}