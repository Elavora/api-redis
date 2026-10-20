# Guia de uso

Cliente Redis opcional, encapsulado e reutilizável para extensões do framework Elavora.

Este guia é a especificação do pacote. Tudo o que um teste precisa saber está aqui.

## Versão e compatibilidade

| Item | Valor |
|---|---|
| Versão deste pacote | `1.0.0` (primeira versão compatível com o framework 2) |
| `elavora/api-framework` | `^2.0` |
| PHP | `>=8.3` |
| `ext-redis` | `*` |

A `1.0.0` sai pelo PR com label `upgrade`. O workflow `tag_on_merge` sobe o major da
última tag (`v0.2.0`) e cria `v1.0.0`.

Pacotes que dependem deste (`elavora/api-cache-redis`, `elavora/api-queue-redis`) devem exigir:

```json
"elavora/api-redis": "^1.0"
```

Não existe compatibilidade com o framework `0.x` nem `1.x`. A versão `0.2.0` deste pacote
continua sendo a última para o framework `^0.3.1`.

### `composer.json`

O `composer.json` do pacote deve ter:

- `require.php` = `">=8.3"`
- `require.ext-redis` = `"*"`
- `require.elavora/api-framework` = `"^2.0"`
- `extra.branch-alias.dev-main` = `"1.0-dev"`
- `autoload.psr-4` = `"Elavora\\Api\\Extension\\Redis\\": "src/"` (sem mudança)
- `composer validate --strict --no-check-publish` sem erros.

### O que muda com o framework 2

Para este pacote, quase nada muda no código. O contrato de extensão do framework 2 é o mesmo:

- `Elavora\Api\Framework\Contracts\Extension` tem só `register(Application $application): void`.
- `Application::create()`, `Application::extend(Extension)` e `Application::container()` continuam iguais.
- O `Container` continua com `has()`, `bind()`, `instance()` e `get()`.
  `get()` guarda o objeto resolvido e devolve a mesma instância nas chamadas seguintes.

A mudança real é de versão: o framework 2 exige PHP 8.3. Por isso este pacote também passa a exigir PHP 8.3.

### Variáveis de ambiente `REDIS_*`

Este pacote **não lê** variáveis de ambiente. Quem lê é o framework, em
`Elavora\Api\Framework\Bootstrap\EnvironmentExtensions`. Ele monta um array e entrega para
`api-cache-redis` (quando `CACHE_DRIVER=redis`) e para `api-queue-redis` (quando instalado):

| Variável | Chave no array | Valor quando vazia ou ausente |
|---|---|---|
| `REDIS_HOST` | `host` | `'redis'` |
| `REDIS_PORT` | `port` (int) | `6379` |
| `REDIS_PASSWORD` | `password` (string) | `null` |
| `REDIS_DATABASE` | `database` (string, ex.: `'2'`) | `null` |

O array também traz chaves de cada extensão, como `prefix` e `ttl`.

Os dependentes devem converter esse array com `RedisConfig::fromArray()`. Por isso `fromArray()`
precisa aceitar `database` como string numérica e ignorar chaves que não conhece (veja abaixo).

## Instalação

```bash
composer require elavora/api-redis:^1.0
```

## Quando usar

- Compartilhar conexões Redis entre cache, fila e outras extensões.
- Encapsular `ext-redis` atrás de contratos do framework.
- Reutilizar configuração de Redis em mais de um pacote.

## Exemplo rápido

```php
use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use Elavora\Api\Extension\Redis\RedisExtension;

$application->extend(new RedisExtension());

$redis = $application->container()
    ->get(RedisConnectionFactory::class)
    ->connect(RedisConfig::fromArray([
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => getenv('REDIS_PORT') ?: 6379,
    ]));

$redis->set('chave', 'valor');
```

A versão `0.2.0` deste guia mostrava `new RedisExtension(['host' => ...])`. Isso está errado:
o construtor recebe uma factory, não um array. Passar um array gera `TypeError`.

