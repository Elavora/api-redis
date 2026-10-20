# elavora/api-redis

Cliente Redis opcional, encapsulado e reutilizável para extensões do framework Elavora.

- Versão `1.0.0`: exige `elavora/api-framework` `^2.0` e PHP `>=8.3`.
- Para o framework `^0.3.1`, use a versão `0.2.0`.

Use este pacote quando uma extensão precisar de Redis sem depender da classe
nativa `Redis`. `RedisConnectionManager` reutiliza o mesmo cliente para
configurações iguais e permite trocar a factory por uma implementação futura
de cluster, roteamento de leitura/escrita ou balanceamento.

```php
use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use Elavora\Api\Extension\Redis\RedisExtension;

$application->extend(new RedisExtension());

$redis = $application->container()
    ->get(RedisConnectionFactory::class)
    ->connect(RedisConfig::fromArray([
        'host' => 'redis',
        'port' => 6379,
        'database' => 0,
    ]));
```

A especificação completa (API, normalização, compatibilidade e testes) está em [docs/USO.md](docs/USO.md).
