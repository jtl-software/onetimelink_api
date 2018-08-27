<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 24.08.18
 */

namespace JTL\Onetimelink;


use RedBeanPHP\R;

class UserQuota
{
    /**
     * @param $email
     * @return int
     */
    public function getUsedQuotaForUser($email){
        $usedQuota = R::getRow('SELECT SUM(size) AS size FROM attachment WHERE user_email = ?', [$email]);
        return $usedQuota['size'] ?? 0;
    }
}