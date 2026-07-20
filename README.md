# elavora/api-redis

[![Packagist Version](https://img.shields.io/packagist/v/elavora/api-redis.svg?style=flat-square)](https://packagist.org/packages/elavora/api-redis)
[![PHP Version](https://img.shields.io/packagist/php-v/elavora/api-redis.svg?style=flat-square)](https://packagist.org/packages/elavora/api-redis)
[![Composer Quality](https://github.com/Elavora/api-redis/actions/workflows/quality.yml/badge.svg?branch=main)](https://github.com/Elavora/api-redis/actions/workflows/quality.yml)
[![CodeQL](https://github.com/Elavora/api-redis/actions/workflows/codeql.yml/badge.svg?branch=main)](https://github.com/Elavora/api-redis/actions/workflows/codeql.yml)
[![License](https://img.shields.io/packagist/l/elavora/api-redis.svg?style=flat-square)](LICENSE)
Cliente Redis opcional, encapsulado e reutilizavel para extensoes do framework Elavora.

Use este pacote quando uma extensao precisar de Redis sem depender da classe
nativa `Redis`. `RedisConnectionManager` reutiliza o mesmo cliente para
configuracoes iguais e permite trocar a factory por uma implementacao futura
de cluster, roteamento de leitura/escrita ou balanceamento.

```php
use Elavora\Api\Extension\Redis\RedisConfig;
use Elavora\Api\Extension\Redis\RedisExtension;

$application->extend(new RedisExtension());

$redis = $application->container()
    ->get(Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory::class)
    ->connect(RedisConfig::fromArray([
        'host' => 'redis',
        'port' => 6379,
        'database' => 0,
    ]));
```
