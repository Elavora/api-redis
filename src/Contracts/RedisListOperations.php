<?php

declare(strict_types=1);

namespace Elavora\Api\Extension\Redis\Contracts;

/**
 * Operacoes de lista para mover itens com seguranca (por exemplo, filas com confirmacao).
 *
 * E uma interface a parte: RedisClient nao mudou. Quem consome checa o suporte com instanceof.
 */
interface RedisListOperations extends RedisClient
{
    /**
     * Adiciona um valor ao inicio de uma lista Redis.
     */
    public function lPush(string $key, string $value): int|false;

    /**
     * Move atomicamente um item de uma lista para outra (LMOVE).
     *
     * @param string $from Ponta de saida: LEFT ou RIGHT.
     * @param string $to Ponta de entrada: LEFT ou RIGHT.
     * @return string|false Item movido ou false quando a origem esta vazia.
     */
    public function lMove(string $source, string $destination, string $from, string $to): string|false;

    /**
     * Remove ate $count ocorrencias de um valor, a partir do inicio (0 remove todas).
     */
    public function lRem(string $key, string $value, int $count): int|false;

    /**
     * Le os itens entre dois indices (0 a -1 e a lista toda).
     *
     * @return list<string>|false
     */
    public function lRange(string $key, int $start, int $stop): array|false;
}
