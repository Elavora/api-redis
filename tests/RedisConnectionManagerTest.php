<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use Elavora\Api\Extension\Redis\RedisConnectionManager;
use PHPUnit\Framework\TestCase;

final class RedisConnectionManagerTest extends TestCase
{
    public function testImplementsConnectionFactory(): void
    {
        self::assertInstanceOf(
            RedisConnectionFactory::class,
            new RedisConnectionManager(new CountingRedisConnectionFactory()),
        );
    }

    public function testReusesConnectionForSameConfig(): void
    {
        $factory = new CountingRedisConnectionFactory();
        $manager = new RedisConnectionManager($factory);
        $config = new RedisConfig(host: 'redis', port: 6379, database: 0);

        $first = $manager->connect($config);
        $second = $manager->connect($config);

        self::assertSame($first, $second);
        self::assertSame(1, $factory->connections);
    }

    public function testReusesConnectionForEqualConfigObjects(): void
    {
        $factory = new CountingRedisConnectionFactory();
        $manager = new RedisConnectionManager($factory);

        $first = $manager->connect(new RedisConfig(host: 'redis', database: 0));
        $second = $manager->connect(new RedisConfig(host: 'redis', database: 0));

        self::assertSame($first, $second);
        self::assertSame(1, $factory->connections);
    }

    public function testReusesConnectionForEqualConfigBuiltFromArray(): void
    {
        $factory = new CountingRedisConnectionFactory();
        $manager = new RedisConnectionManager($factory);

        $first = $manager->connect(new RedisConfig(host: 'redis', database: 3));
        $second = $manager->connect(RedisConfig::fromArray([
            'host' => 'redis',
            'port' => 6379,
            'password' => null,
            'database' => '3',
            'prefix' => 'api:cache:',
            'ttl' => 60,
        ]));

        self::assertSame($first, $second);
        self::assertSame(1, $factory->connections);
    }

    public function testCreatesDifferentConnectionsForDifferentConfigs(): void
    {
        $factory = new CountingRedisConnectionFactory();
        $manager = new RedisConnectionManager($factory);

        $first = $manager->connect(new RedisConfig(host: 'redis', port: 6379, database: 0));
        $second = $manager->connect(new RedisConfig(host: 'redis', port: 6379, database: 1));

        self::assertNotSame($first, $second);
        self::assertSame(2, $factory->connections);
    }

    public function testKeepsEachConnectionAfterSwitchingConfigs(): void
    {
        $factory = new CountingRedisConnectionFactory();
        $manager = new RedisConnectionManager($factory);

        $zero = $manager->connect(new RedisConfig(database: 0));
        $one = $manager->connect(new RedisConfig(database: 1));

        self::assertSame($zero, $manager->connect(new RedisConfig(database: 0)));
        self::assertSame($one, $manager->connect(new RedisConfig(database: 1)));
        self::assertSame(2, $factory->connections);
    }

    public function testReturnsClientCreatedByInnerFactory(): void
    {
        $factory = new CountingRedisConnectionFactory();
        $manager = new RedisConnectionManager($factory);

        $client = $manager->connect(new RedisConfig());

        self::assertInstanceOf(FakeRedisClient::class, $client);
    }
}