## API pública

Namespace base: `Elavora\Api\Extension\Redis`.

### `Contracts\RedisClient` (interface)

Cliente usado pelas extensões. Os dependentes programam contra esta interface.

| Método | Retorno | O que faz |
|---|---|---|
| `get(string $key)` | `string\|false` | Lê a chave. `false` quando não existe. |
| `set(string $key, string $value)` | `bool` | Grava sem TTL. |
| `setex(string $key, int $ttlSeconds, string $value)` | `bool` | Grava com TTL em segundos. |
| `del(string ...$keys)` | `int\|false` | Remove as chaves. Devolve quantas existiam. |
| `rPush(string $key, string $value)` | `int\|false` | Adiciona no fim da lista. Devolve o novo tamanho. |
| `lPop(string $key)` | `string\|false` | Remove e devolve o primeiro da lista. `false` se vazia. |

A interface não muda na `1.0.0`. Uma classe de teste que implementa estes seis métodos com essas
assinaturas deve continuar válida.

### `Contracts\RedisConnectionFactory` (interface)

```php
public function connect(RedisConfig $config): RedisClient;
```

### `RedisConfig` (classe final)

Propriedades públicas `readonly`, todas preenchidas pelo construtor:

| Propriedade | Tipo | Padrão |
|---|---|---|
| `host` | `string` | `'127.0.0.1'` |
| `port` | `int` | `6379` |
| `timeout` | `float` | `0.0` |
| `password` | `?string` | `null` |
| `database` | `?int` | `null` |

O construtor aceita argumentos nomeados: `new RedisConfig(host: 'redis', database: 0)`.

#### `RedisConfig::fromArray(array $config): self`

Regras de normalização:

- `host`: convertido para string. Ausente ou `null` → `'127.0.0.1'`.
- `port`: convertido para int. Ausente ou `null` → `6379`.
- `timeout`: convertido para float. Ausente ou `null` → `0.0`.
- `password`: ausente, `null` ou `''` → `null`. Outro valor → string.
- `database`: ausente, `null` ou `''` → `null`. Outro valor → int.
- Chaves desconhecidas (ex.: `prefix`, `ttl`) são ignoradas, sem erro.

Exemplos:

| Entrada | Resultado |
|---|---|
| `[]` | host `'127.0.0.1'`, port `6379`, timeout `0.0`, password `null`, database `null` |
| `['host' => 'redis', 'port' => '6380', 'timeout' => '1.5', 'password' => '', 'database' => '2']` | host `'redis'`, port `6380`, timeout `1.5`, password `null`, database `2` |
| `['password' => 'segredo', 'database' => 0]` | password `'segredo'`, database `0` |
| `['database' => '0']` | database `0` (não `null`) |
| `['host' => 'redis', 'port' => 6379, 'password' => null, 'database' => '3', 'prefix' => 'api:cache:', 'ttl' => 60]` (formato do framework 2) | host `'redis'`, port `6379`, password `null`, database `3` |

#### `RedisConfig::fingerprint(): string`

- Devolve um hash SHA-256 em hexadecimal minúsculo (64 caracteres).
- Duas configurações com os mesmos cinco valores têm o mesmo fingerprint.
- Mudar qualquer um dos cinco valores muda o fingerprint.
  Ex.: `new RedisConfig(database: 0)` e `new RedisConfig(database: 1)` têm fingerprints diferentes.

### `RedisConnectionManager` (classe final, implementa `RedisConnectionFactory`)

```php
public function __construct(RedisConnectionFactory $factory = new NativeRedisConnectionFactory());
public function connect(RedisConfig $config): RedisClient;
```

- Abre a conexão pela factory interna só na primeira vez de cada fingerprint.
- Chamar `connect()` de novo com configuração igual (mesmo objeto ou outro objeto com os mesmos
  valores) devolve **a mesma instância** de `RedisClient`, sem chamar a factory de novo.
