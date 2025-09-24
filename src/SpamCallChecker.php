<?php

declare(strict_types=1);

namespace BlockSpamCallsPhp;

use ArrayIterator;
use BlockSpamCallsPhp\Spam\Filter\Adapter\{
    NullFilterAdapter,
    SpamFilterAdapterInterface
};
use BlockSpamCallsPhp\Spam\FilterIterator\IsSpamBlockerAddonFilterIterator;
use Laminas\Filter\Word\UnderscoreToCamelCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;
use Twilio\TwiML\VoiceResponse;

readonly class SpamCallChecker
{
    private VoiceResponse $twiml;

    public function __construct(private ?LoggerInterface $logger = null)
    {
        $this->twiml = new VoiceResponse();
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $parsedBody = $request->getParsedBody();

        $response = $response->withStatus(200);
        $response = $response->withHeader("Content-Type", "application/xml");

        return $parsedBody;

        if (empty($parsedBody["AddOns"]["results"])) {
            $this->setSuccessTwiML();
            $response
                ->getBody()
                ->write("test");
//                ->write($this->twiml->asXML());

            return $response;
        }

        if ($this->isSpamCall($parsedBody["AddOns"]["results"])) {
            $response = $response->withStatus(412);
            $this->twiml->reject();
        } else {
            $this->setSuccessTwiML();
        }

        $response
            ->getBody()
            ->write($this->twiml->asXML());

        return $response;
    }

    /**
     * This method retrieves the appropriate spam blocker adapter for the spam blocker addon requested
     */
    private function getSpamBlockerAdapter(string $spamBlocker, array $addonOptions): ?SpamFilterAdapterInterface
    {
        $className = "\BlockSpamCallsPhp\Spam\Filter\Adapter\\" . (new UnderscoreToCamelCase())->filter($spamBlocker) . "FilterAdapter";
        return (class_exists($className))
            ? new $className($addonOptions)
            : new NullFilterAdapter();
    }

    /**
     * This method determines if the current call is a spam call or not
     */
    public function isSpamCall(array $results): bool
    {
        $isSpam = false;

        $spamAddons = new IsSpamBlockerAddonFilterIterator(new ArrayIterator($results));
        foreach ($spamAddons as $addonName => $addonOptions) {
            if ($this->getSpamBlockerAdapter($addonName, $addonOptions)->isSpam()) {
                $isSpam = true;
                break;
            }
        }

        return $isSpam;
    }

    private function setSuccessTwiML(): void
    {
        $this->twiml->say(
            "Welcome to the jungle.",
            ["voice" => "woman", "language" => "en-gb"]
        );
        $this->twiml->hangup();
    }
}
