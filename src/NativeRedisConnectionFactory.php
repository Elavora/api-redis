<?php

declare(strict_types=1);

namespace Elavora\Api\Extension\Redis;

use Elavora\Api\Extension\Redis\Contracts\RedisClient;
use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Redis;
use RuntimeException;

/**
 * Factory baseada na extensao nativa ext-redis.
 */
final class NativeRedisConnectionFactory implements RedisConnectionFactory
{
    /**
     * Abre uma conexao Redis usando ext-redis e retorna o adapter Elavora API.
     */
    public function connect(RedisConfig $config): RedisClient
    {
        $redis = new Redis();
        $connected = $redis->connect($config->host, $config->port, $config->timeout);

        if (!$connected) {
            throw new RuntimeException('Nao foi possivel conectar ao Redis.');
        }

        if ($config->password !== null) {
            $redis->auth($config->password);
        }

        if ($config->database !== null) {
            $redis->select($config->database);
        }

        return new NativeRedisClient($redis);
    }
}
