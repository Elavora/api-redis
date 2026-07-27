<?php

declare(strict_types=1);

namespace Elavora\Api\Extension\Redis;

use InvalidArgumentException;

/**
 * Configuracao de conexao Redis compartilhada por extensoes.
 */
final class RedisConfig
{
    /**
     * @param string $host Host Redis.
     * @param int $port Porta Redis.
     * @param float $timeout Timeout de conexao em segundos.
     * @param string|null $password Senha Redis opcional.
     * @param int|null $database Banco Redis opcional.
     */
    public function __construct(
        public readonly string $host = '127.0.0.1',
        public readonly int $port = 6379,
        public readonly float $timeout = 0.0,
        public readonly ?string $password = null,
        public readonly ?int $database = null
    ) {
        if (trim($this->host) === '') {
            throw new InvalidArgumentException('O host Redis deve ser uma string nao vazia.');
        }

        if ($this->port < 1 || $this->port > 65535) {
            throw new InvalidArgumentException('A porta Redis deve estar entre 1 e 65535.');
        }

        if (!is_finite($this->timeout) || $this->timeout < 0) {
            throw new InvalidArgumentException('O timeout Redis deve ser um numero finito maior ou igual a zero.');
        }

        if ($this->database !== null && $this->database < 0) {
            throw new InvalidArgumentException('O database Redis deve ser um inteiro maior ou igual a zero.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $host = array_key_exists('host', $config)
            ? self::stringValue($config['host'], 'host')
            : '127.0.0.1';
        $port = array_key_exists('port', $config)
            ? self::integerValue($config['port'], 'port')
            : 6379;
        $timeout = array_key_exists('timeout', $config)
            ? self::floatValue($config['timeout'], 'timeout')
            : 0.0;
        $password = self::nullableStringValue($config['password'] ?? null, 'password');
        $database = self::nullableIntegerValue($config['database'] ?? null, 'database');

        return new self(
            host: $host,
            port: $port,
            timeout: $timeout,
            password: $password,
            database: $database
        );
    }

    /**
     * Chave interna usada para reutilizar conexoes equivalentes.
     */
    public function fingerprint(): string
    {
        return hash('sha256', json_encode([
            'host' => $this->host,
            'port' => $this->port,
            'timeout' => $this->timeout,
            'password' => $this->password,
            'database' => $this->database,
        ], JSON_THROW_ON_ERROR));
    }

    private static function stringValue(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("A configuracao Redis '{$field}' deve ser uma string.");
        }

        return $value;
    }

    private static function nullableStringValue(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::stringValue($value, $field);
    }

    private static function integerValue(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value) || preg_match('/^[0-9]+$/D', $value) !== 1) {
            throw new InvalidArgumentException("A configuracao Redis '{$field}' deve ser um inteiro.");
        }

        $normalized = ltrim($value, '0');
        $normalized = $normalized === '' ? '0' : $normalized;
        $maximum = (string) PHP_INT_MAX;

        if (strlen($normalized) > strlen($maximum)
            || (strlen($normalized) === strlen($maximum) && strcmp($normalized, $maximum) > 0)
        ) {
            throw new InvalidArgumentException("A configuracao Redis '{$field}' excede o limite de inteiro.");
        }

        return (int) $normalized;
    }

    private static function nullableIntegerValue(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::integerValue($value, $field);
    }

    private static function floatValue(mixed $value, string $field): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value) || trim($value) !== $value || !is_numeric($value)) {
            throw new InvalidArgumentException("A configuracao Redis '{$field}' deve ser numerica.");
        }

        return (float) $value;
    }
}
