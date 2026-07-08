<?php

declare(strict_types=1);

namespace Elavora\Api\Extension\Redis;

use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\Extension;

/**
 * Registra a factory Redis reutilizavel no container.
 */
final class RedisExtension implements Extension
{
    /**
     * @param RedisConnectionFactory|null $factory Factory customizada para Redis.
     */
    public function __construct(private readonly ?RedisConnectionFactory $factory = null)
    {
    }

    /**
     * Registra a factory Redis compartilhada.
     */
    public function register(Application $application): void
    {
        RedisServiceRegistrar::register($application, $this->factory);
    }
}
