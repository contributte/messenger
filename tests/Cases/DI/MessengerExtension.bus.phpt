<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Tester\Assert;
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
						commandBus:
						eventBus:
			NEON
			));
		})
		->build();

	Assert::count(3, $container->findByType(MessageBus::class));
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
								- Tests\Mocks\Middleware\SimpleMiddleware
			NEON
			));
		})
		->build();

	Assert::count(1, $container->findByType(MessageBus::class));
	Assert::count(4, $container->findByType(MiddlewareInterface::class));
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
