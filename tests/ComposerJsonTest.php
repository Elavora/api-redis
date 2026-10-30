<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ComposerJsonTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $composer;

    protected function setUp(): void
    {
        $json = file_get_contents(__DIR__ . '/../composer.json');
        self::assertIsString($json);

        $this->composer = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testRequiresPhp83(): void
    {
        self::assertSame('>=8.3', $this->composer['require']['php'] ?? null);
    }

    public function testRequiresExtRedis(): void
    {
        self::assertSame('*', $this->composer['require']['ext-redis'] ?? null);
    }

    public function testRequiresFrameworkTwo(): void
    {
        self::assertSame('^2.0', $this->composer['require']['elavora/api-framework'] ?? null);
    }

    public function testBranchAliasIsOneDev(): void
    {
        self::assertSame('1.0-dev', $this->composer['extra']['branch-alias']['dev-main'] ?? null);
    }

    public function testAutoloadUnchanged(): void
    {
        self::assertSame(
            ['Elavora\\Api\\Extension\\Redis\\' => 'src/'],
            $this->composer['autoload']['psr-4'] ?? null,
        );
    }
}
