<?php
declare(strict_types=1);

namespace BlockSpamCallsPhp\Spam\Filter\Adapter;

interface SpamFilterAdapterInterface
{
    public function isSpam(): bool;
}