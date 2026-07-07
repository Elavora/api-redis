<?php

declare(strict_types=1);

namespace Elavora\Api\Extension\Redis\Contracts;

use Elavora\Api\Extension\Redis\RedisConfig;

/**
 * Cria clientes Redis para extensoes opcionais.
 */
interface RedisConnectionFactory
{
    /**
     * Retorna um cliente Redis para a configuracao informada.
     */
    public function connect(RedisConfig $config): RedisClient;
}
