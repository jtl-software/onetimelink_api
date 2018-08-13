<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 13/08/18
 */

namespace JTL\Onetimelink;

use ApiTester;
use JTL\Onetimelink\RegistrationCest;

class ChangePasswordCest extends RegistrationCest
{
    protected $newPassword;

    /**
     * @depends tryLogin
     * @param ApiTester $I
     */
    public function tryChangePassword(ApiTester $I)
    {
        $this->newPassword = uniqid('newpassword', true);

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->amHttpAuthenticated($this->email, $this->password);
        $I->sendPOST('/user/update', [
            'oldPassword' => $this->password,
            'newPassword' => $this->newPassword
        ]);
        $I->seeResponseCodeIs(200);
    }

    /**
     * @depends tryChangePassword
     * @param ApiTester $I
     */
    public function tryLoginWithOldCredentials(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->amHttpAuthenticated($this->email, $this->password);
        $I->sendPOST('/login');
        $I->seeResponseCodeIs(403);
    }

    /**
     * @depends tryChangePassword
     * @param ApiTester $I
     */
    public function tryLoginWithNewCredentials(ApiTester $I)
    {
        $I->amHttpAuthenticated($this->email, $this->newPassword);
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

}