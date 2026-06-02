<?php

declare(strict_types=1);

namespace FlagsmithTest\Engine\Utils;

use Flagsmith\Engine\Utils\Hashing;
use PHPUnit\Framework\TestCase;

final class HashingTest extends TestCase
{
    /**
     * @dataProvider knownPercentageProvider
     *
     * @param array<mixed> $objectIds
     */
    public function testHashedPercentageMatchesKnownGmpValues(array $objectIds, int $iterations, float $expected): void
    {
        self::assertEqualsWithDelta(
            $expected,
            Hashing::getHashedPercentageForObjectIds($objectIds, $iterations),
            0.000000000001,
        );
    }

    /**
     * @return iterable<string, array{0: array<mixed>, 1: int, 2: float}>
     */
    public static function knownPercentageProvider(): iterable
    {
        yield 'feature identity' => [['feature', 'identity'], 1, 29.925985197039406];
        yield 'feature identity repeated' => [['feature', 'identity'], 2, 33.17663532706541];
        yield 'segment test user' => [['1', 'test-user'], 1, 82.80656131226245];
        yield 'numeric identifiers' => [[1, 2, 3], 1, 18.393678735747148];
        yield 'email identifier' => [['segment_id', 'user@example.com'], 1, 36.59731946389278];
        yield 'multivariate identifier' => [['multivariate-feature', 'user-123'], 1, 25.845169033806766];
    }
}
