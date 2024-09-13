<?php

declare(strict_types=1);

namespace BlockSpamCallsPhpTest;

use BlockSpamCallsPhp\SpamCallChecker;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface, StreamInterface};
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

class SpamCallCheckerTest extends TestCase
{
    private string $payload = <<<EOF
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
        "results":{}
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

    /**
     * This data provider returns an array where each array element contains a
     * list of one or more addons and whether, with those addons and their
     * configuration, whether the call should be marked as spam or not.
     */
    public static function addonProvider(): array
    {
        return [
            [
                json_decode('{
    "nomorobo_spamscore": {
        "request_sid": "XR9dae8488888888888888888888888888",
        "status": "failed",
        "message": "Request timed out",
        "code": 61001,
        "result": {}
    },
    "marchex_cleancall": {
        "request_sid": "XRe8b460aecefd41a6666666666666666a",
        "status": "successful",
        "message": null,
        "code": null,
        "result": {
            "result": {
                "recommendation": "PASS",
                "reason": "CleanCall"
            }
        }
    }
}', true),
                true
            ],
            [
                json_decode('{
    "nomorobo_spamscore": {
        "request_sid": "XR9dae8488888888888888888888888888",
        "status": "failed",
        "message": "Request timed out",
        "code": 61001,
        "result": {}
    }
}', true),
                true
            ],
            [
                json_decode('{
    "marchex_cleancall": {
        "request_sid": "XRe8b460aecefd41a6666666666666666a",
        "status": "successful",
        "message": null,
        "code": null,
        "result": {
            "result": {
                "recommendation": "BLOCK",
                "reason": "CleanCall"
            }
        }
    }
}', true),
                true
            ],
            [
                json_decode('{
    "marchex_cleancall": {
        "request_sid": "XRe8b460aecefd41a6666666666666666a",
        "status": "successful",
        "message": null,
        "code": null,
        "result": {
            "result": {
                "recommendation": "PASS",
                "reason": "CleanCall"
            }
        }
    }
}', true),
                false
            ],
            [
                json_decode('{
    "nomorobo_spamscore": {
      "request_sid": "XRxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
      "status": "successful",
      "message": null,
      "code": null,
      "result": {
        "status": "success",
        "message": "success",
        "score": 0
      }
    }
}', true),
                false
            ],
            [
                json_decode('{
    "nomorobo_spamscore": {
      "request_sid": "XRxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
      "status": "successful",
      "message": null,
      "code": null,
      "result": {
        "status": "success",
        "message": "success",
        "score": 1
      }
    }
}', true),
                true
            ],
            [
                json_decode('{}', true),
                false
            ],
        ];
    }

    /**
     * @dataProvider addonProvider
     */
    public function testCanMarkCallAsSpam(array $addonData, bool $isSpam): void
    {
        $payload = json_decode($this->payload, true);
        $payload["AddOns"]["results"] = $addonData;

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($payload);

        $checker = new SpamCallChecker();
        $result = $checker($request, new Response(), []);

        if ($isSpam) {
            $this->assertSame(412, $result->getStatusCode());
            $expectedTwiML = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<Response><Reject/></Response>

EOF;
        } else {
            $this->assertSame(200, $result->getStatusCode());
            $expectedTwiML = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<Response><Say voice="woman" language="en-gb">Welcome to the jungle.</Say><Hangup/></Response>

EOF;
        }

        $this->assertSame($expectedTwiML, (string) $result->getBody());
    }
}
