<?php

declare(strict_types=1);

namespace Elavora\Api\Extension\Redis;

use Closure;
use Elavora\Api\Extension\Redis\Contracts\RedisClient;
use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Redis;
use RuntimeException;
use Throwable;

/**
 * Factory baseada na extensao nativa ext-redis.
 */
final class NativeRedisConnectionFactory implements RedisConnectionFactory
{
    /** @var Closure(): Redis */
    private readonly Closure $redisFactory;

    /**
     * @param (Closure(): Redis)|null $redisFactory Ponto de extensao para testes deterministas.
     */
    public function __construct(?Closure $redisFactory = null)
    {
        $this->redisFactory = $redisFactory ?? static fn (): Redis => new Redis();
    }

    /**
     * Abre uma conexao Redis usando ext-redis e retorna o adapter Elavora API.
     */
    public function connect(RedisConfig $config): RedisClient
    {
        $redis = ($this->redisFactory)();

        try {
            if (!$redis->connect($config->host, $config->port, $config->timeout)) {
                throw new RuntimeException('Falha ao conectar ao Redis.');
            }
        } catch (Throwable) {
            $this->discard($redis);
            throw new RuntimeException('Falha ao conectar ao Redis.');
        }

        if ($config->password !== null) {
            try {
                if (!$redis->auth($config->password)) {
                    throw new RuntimeException('Falha na autenticacao do Redis.');
                }
            } catch (Throwable) {
                $this->discard($redis);
                throw new RuntimeException('Falha na autenticacao do Redis.');
            }
        }

        if ($config->database !== null) {
            try {
                if (!$redis->select($config->database)) {
                    throw new RuntimeException('Falha ao selecionar o banco Redis.');
                }
            } catch (Throwable) {
                $this->discard($redis);
                throw new RuntimeException('Falha ao selecionar o banco Redis.');
            }
        }

        return new NativeRedisClient($redis);
    }

    private function discard(Redis $redis): void
    {
        try {
            $redis->close();
        } catch (Throwable) {
            // A conexao incompleta nao deve mascarar a falha da etapa original.
        }
    }
}
