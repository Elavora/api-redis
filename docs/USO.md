# Guia de uso

Cliente Redis opcional, encapsulado e reutilizavel para extensoes do framework Elavora.

## Instalacao

```bash
composer require elavora/api-redis
```

## Quando usar

- Compartilhar conexoes Redis entre cache, fila e outras extensoes.
- Encapsular `ext-redis` atras de contratos do framework.
- Reutilizar configuracao de Redis em mais de um pacote.

## Exemplo rapido

```php
use Elavora\Api\Extension\Redis\RedisExtension;

$application->extend(new RedisExtension([
    'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('REDIS_PORT') ?: 6379),
]));
```

## Principais pontos de entrada

- `Elavora\Api\Extension\Redis\NativeRedisClient`
- `Elavora\Api\Extension\Redis\NativeRedisConnectionFactory`
- `Elavora\Api\Extension\Redis\RedisConfig`
- `Elavora\Api\Extension\Redis\RedisConnectionManager`
- `Elavora\Api\Extension\Redis\RedisExtension`

## Dependencias de runtime

- `ext-redis` `*`
- `elavora/api-framework` `^0.3.1`

## Validacao no projeto consumidor

Depois de instalar o pacote, rode os testes da aplicacao consumidora. Para uma verificacao isolada do pacote, use container:

```bash
docker run --rm -v "${PWD}:/workspace" -w "/workspace/api-redis" composer:2 composer validate --strict --no-check-publish
docker run --rm -v "${PWD}:/workspace" -w "/workspace/api-redis" composer:2 sh -lc "find . \\( -path ./.git -o -path ./vendor \\) -prune -o -name '*.php' -print0 | xargs -0 -r -n1 php -l"
```

## Observacoes

- Mantenha regras de produto fora deste pacote.
- Prefira configurar extensoes no bootstrap da aplicacao.
- Instale apenas os modulos que a aplicacao realmente usa.