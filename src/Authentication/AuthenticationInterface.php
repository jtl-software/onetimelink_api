<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Authentication;

use JTL\Onetimelink\User;
use JTL\Onetimelink\UserList;

interface AuthenticationInterface
{
    public function authenticate(UserList $userList): User;

    public function generateAuthToken(User $user): string;
}
