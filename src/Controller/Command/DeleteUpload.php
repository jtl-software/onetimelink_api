<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 24.08.18
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\DAO\AttachmentDAO;
use JTL\Onetimelink\DAO\UploadDAO;
use JTL\Onetimelink\LinkHash;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\View\JsonView;
use RedBeanPHP\R;

class DeleteUpload implements CommandInterface
{
    /**
     * @var DatabaseStorage
     */
    private $storage;

    /**
     * @var string
     */
    private $token;

    /**
     * @var JsonView
     */
    private $view;


    function __construct(DatabaseStorage $storage, string $token)
    {
        $this->storage = $storage;
        $this->token = $token;
        $this->view = new JsonView();
    }

    public function execute(): Response
    {
        if($upload = UploadDAO::getUploadFromToken($this->token)){
            R::trash($upload->loadDBObject());
        }
        if($attachment = AttachmentDAO::getAttachmentFromHash(LinkHash::create($this->token))){
            $this->storage->deleteAttachment($attachment->getHash());
            R::trash($attachment->loadDBObject());
        }
        return Response::createSuccessful($this->view);
    }
}