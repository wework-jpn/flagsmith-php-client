<?php

namespace Flagsmith\Engine\Utils;

class Hashing
{
    public function getHashedPercentageForObjectIds(array $objectIds, int $iterations = 1)
    {
        $toHash = str_repeat(implode(',', $objectIds), $iterations);
        $toHashValue = md5($toHash);

        $value = floatval(bcdiv((string) self::hexModulo($toHashValue, 9999), '9998', 5)) * 100;

        if ($value == 100) {
            return self::getHashedPercentageForObjectIds($objectIds, $iterations + 1);
        }

        return $value;
    }

    private static function hexModulo(string $hex, int $modulus): int
    {
        $result = 0;

        foreach (str_split($hex) as $character) {
            $result = (($result * 16) + intval($character, 16)) % $modulus;
        }

        return $result;
    }
}
