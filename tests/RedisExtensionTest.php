<?php

declare(strict_types=1);

use Elavora\Api\Extension\Redis\Contracts\RedisConnectionFactory;
use Elavora\Api\Extension\Redis\RedisConfig;
use Elavora\Api\Extension\Redis\RedisConnectionManager;
use Elavora\Api\Extension\Redis\RedisExtension;
use Elavora\Api\Extension\Redis\RedisServiceRegistrar;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\Extension;
use PHPUnit\Framework\TestCase;

final class RedisExtensionTest extends TestCase
{
    public function testImplementsFrameworkExtension(): void
    {
        self::assertInstanceOf(Extension::class, new RedisExtension());
    }

    public function testExtensionDoesNotOverrideExistingFactory(): void
    {
        $application = Application::create();
        $factory = new CountingRedisConnectionFactory();
        $application->container()->instance(RedisConnectionFactory::class, $factory);

        $application->extend(new RedisExtension());

        self::assertSame($factory, $application->container()->get(RedisConnectionFactory::class));
    }

    public function testRegistersManagerThatReusesConnections(): void
    {
        $application = Application::create();
        $factory = new CountingRedisConnectionFactory();

        $application->extend(new RedisExtension($factory));

        $manager = $application->container()->get(RedisConnectionFactory::class);
        self::assertInstanceOf(RedisConnectionManager::class, $manager);

        $first = $manager->connect(new RedisConfig(host: 'redis', database: 0));
        $second = $manager->connect(new RedisConfig(host: 'redis', database: 0));

        self::assertSame($first, $second);
        self::assertSame(1, $factory->connections);
    }

    public function testSecondExtensionKeepsFirstRegistration(): void
    {
        $application = Application::create();
        $first = new CountingRedisConnectionFactory();
        $second = new CountingRedisConnectionFactory();

        $application->extend(new RedisExtension($first));
        $manager = $application->container()->get(RedisConnectionFactory::class);
        $application->extend(new RedisExtension($second));

        $resolved = $application->container()->get(RedisConnectionFactory::class);
        self::assertSame($manager, $resolved);

        $resolved->connect(new RedisConfig());

        self::assertSame(1, $first->connections);
        self::assertSame(0, $second->connections);
    }

    public function testSecondExtensionBeforeResolvingStillUsesFirstFactory(): void
    {
        $application = Application::create();
        $first = new CountingRedisConnectionFactory();
        $second = new CountingRedisConnectionFactory();

        $application->extend(new RedisExtension($first));
        $application->extend(new RedisExtension($second));

        $application->container()->get(RedisConnectionFactory::class)->connect(new RedisConfig());

        self::assertSame(1, $first->connections);
        self::assertSame(0, $second->connections);
    }

    public function testDefaultExtensionRegistersManagerWithoutConnecting(): void
    {
        $application = Application::create();

        $application->extend(new RedisExtension());

        // Sem Redis disponivel: se abrisse conexao aqui, lancaria excecao.
        self::assertInstanceOf(
            RedisConnectionManager::class,
            $application->container()->get(RedisConnectionFactory::class),
        );
    }

    public function testRegistrarRegistersManagerWithGivenFactory(): void
    {
        $application = Application::create();
        $factory = new CountingRedisConnectionFactory();

        RedisServiceRegistrar::register($application, $factory);

        $manager = $application->container()->get(RedisConnectionFactory::class);
        self::assertInstanceOf(RedisConnectionManager::class, $manager);

        $manager->connect(new RedisConfig());
        $manager->connect(new RedisConfig());

        self::assertSame(1, $factory->connections);
    }

    public function testRegistrarDoesNotOverrideExistingInstance(): void
    {
        $application = Application::create();
        $existing = new CountingRedisConnectionFactory();
        $application->container()->instance(RedisConnectionFactory::class, $existing);

        RedisServiceRegistrar::register($application, new CountingRedisConnectionFactory());

        self::assertSame($existing, $application->container()->get(RedisConnectionFactory::class));
    }

    public function testRegistrarWithNullFactoryRegistersManager(): void
    {
        $application = Application::create();

        RedisServiceRegistrar::register($application);

        self::assertInstanceOf(
            RedisConnectionManager::class,
            $application->container()->get(RedisConnectionFactory::class),
        );
    }

    public function testExtensionConstructorRejectsArray(): void
    {
        $this->expectException(TypeError::class);

        /** @phpstan-ignore-next-line */
        new RedisExtension(['host' => 'redis']);
    }
}
