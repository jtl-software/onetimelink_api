<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 03.08.17
 */

namespace JTL\Onetimelink;


class InputStream
{

    public function readFromStream()
    {
        return file_get_contents("php://input");
    }
}