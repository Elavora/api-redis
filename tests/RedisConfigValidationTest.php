<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\RedisConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RedisConfigValidationTest extends TestCase
{
    public function testAcceptsBoundaryValues(): void
    {
        $minimum = new RedisConfig(host: 'redis', port: 1, timeout: 0.0, database: 0);
        $maximum = RedisConfig::fromArray([
            'host' => 'redis',
            'port' => '65535',
            'timeout' => '1.5',
            'database' => '15',
        ]);

        self::assertSame(1, $minimum->port);
        self::assertSame(0.0, $minimum->timeout);
        self::assertSame(0, $minimum->database);
        self::assertSame(65535, $maximum->port);
        self::assertSame(1.5, $maximum->timeout);
        self::assertSame(15, $maximum->database);
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('invalidArrayConfigProvider')]
    public function testRejectsInvalidArrayConfiguration(array $config): void
    {
        $this->expectException(InvalidArgumentException::class);

        RedisConfig::fromArray($config);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidArrayConfigProvider(): iterable
    {
        yield 'host vazio' => [['host' => '']];
        yield 'host apenas espacos' => [['host' => '   ']];
        yield 'host nao string' => [['host' => 123]];
        yield 'porta zero' => [['port' => 0]];
        yield 'porta acima do limite' => [['port' => 65536]];
        yield 'porta decimal' => [['port' => 6379.5]];
        yield 'porta nao numerica' => [['port' => 'redis']];
        yield 'porta booleana' => [['port' => true]];
        yield 'timeout negativo' => [['timeout' => -0.1]];
        yield 'timeout nao numerico' => [['timeout' => 'rapido']];
        yield 'timeout booleano' => [['timeout' => false]];
        yield 'database negativo' => [['database' => -1]];
        yield 'database decimal' => [['database' => 1.5]];
        yield 'database nao numerico' => [['database' => 'principal']];
        yield 'senha nao string' => [['password' => ['segredo']]];
    }

    public function testRejectsInvalidDirectConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RedisConfig(host: 'redis', port: 6379, timeout: INF);
    }
}
