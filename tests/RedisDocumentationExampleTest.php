<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\Contracts\RedisClient;
use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use Elavora\Api\Extension\Redis\RedisExtension;
use Elavora\Api\Framework\Application;
use PHPUnit\Framework\TestCase;

final class RedisDocumentationExampleTest extends TestCase
{
    public function testDocumentedRegistrationAndCustomFactoryFlowExecutes(): void
    {
        $application = Application::create();
        $application->extend(new RedisExtension(new DocumentationRedisConnectionFactory()));

        $service = $application->container()->get(RedisConnectionFactory::class);
        self::assertInstanceOf(RedisConnectionFactory::class, $service);

        $redis = $service->connect(RedisConfig::fromArray([
            'host' => 'redis',
            'port' => '6379',
            'database' => '0',
        ]));

        self::assertTrue($redis->set('documentation:key', 'ok'));
        self::assertSame('ok', $redis->get('documentation:key'));
    }
}

final class DocumentationRedisConnectionFactory implements RedisConnectionFactory
{
    public function connect(RedisConfig $config): RedisClient
    {
        return new DocumentationRedisClient();
    }
}

final class DocumentationRedisClient implements RedisClient
{
    /** @var array<string, string> */
    private array $values = [];

    public function get(string $key): string|false
    {
        return $this->values[$key] ?? false;
    }

    public function set(string $key, string $value): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function setex(string $key, int $ttlSeconds, string $value): bool
    {
        return $this->set($key, $value);
    }

    public function del(string ...$keys): int|false
    {
        $removed = 0;

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->values)) {
                unset($this->values[$key]);
                $removed++;
            }
        }

        return $removed;
    }

    public function rPush(string $key, string $value): int|false
    {
        return 1;
    }

    public function lPop(string $key): string|false
    {
        return false;
    }
}