- Configurações diferentes geram clientes diferentes, um `connect()` da factory para cada.

Exemplo: com uma factory que conta chamadas,
`connect(new RedisConfig(host: 'redis', database: 0))` duas vezes → mesmo cliente, factory chamada 1 vez.
`database: 0` e depois `database: 1` → clientes diferentes, factory chamada 2 vezes.

### `NativeRedisConnectionFactory` (classe final, implementa `RedisConnectionFactory`)

- Cria `\Redis`, conecta com `host`, `port` e `timeout`.
- Se a conexão devolver `false`, lança `RuntimeException` com a mensagem
  `Nao foi possivel conectar ao Redis.`
- Se `password` não for `null`, chama `auth()`. Se `database` não for `null`, chama `select()`.
- Devolve um `NativeRedisClient`.

Esta classe precisa de um Redis real. Não faz parte dos testes unitários.

### `NativeRedisClient` (classe final, implementa `RedisClient`)

Adapter fino sobre `\Redis`. Recebe a instância no construtor e repassa cada método.
Também precisa de Redis real e fica fora dos testes unitários.

### `RedisServiceRegistrar` (classe final)

```php
public static function register(Application $application, ?RedisConnectionFactory $factory = null): void;
```

- Se o container já tem `RedisConnectionFactory::class` (por `bind` ou `instance`), não faz nada.
- Senão, registra em `RedisConnectionFactory::class` um `RedisConnectionManager` que usa `$factory`,
  ou `NativeRedisConnectionFactory` quando `$factory` é `null`.
- O objeto devolvido por `container()->get(RedisConnectionFactory::class)` é um `RedisConnectionManager`.
  Ele reaproveita conexões como descrito acima, mesmo quando a factory passada não reaproveita.

`Application` aqui é `Elavora\Api\Framework\Application` do framework 2.

### `RedisExtension` (classe final, implementa `Elavora\Api\Framework\Contracts\Extension`)

```php
public function __construct(?RedisConnectionFactory $factory = null);
public function register(Application $application): void;
```

- `register()` chama `RedisServiceRegistrar::register($application, $factory)`.
- Usada por `Application::create()->extend(new RedisExtension($factory))`.

Exemplos:

| Cenário | Resultado de `container()->get(RedisConnectionFactory::class)` |
|---|---|
| Container já tem uma factory `$f` registrada com `instance()`, depois `extend(new RedisExtension())` | `$f` (a mesma instância) |
| Container vazio, `extend(new RedisExtension($f))` | um `RedisConnectionManager`; dois `connect()` com a mesma config chamam `$f` 1 vez |
| `extend(new RedisExtension($f1))` e depois `extend(new RedisExtension($f2))` | o manager do primeiro registro; `connect()` usa `$f1`, nunca `$f2` |
| Container vazio, `extend(new RedisExtension())` | um `RedisConnectionManager`; nenhuma conexão é aberta até chamar `connect()` |

## Validação local

Os testes rodam no container `ghcr.io/elavora/php-dev:8.3-latest`, que já tem PHP 8.3 e Composer.
No PowerShell, na pasta do repo:

```powershell
docker run --rm -v "${PWD}:/origem" -w /origem ghcr.io/elavora/php-dev:8.3-latest sh -lc "git config --global --add safe.directory /origem && composer validate --strict --no-check-publish && composer update --no-interaction --ignore-platform-req=ext-redis && composer test"
```

Monte o repo em `/origem`. Não use `/src`.

A imagem não tem `ext-redis`. Por isso o `composer update` usa `--ignore-platform-req=ext-redis`.
Os testes unitários não usam a classe `Redis`.

## Observações

- Mantenha regras de produto fora deste pacote.
- Prefira configurar extensões no bootstrap da aplicação.
- Instale apenas os módulos que a aplicação realmente usa.
