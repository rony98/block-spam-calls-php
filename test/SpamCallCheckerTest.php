<?php
declare(strict_types=1);

namespace BlockSpamCallsPhpTest;

use BlockSpamCallsPhp\SpamCallChecker;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

class SpamCallCheckerTest extends TestCase
{
    public function testCanRetrieveAddons()
    {
        $payload = <<<EOF
{
    "Called": "+12100000000",
    "ToState": "CA",
    "CallerCountry": "AU",
    "Direction": "inbound",
    "CallerState": "",
    "ToZip": "",
    "CallSid": "CA00000000000000000000000000000000",
    "To": "+12111111111",
    "CallerZip": "",
    "ToCountry": "US",
    "StirVerstat": "TN-Validation-Passed-C",
    "CallToken": "%7B%22parentCallInfoToken%22%3A%22eyJhbGciOiJFUzI1NiJ9.eyJjYWxsU20000000000000000000000Y2M5NjQ3ZWRiZT00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000xMTE0NTIifQ.5kaJr-b1Iqd3rVSRWSkr6p8-ZP_EAcsmFqh1OR4GN_asAUM21RpctLxdAy4NyBtgFSullJkLEtabl3lLVU-wRw%22%2C%22identityHeaderTokens%22%3A%5B%5D%7D",
    "CalledZip": "",
    "ApiVersion": "2010-04-01",
    "CalledCity": "",
    "CallStatus": "ringing",
    "AddOns": {
        "status":"successful",
        "message":null,
        "code":null,
        "results":{
            "nomorobo_spamscore":{
                "request_sid":"XR9dae8488888888888888888888888888",
                "status":"failed",
                "message":"Request timed out",
                "code":61001,
                "result":{}
            },
            "marchex_cleancall":{
                "request_sid":"XRe8b460aecefd41a6666666666666666a",
                "status":"successful",
                "message":null,
                "code":null,
                "result":{
                    "result":{
                        "recommendation":"PASS",
                        "reason":"CleanCall"
                    }
                }
            }
        }
    },
    "From": "+61000000000",
    "AccountSid": "AC98000000000000000000000000000000",
    "CalledCountry": "US",
    "CallerCity": "",
    "ToCity": "",
    "FromCountry": "AU",
    "Caller": "+61000000000",
    "FromCity": "",
    "CalledState": "CA",
    "FromZip": "",
    "FromState": ""
}
EOF;

        $addons = <<<EOF
{"nomorobo_spamscore":{"request_sid":"XR9dae84c6f858fdf2e1a39caa18944a28","status":"failed","message":"Request timed out","code":61001,"result":[]},"marchex_cleancall":{"request_sid":"XRe8b460aecefd41a669ce84a2515c003a","status":"successful","message":null,"code":null,"result":{"result":{"recommendation":"PASS","reason":"CleanCall"}}}}
EOF;


        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(json_decode($payload, true));

        $checker = new SpamCallChecker($this->createMock(LoggerInterface::class));
        $result = $checker($request, new Response(), []);
        $this->assertSame(412, $result->getStatusCode());

        $expectedTwiML = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<Response><Reject/></Response>

EOF;
        $this->assertSame($expectedTwiML, (string) $result->getBody());
    }
}