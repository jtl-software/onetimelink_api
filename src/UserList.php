<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 10.08.17
 */

namespace JTL\Onetimelink;


/**
 * Class UserList
 * @package JTL\Onetimelink
 */
class UserList
{

    /**
     * @var array
     */
    private $userList;

    /**
     * @var array
     */
    private $adminUser;

    /**
     * UserList constructor.
     * @param array $userList
     * @param array $adminUser
     */
    public function __construct(array $userList, array $adminUser = [])
    {
        $this->userList = $userList;
        $this->adminUser = $adminUser;
    }

    /**
     * @param string $email
     * @return User
     */
    public function getUser(string $email): User
    {
        $user = User::createAnonymousUser();
        if (isset($this->userList[$email])) {

            $user = User::createFromCredentials(
                $email,
                PasswordHash::createFromHash($this->userList[$email]['password'])
            );
            $user->setIsActive($this->userList[$email]['active'] ?? true);
            $user->setMaxUploadSize($this->userList[$email]['maxUploadSize'] ?? 0);
            $user->setQuota($this->userList[$email]['quota'] ?? 0);

            if (\in_array($email, $this->adminUser, true)) {
                $user->setIsAdmin();
            }
        }

        return $user;
    }

    public function getUsers(): array
    {
        $_ = [];
        foreach ($this->userList as $email => $user) {
            $_[] = [
                'email' => $email,
                'isAdmin' => \in_array($email, $this->adminUser, true),
                'active' => $user['active'] ?? true,
                'maxUploadSize' => $user['maxUploadSize'] ?? 0,
                'quota' => $user['quota'] ?? 0,
                'created_at' => $user['created_at'] ?? null
            ];
        }

        return $_;
    }
}