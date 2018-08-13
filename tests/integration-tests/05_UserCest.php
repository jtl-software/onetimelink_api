<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 14.08.17
 */

namespace JTL\Onetimelink;


use ApiTester;

class UserCest extends AuthenticationCest
{

    private $createdUserEmail;
    private $notCreatedUserEmail;

    /**
     * @depends tryAuthentication
     */
    public function tryCreateNewUser(ApiTester $I)
    {
        $I->wantTo('Create new User');

        $password = uniqid('newpassword', true);
        $this->createdUserEmail = uniqid('email', true) . '@example.com';

        $I->amHttpAuthenticated('otl-tester@jtl-software.com', 'this-is-a-passw0rd');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/user/add', [
                'email' => $this->createdUserEmail,
                'password' => $password,
            ]
        );
        $I->seeResponseCodeIs(201);
    }

    /**
     * @depends tryAuthentication
     */
    public function tryCreateUserWithNotWhitelistedMail(ApiTester $I)
    {
        $I->wantTo('Create new User with not whitelisted mail');

        $username = uniqid('newuser', true);
        $password = uniqid('newpassword', true);
        $this->notCreatedUserEmail = uniqid('email', true) . '@not-allowed.de';

        $I->amHttpAuthenticated('tester', 'this-is-a-passw0rd');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/user/add', [
                'username' => $username,
                'password' => $password,
                'email' => $this->notCreatedUserEmail
            ]
        );
        $I->seeResponseCodeIs(403);
    }
}