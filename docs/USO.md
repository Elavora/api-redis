# Guia de uso

Cliente Redis opcional, encapsulado e reutilizavel para extensoes do framework
Elavora.

## Instalacao

```bash
composer require elavora/api-redis:^1.0
```

Requisitos de runtime:

- PHP `>=8.3`
- `ext-redis`
- `elavora/api-framework` `^1.0`

## Registro e conexao

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
    'timeout' => getenv('REDIS_TIMEOUT') ?: '1.5',
    'password' => getenv('REDIS_PASSWORD') ?: null,
    'database' => getenv('REDIS_DATABASE') ?: '0',
]);

$factory = $application->container()->get(RedisConnectionFactory::class);
assert($factory instanceof RedisConnectionFactory);

$redis = $factory->connect($config);
$redis->set('example:key', 'value');
assert($redis->get('example:key') === 'value');
$redis->del('example:key');
```

## Configuracao

`RedisConfig::fromArray()` aceita:

| Opcao | Tipo | Regra |
| --- | --- | --- |
| `host` | `string` | Nao vazio |
| `port` | `int` ou string inteira | Entre 1 e 65535 |
| `timeout` | `int`, `float` ou string numerica | Finito e maior ou igual a zero |
| `password` | `string` ou `null` | String vazia equivale a `null` |
| `database` | `int`, string inteira ou `null` | Maior ou igual a zero |

Tipos diferentes falham antes da conexao. A factory tambem verifica os
retornos de `connect`, `auth` e `select`. Conexoes incompletas sao descartadas,
e as mensagens de erro nunca incluem a senha.

## Factory personalizada

Uma factory personalizada implementa o mesmo contrato e pode decorar a
implementacao nativa:

```php
use Elavora\Api\Extension\Redis\Contracts\RedisClient;
use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Extension\Redis\NativeRedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use Elavora\Api\Extension\Redis\RedisExtension;

final class CustomRedisConnectionFactory implements RedisConnectionFactory
{
    public function __construct(
        private readonly RedisConnectionFactory $inner = new NativeRedisConnectionFactory()
    ) {
    }

    public function connect(RedisConfig $config): RedisClient
    {
        return $this->inner->connect($config);
    }
}

$application->extend(new RedisExtension(new CustomRedisConnectionFactory()));
```

## Qualidade

Os comandos nao dependem de Bash, `find` ou `xargs`:

```bash
composer validate --strict --no-check-publish
composer lint
composer analyse
composer test
composer check
```

`composer check` executa lint, PHPStan nivel 8 e PHPUnit. A suite de integracao
usa `REDIS_INTEGRATION=1` e valida conexao, autenticacao, selecao de banco,
PING no servico de teste, escrita e leitura contra Redis real.
