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
        $mailHost = getenv('I9N_EMAIL_HOST') ?: 'example.com';
        $this->createdUserEmail = uniqid('email', true) . '@' . $mailHost;

        $I->amHttpAuthenticated(getenv('OTL_USERNAME'), getenv('OTL_PASSWORD'));
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/user/add', json_encode([
                'email' => $this->createdUserEmail,
                'password' => $password,
            ])
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

        $I->amHttpAuthenticated('tester', 'test');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/user/add', json_encode([
                'username' => $username,
                'password' => $password,
                'email' => $this->notCreatedUserEmail
            ])
        );
        $I->seeResponseCodeIs(403);
    }
}