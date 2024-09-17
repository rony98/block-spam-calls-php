<?php
declare(strict_types=1);

namespace BlockSpamCallsPhp\Spam\FilterIterator;

class IsSpamBlockerAddonFilterIterator extends \FilterIterator
{
    const array ADDONS = [
        "marchex_cleancall",
        "nomorobo_spamscore",
    ];

    public function accept(): bool
    {
        return in_array($this->key(), self::ADDONS);
    }
}