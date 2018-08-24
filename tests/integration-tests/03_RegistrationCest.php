<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 13/08/18
 */

namespace JTL\Onetimelink;

use ApiTester;

class RegistrationCest
{
    protected $email;
    protected $activationHash;
    protected $password;
    protected $authToken;
    protected $authSession;


    public function tryRegistration(ApiTester $I)
    {
        $mailHost = getenv('I9N_EMAIL_HOST') ?: 'example.com';
        $this->email = uniqid('email', true) . '@' . $mailHost;
        $this->password = uniqid('password', true);

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/user/add', json_encode([
            'email' => $this->email,
            'password' => $this->password
        ]));

        $userActivationSecret = getenv('I9N_ACTIVATION_SECRET') ?: '##secret##';
        $this->activationHash = sha1($userActivationSecret . $this->email);
        $I->seeResponseCodeIs(201);
    }

    /**
     * @depends tryRegistration
     * @param ApiTester $I
     */
    public function tryActivate(ApiTester $I)
    {
        $I->sendPOST('/user/activate/' . $this->activationHash);
        $I->seeResponseCodeIs(200);
    }

    /**
     * @depends tryActivate
     * @param ApiTester $I
     */
    public function tryLogin(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->amHttpAuthenticated($this->email, $this->password);
        $I->sendPOST('/login');
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();
        $data = json_decode($response, true);

        $I->assertTrue(isset($data['authtoken']));
        $I->assertTrue(isset($data['session']));
        $I->assertEquals($this->email, $data['authuser']);

        $this->authToken = $data['authtoken'];
        $this->authSession = $data['session'];
    }

    protected function getAuthParams(): array
    {
        return ['auth' => $this->authToken, 'ses' => $this->authSession];
    }

}