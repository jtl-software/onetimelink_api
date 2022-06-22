<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 14.08.17
 */

namespace JTL\Onetimelink;


use ApiTester;

class AuthenticationCest
{

    private $authToken;
    private $authSession;

    public function tryAuthentication(ApiTester $I)
    {
        $I->wantTo('Successfully log in before creating a Guest Link');

        $I->amHttpAuthenticated(getenv('OTL_USERNAME'), getenv('OTL_PASSWORD'));
        $I->sendPOST('/login');
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();
        $data = json_decode($response, true);

        $I->assertTrue(isset($data['authtoken']));
        $I->assertTrue(isset($data['session']));
        $I->assertEquals(getenv('OTL_USERNAME'), $data['authuser']);

        $this->authToken = $data['authtoken'];
        $this->authSession = $data['session'];
    }

    /**
     * @depends tryAuthentication
     */
    public function tryToVerifyActiveSession(ApiTester $I)
    {
        $I->wantTo('Verify that my session is still active');

        $I->sendGET('/_', $this->getAuthParams());
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();
        $data = json_decode($response, true);

        $I->assertTrue(isset($data['session']));
        $I->assertEquals('active', $data['session']);
    }

    public function tryToVerifyInvalidSession(ApiTester $I)
    {
        $I->wantTo('Verify that a invalid session is marked a inactive');

        $I->sendGET('/_', ['auth' => uniqid(), 'ses' => uniqid()]);
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();
        $data = json_decode($response, true);

        $I->assertTrue(isset($data['session']));
        $I->assertEquals('inactive', $data['session']);
    }

    public function tryToFailAuthentication(ApiTester $I)
    {
        $I->wantTo('Test authentication with invalid credentials');

        $I->amHttpAuthenticated('arno nym', 'any-password-might-pass');
        $I->sendPOST('/login');
        $I->seeResponseCodeIs(403);
    }

    protected function getAuthParams(): array
    {
        return ['auth' => $this->authToken, 'ses' => $this->authSession];
    }
}