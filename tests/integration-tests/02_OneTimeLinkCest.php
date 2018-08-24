<?php

class OneTimeLinkCest extends \JTL\Onetimelink\AuthenticationCest
{

    private $formerCreatedLink;
    private $resumableID;

    public function tryCreateOneTimeLinkUnauthenticated(ApiTester $I)
    {
        $I->wantTo('Create a One Time Link unauthenticated');

        $I->sendPOST('/create', json_encode(['text' => $this->getTextContent()]));
        $I->seeResponseCodeIs(403);
    }

    /**
     * @depends tryAuthentication
     */
    public function tryCreateOneTimeLink(ApiTester $I)
    {
        $I->wantTo('Create a One Time Link as user Tester');
        $this->resumableID = uniqid('2048-codeceptionjpg', true);

        $I->sendPOST('/prepare_create?' . http_build_query($this->getAuthParams()), [
            'resumableChunkNumber' => 1,
            'resumableChunkSize' => 4096,
            'resumableCurrentChunkSize' => 2048,
            'resumableFilename' => 'codeception2.jpg',
            'resumableIdentifier' => $this->resumableID,
            'resumableRelativePath' => 'codeception2.jpg',
            'resumableTotalChunks' => 1,
            'resumableTotalSize' => 2048,
            'resumableType' => 'image/jpg',
            'data' => 'test codeception image',
        ]);

        $I->amHttpAuthenticated('otl-tester@jtl-software.com', 'this-is-a-passw0rd');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/create', json_encode([
            'text' => $this->getTextContent(),
            'file0' => $this->resumableID,
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
        $I->assertTrue(isset($data['links'][0]['user']));

        $this->formerCreatedLink = $data['links'][0]['onetimelink'];
    }

    /**
     * @depends tryCreateOneTimeLink
     */
    public function tryToReceiveOneTimeLinkContent(ApiTester $I)
    {
        $I->wantTo('Receive former created Content');
        $I->sendGET($this->formerCreatedLink);
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();

        $I->assertEquals('test codeception image', $response);
    }

    /**
     * @depends tryToReceiveOneTimeLinkContent
     */
    public function tryToReceiveOneTimeLinkSecondTime(ApiTester $I)
    {
        $I->wantTo('Receive a already received link twice');
        $I->sendGET($this->formerCreatedLink);
        $I->seeResponseCodeIs(404);
    }

    private function getTextContent(): string
    {
        return 'foo bar baz';
    }
}