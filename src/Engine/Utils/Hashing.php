<?php

declare(strict_types=1);

namespace Flagsmith\Engine\Utils;

use Brick\Math\BigInteger;

final class Hashing
{
    private const HASH_MODULUS = 9999;
    private const HASH_DIVISOR = '9998';

    /**
     * Given a list of object ids, get a deterministic percentage between 0
     * (inclusive) and 100 (exclusive) based on the MD5 hash of those ids.
     *
     * This intentionally mirrors the previous GMP-backed implementation and
     * the canonical Flagsmith engine algorithm:
     * md5(join(',', repeated ids)) as a base-16 integer, modulo 9999, divided
     * by 9998, multiplied by 100. The final division continues to use bcdiv
     * so ext-bcmath remains a required extension for the SDK.
     *
     * @param iterable<mixed> $objectIds
     */
    public static function getHashedPercentageForObjectIds(iterable $objectIds, int $iterations = 1): float
    {
        if ($iterations < 1) {
            $iterations = 1;
        }

        $ids = is_array($objectIds) ? array_values($objectIds) : iterator_to_array($objectIds, false);
        $idsToHash = [];

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($ids as $id) {
                $idsToHash[] = (string) $id;
            }
        }

        $hash = md5(implode(',', $idsToHash));
        $modulo = self::md5HexModulo($hash, self::HASH_MODULUS);
        $value = (float) bcdiv((string) $modulo, self::HASH_DIVISOR, 20) * 100;

        if ($value === 100.0) {
            return self::getHashedPercentageForObjectIds($ids, $iterations + 1);
        }

        return $value;
    }

    private static function md5HexModulo(string $hash, int $modulus): int
    {
        if (class_exists(BigInteger::class)) {
            return BigInteger::fromBase($hash, 16)->mod($modulus)->toInt();
        }

        $result = 0;
        foreach (str_split($hash) as $character) {
            $result = (($result * 16) + intval($character, 16)) % $modulus;
        }

        return $result;
    }
}
