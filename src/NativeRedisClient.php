<?php

declare(strict_types=1);

namespace Elavora\Api\Extension\Redis;

use Elavora\Api\Extension\Redis\Contracts\RedisListOperations;
use InvalidArgumentException;
use Redis;

/**
 * Adapter do cliente nativo ext-redis para o contrato Elavora API.
 */
final class NativeRedisClient implements RedisListOperations
{
    public function __construct(private readonly Redis $redis) {}

    public function get(string $key): string|false
    {
        return $this->redis->get($key);
    }

    public function set(string $key, string $value): bool
    {
        return $this->redis->set($key, $value);
    }

    public function setex(string $key, int $ttlSeconds, string $value): bool
    {
        return $this->redis->setex($key, $ttlSeconds, $value);
    }

    public function del(string ...$keys): int|false
    {
        return $this->redis->del($keys);
    }

    public function rPush(string $key, string $value): int|false
    {
        return $this->redis->rPush($key, $value);
    }

    public function lPop(string $key): string|false
    {
        return $this->redis->lPop($key);
    }

    public function lPush(string $key, string $value): int|false
    {
        return $this->redis->lPush($key, $value);
    }

    public function lMove(string $source, string $destination, string $from, string $to): string|false
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if (!in_array($from, ['LEFT', 'RIGHT'], true) || !in_array($to, ['LEFT', 'RIGHT'], true)) {
            throw new InvalidArgumentException('A direcao deve ser LEFT ou RIGHT.');
        }

        return $this->redis->lMove($source, $destination, strtolower($from), strtolower($to));
    }

    public function lRem(string $key, string $value, int $count): int|false
    {
        return $this->redis->lRem($key, $value, $count);
    }

    public function lRange(string $key, int $start, int $stop): array|false
    {
        return $this->redis->lRange($key, $start, $stop);
    }
}
