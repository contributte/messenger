<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Default setup
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	Assert::count(1, $container->findByType(SerializerInterface::class));
	Assert::count(5, $container->findByType(MiddlewareInterface::class));
	Assert::count(5, $container->findByType(TransportFactoryInterface::class));
	Assert::count(0, $container->findByType(TransportInterface::class));
});

// Count transport factories
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transportFactory:
			NEON
			));
		})
		->build();

	Assert::count(5, $container->findByType(TransportFactoryInterface::class));
});

// Override transport factories
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transportFactory:
						redis1: Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory
						redis2: @redis
				services:
					redis: Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory
			NEON
			));
		})
		->build();

	Assert::count(8, $container->findByType(TransportFactoryInterface::class));
});

// Create transport from factory
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						memory1:
							dsn: in-memory://

						memory2:
							dsn: in-memory://
							options:
								serialize: false
							serializer: Symfony\Component\Messenger\Transport\Serialization\PhpSerializer

						redis1:
							dsn: "redis://redis:user@localhost/queue/group/consumer?lazy=true"

						sync1:
							dsn: "sync://"
				NEON
			));
		})
		->build();

	Assert::type(InMemoryTransport::class, $container->getService('messenger.transport.memory1'));
	Assert::type(InMemoryTransport::class, $container->getService('messenger.transport.memory2'));
	Assert::type(RedisTransport::class, $container->getService('messenger.transport.redis1'));
	Assert::type(SyncTransport::class, $container->getService('messenger.transport.sync1'));

	Assert::exception(
		static fn () => $container->getByType(TransportFactoryInterface::class)->createTransport('fake://', [], new PhpSerializer()),
		InvalidArgumentException::class,
		'No transport supports the given Messenger DSN.'
	);
});

// Failed transports
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						memory1:
							dsn: in-memory://
							failureTransport: memory2
						memory2:
							dsn: in-memory://
			NEON
			));
		})
		->build();

	Assert::count(1, $container->findByTag(MessengerExtension::FAILURE_TRANSPORT_TAG));
});

// Dynamic parameters
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withDynamicParameters(['sync_dsn' => 'sync://'])
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						sync:
							dsn: %sync_dsn%
			NEON
			));
		})
		->build();

	Assert::type(SyncTransport::class, $container->getByName('messenger.transport.sync'));
});
