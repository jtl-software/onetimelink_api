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
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\LinkHash;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\UserQuota;
use JTL\Onetimelink\View\JsonView;
use RedBeanPHP\R;

class GenerateUploadToken implements CommandInterface {

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var JsonView
     */
    private $view;

    /**
     * @var int
     */
    private $maxUploadSize;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var DatabaseStorage
     */
    private $storage;

    /**
     * @var bool
     */
    private $isGuest;

    /**
     * @var
     */
    private $quota;

    /**
     * GenerateUploadToken constructor.
     * @param DatabaseStorage $storage
     * @param Request $request
     * @param Factory $factory
     * @param bool $isGuest
     * @param int $maxUploadSize
     * @param int $quota
     * @param string|null $identifier
     */
    public function __construct(DatabaseStorage $storage, Request $request, Factory $factory,bool $isGuest, int $maxUploadSize = 0,
                                string $identifier = null, int $quota = 0)
    {
        $this->factory = $factory;
        $this->request = $request;
        $this->maxUploadSize = $maxUploadSize;
        $this->view = new JsonView();
        $this->identifier = $identifier;
        $this->storage = $storage;
        $this->isGuest = $isGuest;
        $this->quota = $quota;
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function execute(): Response
    {
        $token = LinkHash::createUnique();
        if($this->isGuest === true){
            if($upload = UploadDAO::getUploadFromIdentifier($this->identifier)){
                $token = $upload->getToken();
                $this->removeExistingUpload($token);
            }
        }

        if($this->isGuest === false && $this->quota !== 0){
            $usedQuota = (new UserQuota())->getUsedQuotaForUser($this->identifier);
            if($usedQuota >= $this->quota){
                throw new \RuntimeException('Quota used');
            }
        }

        $uploadDAO = new UploadDAO($token,0, $this->maxUploadSize, false, $this->identifier,
            (new \DateTimeImmutable('now'))->format('c'));
        $uploadDAO->save();
        $this->view->set('uploadToken', $token);
        return Response::createSuccessful($this->view);
    }


    /**
     * @param string $token
     */
    private function removeExistingUpload(string $token){
        if($attachment = AttachmentDAO::getAttachmentFromHash(LinkHash::create($token))){
            $this->storage->deleteAttachment($attachment->getHash());
            R::trash($attachment->loadDBObject());
        }
    }
}