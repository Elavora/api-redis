<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\Contracts\RedisClient;
use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;

final class CountingRedisConnectionFactory implements RedisConnectionFactory
{
    public int $connections = 0;

    public function connect(RedisConfig $config): RedisClient
    {
        $this->connections++;

        return new FakeRedisClient();
    }
}

final class FakeRedisClient implements RedisClient
{
    /** @var array<string, string> */
    private array $values = [];

    /** @var array<string, list<string>> */
    private array $lists = [];

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
        $this->lists[$key][] = $value;

        return count($this->lists[$key]);
    }

    public function lPop(string $key): string|false
    {
        if (($this->lists[$key] ?? []) === []) {
            return false;
        }

        return array_shift($this->lists[$key]);
    }
}
