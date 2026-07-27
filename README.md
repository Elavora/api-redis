# elavora/api-redis

Cliente Redis opcional, encapsulado e reutilizavel para extensoes do framework
Elavora.

## Requisitos

- PHP `>=8.3`
- `ext-redis`
- `elavora/api-framework` `^1.0`

## Uso rapido

```php
use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use Elavora\Api\Extension\Redis\RedisExtension;
use Elavora\Api\Framework\Application;

$application = Application::create();
$application->extend(new RedisExtension());

$config = RedisConfig::fromArray([
    'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
    'port' => getenv('REDIS_PORT') ?: '6379',
    'password' => getenv('REDIS_PASSWORD') ?: null,
    'database' => getenv('REDIS_DATABASE') ?: '0',
]);

$factory = $application->container()->get(RedisConnectionFactory::class);
assert($factory instanceof RedisConnectionFactory);

$redis = $factory->connect($config);
$redis->set('health:redis', 'ok');
assert($redis->get('health:redis') === 'ok');
```

`RedisConfig` valida host, porta, timeout e database antes de abrir a conexao.
Falhas de conexao, autenticacao ou selecao de banco geram excecoes distintas e
nao incluem credenciais.

Consulte [docs/USO.md](docs/USO.md) para configuracao, factory personalizada e
comandos de validacao.
