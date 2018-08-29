<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Controller\Query;

use JTL\Onetimelink\Controller\AbstractObservable;
use JTL\Onetimelink\Controller\QueryInterface;
use JTL\Onetimelink\DAO\AttachmentDAO;
use JTL\Onetimelink\DAO\LinkDAO;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Header;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\PlainView;
use RedBeanPHP\OODBBean;

class ReadOneTimeLink extends AbstractObservable implements QueryInterface
{

    /** @var string */
    private $hash;

    /**
     * @var User
     */
    private $user;

    /**
     * @var DatabaseStorage
     */
    private $storage;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var bool
     */
    private $viewing;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * ReadOTL constructor.
     * @param DatabaseStorage $storage
     * @param string $hash
     * @param User $user
     * @param Request $request
     * @param Factory $factory
     * @param bool $viewing
     */
    public function __construct(
        DatabaseStorage $storage,
        string $hash,
        User $user,
        Request $request,
        Factory $factory,
        bool $viewing
    ) {
        $this->storage = $storage;
        $this->hash = $hash;
        $this->user = $user;
        $this->request = $request;
        $this->factory = $factory;
        $this->viewing = $viewing;
        $this->logger = $factory->createLogger();

        parent::__construct($this->factory->getConfig()->createNotifier());
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function run(): Response
    {
        $this->logger->info("read link OTL/{$this->hash}");

        $link = $this->storage->readLinkAsBean($this->hash);
        $linkDAO = LinkDAO::constructFromDB($link);
        $linkUser = User::createUserFromString($linkDAO->getUser());
        $attachments = $linkDAO->getAttachments();

        if ($link === null || $linkDAO->getDeleted()) {
            return Response::createNotFound();
        }

        if ($this->viewing) {
            // Protected links may only be viewed by logged in users
            if (!$this->user->isAuthenticated() && $linkDAO->isProtectedLink()) {
                return Response::createForbidden();
            }

            if (\count($attachments) === 1) {
                $attachment = AttachmentDAO::getAttachmentFromHash(reset($attachments)->hash);

                if ($attachment !== null
                    && $attachment->getFileName() === '-#-TEXTINPUT-#-'
                    && !$attachment->getDeleted()) {
                    $linkUser->setEmail($attachment->getUserEmail());

                    $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

                    $linkDAO->setDeleted($now);
                    $linkDAO->save();

                    if (!$this->user->equals($linkUser) && !$this->user->isAnonymous()) {
                        $this->notify(
                            $this->factory->getConfig()->createMessageForLinkDeleted(
                                $linkUser,
                                $linkDAO->getTags(),
                                $this->request->getBlurredClientIp(),
                                $this->request->getUserAgent()
                            )
                        );
                    }
                } elseif ($attachment->getDeleted()) {
                    return Response::createNotFound();
                }
            }

            return Response::createSuccessful(new PlainView());
        }

        $owner = null;
        /** @var OODBBean $attachment */
        foreach ($attachments as $attachment) {
            if ($attachment->name === '-#-TEXTINPUT-#-') {
                continue;
            }
            $payload = $this->storage->readAttachment($attachment->hash);
            $fileName = $this->factory->getConfig()->getTempDir() . '/';
            $fileName .= substr($attachment->hash, 0, 2) . '/';
            $fileName .= $attachment->hash;
            $attachmentName = $attachment->name;
            $owner = $payload->getMetaData()->getUser();
        }

        $tags = $linkDAO->getTags();

        $view = new PlainView('application/octet-stream');
        $header = new Header();

        if (!$owner->equals($this->user) && !$this->user->isAnonymous()) {
            $this->notify(
                $this->factory->getConfig()->createMessageForLinkDeleted(
                    $owner,
                    $tags,
                    $this->request->getBlurredClientIp(),
                    $this->request->getUserAgent()
                )
            );
        }

        $header->set('X-OTL-Status', 'deleted');
        $userMeta = $this->factory->createUserMetaStorage($owner);
        $userMeta->setToDeleted($this->hash);
        $this->storage->deleteLink($this->hash);
        $this->logger->info('Sending filename ' . $fileName);

        if ($fileName !== null && file_exists($fileName)) {
            $header->set('Content-Disposition', "attachment; filename=\"{$attachmentName}\"");
            $header->set('X-Accel-Redirect', $fileName);
            $header->set('X-Sendfile', $fileName);
        } else {
            return Response::createNotFound();
        }


        $this->logger->info("OTL/{$this->hash} picked up by user {$this->user}", ['attachments' => $attachments]);
        return Response::createSuccessful($view, $header);
    }
}
