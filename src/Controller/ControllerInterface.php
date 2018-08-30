<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 03.08.17
 */

namespace JTL\Onetimelink\Controller;

use JTL\Onetimelink\Response;

interface ControllerInterface
{
    public function run(): Response;
}
