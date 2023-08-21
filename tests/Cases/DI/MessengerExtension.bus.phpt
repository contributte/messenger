<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Tester\Assert;
use Tests\Mocks\Bus\BusWrapper;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Count buses
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						messageBus:
							middlewares: []
						commandBus:
							allowNoHandlers: true
							allowNoSenders: false
						eventBus:
			NEON
			));
		})
		->build();

	Assert::count(3, $container->findByType(MessageBus::class));
	Assert::count(3 * 5, $container->findByType(MiddlewareInterface::class));
});

// Buses with middlewares
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						messageBus:
							middlewares:
								dummy1: Tests\Mocks\Middleware\SimpleMiddleware
								dummy2: @middleware

				services:
					middleware: Tests\Mocks\Middleware\SimpleMiddleware
			NEON
			));
		})
		->build();

	Assert::count(1, $container->findByType(MessageBus::class));
	Assert::count(8, $container->findByType(MiddlewareInterface::class));
});

// Default middlewares disabled
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						messageBus:
							defaultMiddlewares: false
			NEON
			));
		})
		->build();

	Assert::count(1, $container->findByType(MessageBus::class));
	Assert::count(0, $container->findByType(MiddlewareInterface::class));
});

// Default middlewares disabled, with custom middlewares registered
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						commandBus:
						messageBus:
							defaultMiddlewares: false
							middlewares:
								dummy1: Tests\Mocks\Middleware\SimpleMiddleware
								dummy2: @middleware

				services:
					middleware: Tests\Mocks\Middleware\SimpleMiddleware
			NEON
			));
		})
		->build();

	Assert::count(2, $container->findByType(MessageBus::class));
	Assert::count(5 + 3, $container->findByType(MiddlewareInterface::class));
});

// Bus container
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						messageBus:
						commandBus:
						eventBus:
			NEON
			));
		})
		->build();

	/** @var ContainerInterface $busContainer */
	$busContainer = $container->getService('messenger.bus.container');

	Assert::type(MessageBus::class, $busContainer->get('messageBus'));
	Assert::type(MessageBus::class, $busContainer->get('commandBus'));
	Assert::type(MessageBus::class, $busContainer->get('eventBus'));
});

// Invalid bus class
Toolkit::test(function (): void {
	Assert::exception(static function (): void {
		Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						messageBus:
							class: Tests\Mocks\Bus\InvalidBus
			NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, "Failed assertion 'Specified bus class must implements \"MessageBusInterface\"' for item 'messenger › bus › messageBus › class' with value 'Tests\Mocks...'.");
});

// Bus class
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						messageBus:
							wrapper: Tests\Mocks\Bus\BusWrapper
			NEON
			));
		})
		->build();

	Assert::type(BusWrapper::class, $container->getByType(BusWrapper::class));
});
