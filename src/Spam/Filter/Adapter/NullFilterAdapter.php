<?php
declare(strict_types=1);

namespace BlockSpamCallsPhp\Spam\Filter\Adapter;

use BlockSpamCallsPhp\Spam\Filter\Adapter\SpamFilterAdapterInterface;

class NullFilterAdapter implements SpamFilterAdapterInterface
{
    public function isSpam(): bool
    {
        return true;
    }
}