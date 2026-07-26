<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\NativeRedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use PHPUnit\Framework\TestCase;

final class RedisIntegrationTest extends TestCase
{
    public function testConnectsAuthenticatesSelectsAndReadsData(): void
    {
        $this->requireIntegrationEnvironment();

        $config = $this->validConfig(database: 2);
        $client = (new NativeRedisConnectionFactory())->connect($config);
        $key = 'integration:' . bin2hex(random_bytes(8));

        try {
            $this->assertServerRespondsToPing($config);
            self::assertTrue($client->set($key, 'ok'));
            self::assertSame('ok', $client->get($key));
        } finally {
            $client->del($key);
        }
    }

    public function testRejectsInvalidPasswordWithoutExposingIt(): void
    {
        $this->requireIntegrationEnvironment();
        $invalidPassword = 'invalid-integration-password';

        try {
            (new NativeRedisConnectionFactory())->connect(new RedisConfig(
                host: $this->host(),
                port: $this->port(),
                timeout: 2.0,
                password: $invalidPassword,
                database: 0
            ));
            self::fail('A autenticacao deveria falhar.');
        } catch (RuntimeException $exception) {
            self::assertSame('Falha na autenticacao do Redis.', $exception->getMessage());
            self::assertStringNotContainsString($invalidPassword, $exception->getMessage());
        }
    }

    public function testRejectsUnavailableDatabase(): void
    {
        $this->requireIntegrationEnvironment();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Falha ao selecionar o banco Redis.');

        (new NativeRedisConnectionFactory())->connect($this->validConfig(database: 999));
    }

    private function requireIntegrationEnvironment(): void
    {
        if (getenv('REDIS_INTEGRATION') !== '1') {
            self::markTestSkipped('Defina REDIS_INTEGRATION=1 para executar a integracao Redis.');
        }
    }

    private function validConfig(int $database): RedisConfig
    {
        return new RedisConfig(
            host: $this->host(),
            port: $this->port(),
            timeout: 2.0,
            password: $this->password(),
            database: $database
        );
    }

    private function host(): string
    {
        return getenv('REDIS_HOST') ?: 'redis';
    }

    private function port(): int
    {
        $port = getenv('REDIS_PORT');

        return $port === false ? 6379 : (int) $port;
    }

    private function password(): string
    {
        return getenv('REDIS_PASSWORD') ?: 'integration-secret';
    }

    private function assertServerRespondsToPing(RedisConfig $config): void
    {
        $redis = new Redis();
        self::assertTrue($redis->connect($config->host, $config->port, $config->timeout));
        self::assertTrue($redis->auth($config->password ?? ''));
        self::assertTrue($redis->select($config->database ?? 0));

        try {
            self::assertNotFalse($redis->ping());
        } finally {
            $redis->close();
        }
    }
}
