<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\NativeRedisClient;
use Elavora\Api\Extension\Redis\NativeRedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NativeRedisConnectionFactoryTest extends TestCase
{
    public function testReturnsClientAfterSuccessfulInitialization(): void
    {
        $redis = $this->redisMock();
        $redis->expects(self::once())->method('connect')->willReturn(true);
        $redis->expects(self::once())->method('auth')->with('secret')->willReturn(true);
        $redis->expects(self::once())->method('select')->with(2)->willReturn(true);
        $redis->expects(self::never())->method('close');

        $factory = new NativeRedisConnectionFactory(static fn (): Redis => $redis);
        $client = $factory->connect(new RedisConfig(
            host: 'redis',
            port: 6379,
            password: 'secret',
            database: 2
        ));

        self::assertInstanceOf(NativeRedisClient::class, $client);
    }

    public function testClosesConnectionWhenConnectFails(): void
    {
        $redis = $this->redisMock();
        $redis->expects(self::once())->method('connect')->willReturn(false);
        $redis->expects(self::once())->method('close')->willReturn(true);

        $factory = new NativeRedisConnectionFactory(static fn (): Redis => $redis);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Falha ao conectar ao Redis.');

        $factory->connect(new RedisConfig(host: 'redis'));
    }

    public function testClosesConnectionAndHidesPasswordWhenAuthenticationFails(): void
    {
        $redis = $this->redisMock();
        $redis->expects(self::once())->method('connect')->willReturn(true);
        $redis->expects(self::once())->method('auth')->with('sensitive-password')->willReturn(false);
        $redis->expects(self::once())->method('close')->willReturn(true);

        $factory = new NativeRedisConnectionFactory(static fn (): Redis => $redis);

        try {
            $factory->connect(new RedisConfig(host: 'redis', password: 'sensitive-password'));
            self::fail('A autenticacao deveria falhar.');
        } catch (RuntimeException $exception) {
            self::assertSame('Falha na autenticacao do Redis.', $exception->getMessage());
            self::assertStringNotContainsString('sensitive-password', $exception->getMessage());
        }
    }

    public function testClosesConnectionWhenDatabaseSelectionFails(): void
    {
        $redis = $this->redisMock();
        $redis->expects(self::once())->method('connect')->willReturn(true);
        $redis->expects(self::once())->method('select')->with(99)->willReturn(false);
        $redis->expects(self::once())->method('close')->willReturn(true);

        $factory = new NativeRedisConnectionFactory(static fn (): Redis => $redis);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Falha ao selecionar o banco Redis.');

        $factory->connect(new RedisConfig(host: 'redis', database: 99));
    }

    /**
     * @return Redis&MockObject
     */
    private function redisMock(): Redis
    {
        return $this->createMock(Redis::class);
    }
}
