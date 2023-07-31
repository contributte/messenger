<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\Exception\LogicalException;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Tester\Assert;
use Tests\Mocks\Handler\SimpleHandler;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Handler
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						memory:
							dsn: in-memory://

					routing:
						Tests\Mocks\Message\SimpleMessage: [memory]

				services:
					- Tests\Mocks\Handler\SimpleHandler
			NEON
			));
		})
		->build();

	Assert::count(1, $container->findByType(TransportInterface::class));
	Assert::count(1, $container->findByType(SimpleHandler::class));
});

// Error: no invoke method
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			 Container::of()
				 ->withDefaults()
				 ->withCompiler(function (Compiler $compiler): void {
					$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
				services:
					- { factory: Tests\Mocks\Handler\NoMethodHandler, tags: [contributte.messenger.handler] }
			NEON
					));
				 })
				->build();
		},
		LogicalException::class,
		'Handler must have "Tests\Mocks\Handler\NoMethodHandler::__invoke()" method.'
	);
});

// Error: multiple attributes
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			Container::of()
				->withDefaults()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
				services:
					- Tests\Mocks\Handler\MultipleAttributesHandler
			NEON
					));
				})
				->build();
		},
		LogicalException::class,
		'Only attribute #[AsMessageHandler] can be used on class "Tests\Mocks\Handler\MultipleAttributesHandler"'
	);
});

// Error: multiple parameters handler
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			Container::of()
				->withDefaults()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
				services:
					- Tests\Mocks\Handler\MultipleParametersHandler
			NEON
					));
				})
				->build();
		},
		LogicalException::class,
		'Only one parameter is allowed in "Tests\Mocks\Handler\MultipleParametersHandler::__invoke()."'
	);
});
