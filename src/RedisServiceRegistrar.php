<?php

declare(strict_types=1);

namespace Elavora\Api\Extension\Redis;

use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Framework\Application;

/**
 * Garante um unico registro de conexoes Redis compartilhado entre extensoes.
 */
final class RedisServiceRegistrar
{
    /**
     * Registra a factory Redis compartilhada se ainda nao houver uma no container.
     */
    public static function register(Application $application, ?RedisConnectionFactory $factory = null): void
    {
        if ($application->container()->has(RedisConnectionFactory::class)) {
            return;
        }

        $application->container()->bind(
            RedisConnectionFactory::class,
            new RedisConnectionManager($factory ?? new NativeRedisConnectionFactory())
        );
    }
}
