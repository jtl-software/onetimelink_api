<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 24.08.18
 */

namespace JTL\Onetimelink\Controller\Query;


use JTL\Onetimelink\Config;
use JTL\Onetimelink\Controller\QueryInterface;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\UserQuota;
use JTL\Onetimelink\View\JsonView;

class GetUploadLimits implements QueryInterface
{
    /**
     * @var JsonView
     */
    private $view;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var int
     */
    private $maxUploadSize;

    /**
     * @var bool
     */
    private $isGuest;

    /**
     * @var int
     */
    private $quota;

    /**
     * @var string
     */
    private $identifier;

    /**
     * GetUploadLimits constructor.
     * @param Factory $factory
     * @param int $maxUploadSize
     * @param bool $isGuest
     * @param int $quota
     * @param string|null $identifier
     */
    function __construct(Factory $factory, int $maxUploadSize = 0, bool $isGuest = true, int $quota = 0, string $identifier = null)
    {
        $this->view = new JsonView();
        $this->config = $factory->getConfig();
        $this->maxUploadSize = $maxUploadSize;
        $this->isGuest = $isGuest;
        $this->quota = $quota;
        $this->identifier = $identifier;
    }

    public function run(): Response
    {
        $maxFileSize = $this->maxUploadSize;
        if($maxFileSize === 0 || $maxFileSize > $this->config->getMaxFileSize()){
            $maxFileSize = $this->config->getMaxFileSize();
        }
        if($this->isGuest === false && $this->quota !== 0){
            $usedQuota = UserQuota::getUsedQuotaForUser($this->identifier);
            $this->view->set('usedQuota', $usedQuota);
            $this->view->set('quota', $this->quota);
        }
        $this->view->set('maxFileSize', $maxFileSize);
        $this->view->set('chunkSize', $this->config->getChunkSize());
        return Response::createSuccessful($this->view);
    }
}