<?php
declare(strict_types=1);

namespace BlockSpamCallsPhp\Spam\Filter\Adapter;

readonly class NomoroboSpamscoreFilterAdapter implements SpamFilterAdapterInterface
{
    /**
     * @see https://console.twilio.com/us1/develop/add-ons/catalog/XB06d5274893cc9af4198667d2f7d74d09
     */
    const int IS_ROBOCALLER = 0;

    public function __construct(public array $options = []){}

    public function isSpam(): bool
    {
        if ($this->options["status"] === "fail"
            || empty($this->options["result"])
        ) {
            return true;
        }

        if ($this->options["status"] === "successful"
            && ! empty($this->options["result"])
            && $this->options["result"]["score"] === self::IS_ROBOCALLER
        ) {
            return true;
        }

        return false;
    }
}