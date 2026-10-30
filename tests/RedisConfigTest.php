<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\RedisConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RedisConfigTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $config = new RedisConfig();

        self::assertSame('127.0.0.1', $config->host);
        self::assertSame(6379, $config->port);
        self::assertSame(0.0, $config->timeout);
        self::assertNull($config->password);
        self::assertNull($config->database);
    }

    public function testConstructorAcceptsNamedArguments(): void
    {
        $config = new RedisConfig(host: 'redis', database: 0);

        self::assertSame('redis', $config->host);
        self::assertSame(0, $config->database);
        self::assertSame(6379, $config->port);
    }

    public function testPropertiesAreReadonly(): void
    {
        $config = new RedisConfig();

        $this->expectException(Error::class);
        /** @phpstan-ignore-next-line */
        $config->host = 'outro';
    }

    public function testFromEmptyArrayUsesDefaults(): void
    {
        $config = RedisConfig::fromArray([]);

        self::assertSame('127.0.0.1', $config->host);
        self::assertSame(6379, $config->port);
        self::assertSame(0.0, $config->timeout);
        self::assertNull($config->password);
        self::assertNull($config->database);
    }

    public function testFromArrayWithNullValuesUsesDefaults(): void
    {
        $config = RedisConfig::fromArray([
            'host' => null,
            'port' => null,
            'timeout' => null,
            'password' => null,
            'database' => null,
        ]);

        self::assertSame('127.0.0.1', $config->host);
        self::assertSame(6379, $config->port);
        self::assertSame(0.0, $config->timeout);
        self::assertNull($config->password);
        self::assertNull($config->database);
    }

    public function testConfigNormalizesArrayValues(): void
    {
        $config = RedisConfig::fromArray([
            'host' => 'redis',
            'port' => '6380',
            'timeout' => '1.5',
            'password' => '',
            'database' => '2',
        ]);

        self::assertSame('redis', $config->host);
        self::assertSame(6380, $config->port);
        self::assertSame(1.5, $config->timeout);
        self::assertNull($config->password);
        self::assertSame(2, $config->database);
    }

    public function testFromArrayKeepsPasswordAndIntDatabaseZero(): void
    {
        $config = RedisConfig::fromArray(['password' => 'segredo', 'database' => 0]);

        self::assertSame('segredo', $config->password);
        self::assertSame(0, $config->database);
    }

    public function testFromArrayStringDatabaseZeroIsNotNull(): void
    {
        $config = RedisConfig::fromArray(['database' => '0']);

        self::assertSame(0, $config->database);
    }

    public function testFromArrayEmptyDatabaseIsNull(): void
    {
        self::assertNull(RedisConfig::fromArray(['database' => ''])->database);
    }

    public function testFromArrayAcceptsFrameworkTwoFormatWithExtraKeys(): void
    {
        $config = RedisConfig::fromArray([
            'host' => 'redis',
            'port' => 6379,
            'password' => null,
            'database' => '3',
            'prefix' => 'api:cache:',
            'ttl' => 60,
        ]);

        self::assertSame('redis', $config->host);
        self::assertSame(6379, $config->port);
        self::assertSame(0.0, $config->timeout);
        self::assertNull($config->password);
        self::assertSame(3, $config->database);
    }

    public function testFromArrayConvertsNonStringPasswordToString(): void
    {
        self::assertSame('123', RedisConfig::fromArray(['password' => 123])->password);
    }

    public function testFingerprintIsLowercaseSha256Hex(): void
    {
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (new RedisConfig())->fingerprint());
    }

    public function testFingerprintIsEqualForEqualConfigs(): void
    {
        $a = new RedisConfig(host: 'redis', port: 6380, timeout: 1.5, password: 'x', database: 2);
        $b = new RedisConfig(host: 'redis', port: 6380, timeout: 1.5, password: 'x', database: 2);

        self::assertSame($a->fingerprint(), $b->fingerprint());
    }

    public function testFingerprintIsEqualForArrayAndConstructorWithSameValues(): void
    {
        self::assertSame(
            (new RedisConfig(host: 'redis', database: 3))->fingerprint(),
            RedisConfig::fromArray(['host' => 'redis', 'database' => '3', 'prefix' => 'p:', 'ttl' => 60])->fingerprint(),
        );
    }

    public function testFingerprintDiffersForDatabaseZeroAndOne(): void
    {
        self::assertNotSame(
            (new RedisConfig(database: 0))->fingerprint(),
            (new RedisConfig(database: 1))->fingerprint(),
        );
    }

    /**
     * @return iterable<string, array{RedisConfig}>
     */
    public static function changedConfigs(): iterable
    {
        yield 'host' => [new RedisConfig(host: 'outro')];
        yield 'port' => [new RedisConfig(port: 6380)];
        yield 'timeout' => [new RedisConfig(timeout: 2.5)];
        yield 'password' => [new RedisConfig(password: 'segredo')];
        yield 'database' => [new RedisConfig(database: 0)];
    }

    #[DataProvider('changedConfigs')]
    public function testFingerprintChangesWhenAnyFieldChanges(RedisConfig $changed): void
    {
        self::assertNotSame((new RedisConfig())->fingerprint(), $changed->fingerprint());
    }
}
