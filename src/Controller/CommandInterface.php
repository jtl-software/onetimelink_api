<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Controller;

use JTL\Onetimelink\Response;

interface CommandInterface
{
    public function execute(): Response;
}