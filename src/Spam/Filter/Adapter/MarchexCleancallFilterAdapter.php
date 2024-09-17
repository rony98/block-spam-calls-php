<?php
declare(strict_types=1);

namespace BlockSpamCallsPhp\Spam\Filter\Adapter;

class MarchexCleancallFilterAdapter implements SpamFilterAdapterInterface
{
    public function __construct(public array $options = []){}

    public function isSpam(): bool
    {
        if ($this->options["status"] === "fail"
            || empty($this->options["result"])
            || empty($this->options["result"]["result"])
        ) {
            return true;
        }

        return $this->options["status"] === "successful" 
            && ! empty($this->options["result"]["result"])
            && $this->options["result"]["result"]["recommendation"] === "BLOCK";
    }
}