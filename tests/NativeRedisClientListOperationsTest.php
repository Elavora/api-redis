<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\Contracts\RedisClient;
use Elavora\Api\Extension\Redis\Contracts\RedisListOperations;
use Elavora\Api\Extension\Redis\NativeRedisClient;
use PHPUnit\Framework\TestCase;

final class NativeRedisClientListOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            self::markTestSkipped('ext-redis nao esta carregada.');
        }
    }

    public function testImplementsListOperationsAndKeepsRedisClient(): void
    {
        $client = new NativeRedisClient($this->createMock(Redis::class));

        self::assertInstanceOf(RedisClient::class, $client);
        self::assertInstanceOf(RedisListOperations::class, $client);
        self::assertTrue(is_subclass_of(RedisListOperations::class, RedisClient::class));
    }

    public function testRedisClientDoesNotRequireListOperations(): void
    {
        $client = new class implements RedisClient {
            public function get(string $key): string|false
            {
                return false;
            }

            public function set(string $key, string $value): bool
            {
                return true;
            }

            public function setex(string $key, int $ttlSeconds, string $value): bool
            {
                return true;
            }

            public function del(string ...$keys): int|false
            {
                return 0;
            }

            public function rPush(string $key, string $value): int|false
            {
                return 1;
            }

            public function lPop(string $key): string|false
            {
                return false;
            }
        };

        self::assertNotInstanceOf(RedisListOperations::class, $client);
    }

    public function testLPushForwardsToRedis(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->expects(self::once())->method('lPush')->with('fila', 'item')->willReturn(3);

        self::assertSame(3, (new NativeRedisClient($redis))->lPush('fila', 'item'));
    }

    public function testLMoveForwardsDirectionsInLowerCase(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->expects(self::once())->method('lMove')->with('origem', 'destino', 'left', 'right')->willReturn('x');

        self::assertSame('x', (new NativeRedisClient($redis))->lMove('origem', 'destino', 'LEFT', 'Right'));
    }

    public function testLMoveReturnsFalseWhenSourceIsEmpty(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->method('lMove')->willReturn(false);

        self::assertFalse((new NativeRedisClient($redis))->lMove('a', 'b', 'LEFT', 'RIGHT'));
    }

    public function testLMoveRejectsInvalidDirectionBeforeCallingRedis(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->expects(self::never())->method('lMove');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A direcao deve ser LEFT ou RIGHT.');

        (new NativeRedisClient($redis))->lMove('a', 'b', 'UP', 'RIGHT');
    }

    public function testLRemForwardsToRedis(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->expects(self::once())->method('lRem')->with('fila', 'item', 1)->willReturn(1);

        self::assertSame(1, (new NativeRedisClient($redis))->lRem('fila', 'item', 1));
    }

    public function testLRangeForwardsToRedis(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->expects(self::once())->method('lRange')->with('fila', 0, -1)->willReturn(['a', 'b']);

        self::assertSame(['a', 'b'], (new NativeRedisClient($redis))->lRange('fila', 0, -1));
    }
}
