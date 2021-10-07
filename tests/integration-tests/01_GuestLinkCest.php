<?php

class GuestLinkCest extends \JTL\Onetimelink\AuthenticationCest
{

    private $formerCreatedLink;
    private $formerCreatedHash;
    private $resumableID;

    public function tryCreateGuestLinkUnauthenticated(ApiTester $I)
    {
        $I->wantTo('Create a Guest Link unauthenticated');

        $I->sendPOST('/create/guest');
        $I->seeResponseCodeIs(403);
    }


    /**
     * @depends tryAuthentication
     */
    public function tryCreateGuestLink(ApiTester $I)
    {
        $I->wantTo('Create a Guest Link as user Tester with');

        $I->amHttpAuthenticated(getenv('OTL_USERNAME'), getenv('OTL_PASSWORD'));
        $I->sendPOST('/login');
        $I->seeResponseCodeIs(200);

        $I->sendPOST('/create/guest', json_encode(array_merge(['amount' => 1], $this->getAuthParams())));
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $response = $I->grabResponse();
        $data = json_decode($response, true);

        $I->assertTrue(isset($data['links']));
        $I->assertCount(1, $data['links']);
        $I->assertTrue(isset($data['links'][0]['onetimelink']));

        $this->formerCreatedLink = $data['links'][0]['onetimelink'];
    }

    /**
     * @depends tryCreateGuestLink
     */
    public function tryCreateOneTimeLinkFromGuestLink(ApiTester $I)
    {
        $I->wantTo('Create a One Time Link as Guest Link Response');
        $this->resumableID = uniqid('1024-codeceptionjpg', true);
        $formerHash = explode('/', $this->formerCreatedLink)[1];

        $I->amHttpAuthenticated(getenv('OTL_USERNAME'), getenv('OTL_PASSWORD'));
        $I->sendPOST('/login');
        $I->seeResponseCodeIs(200);

        $I->sendPost('/request_upload/' . $formerHash);
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();
        $data = json_decode($response, true);
        $uploadToken = $data['uploadToken'];

        $I->sendPOST('/upload?' . http_build_query($this->getAuthParams()), [
            'resumableChunkNumber' => 1,
            'resumableChunkSize' => 4096,
            'resumableCurrentChunkSize' => 1024,
            'resumableFilename' => 'codeception.jpg',
            'resumableIdentifier' => $this->resumableID,
            'resumableRelativePath' => 'codeception.jpg',
            'resumableTotalChunks' => 1,
            'resumableTotalSize' => 1024,
            'resumableType' => 'image/jpg',
            'data' => 'test codeception image',
            'uploadToken' => $uploadToken,
        ]);

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST($this->formerCreatedLink, json_encode([
            'text' => $this->getTextContent(),
            'file0' => $uploadToken,
            'amount' => 1,
        ]));

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $response = $I->grabResponse();
        $data = json_decode($response, true);

        $I->assertTrue(isset($data['links']));
        $I->assertCount(1, $data['links']);
        $I->assertTrue(isset($data['links'][0]['onetimelink']));
        $I->assertTrue(isset($data['links'][0]['hash']));

        $this->formerCreatedLink = $data['links'][0]['onetimelink'];
        $this->formerCreatedHash = $data['links'][0]['hash'];
    }

    /**
     * @depends tryCreateOneTimeLinkFromGuestLink
     */
     /**
     public function tryToCheckNotificationEmail(ApiTester $I)
     {
        $I->wantTo('Create a to check if there is a notification Mail send.')

        $I->sendGET('http://localhost:8081/api/v2/messages');
        $response = $I->grabResponse();
        $emails = json_decode($response, true);

        $found = false;
        foreach ($emails['items'] as $mail) {
            if (isset($mail['Content']['Body']) && strpos($mail['Content']['Body'], $this->formerCreatedHash) !== false) {
                $found = true;
                break;
            }
        }
        $I->assertTrue($found);
     }
     **/

    /**
     * @depends tryCreateOneTimeLinkFromGuestLink
     */
    public function tryToReceiveOneTimeLinkContent(ApiTester $I)
    {
        $I->wantTo('Receive Content from Link');
        $I->sendGET($this->formerCreatedLink);
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();

        $I->assertEquals('test codeception image', $response);
    }

    private function getTextContent(): string
    {
        return 'foo bar baz';
    }
}