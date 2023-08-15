<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\Exception\LogicalException;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Nette\DI\Container as NetteContainer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Tester\Assert;
use Tests\Mocks\Handler\SimpleHandler;
use Tests\Mocks\Message\BarMessage;
use Tests\Mocks\Message\FooMessage;
use Tests\Mocks\Message\SimpleMessage;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// successful DIC registration
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

// default attribute values
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

	$descriptor = getHandlerDescriptor($container, new SimpleMessage('test'));
	Assert::same('messageBus', $descriptor->getOption('bus'));
	Assert::same(null, $descriptor->getOption('alias'));
	Assert::same('__invoke', $descriptor->getOption('method'));
	Assert::same(SimpleMessage::class, $descriptor->getOption('handles'));
	Assert::same(0, $descriptor->getOption('priority'));
	Assert::same(null, $descriptor->getOption('from_transport'));
});

// non-default attribute values
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

					bus:
						command: []
				services:
					- Tests\Mocks\Handler\NonDefaultMethodWithAttributeHandler
			NEON
			));
		})
		->build();

	$descriptor = getHandlerDescriptor($container, new SimpleMessage('test'), 'command');
	Assert::same('command', $descriptor->getOption('bus'));
	Assert::same(null, $descriptor->getOption('alias'));
	Assert::same('nonDefaultMethod', $descriptor->getOption('method'));
	Assert::same(SimpleMessage::class, $descriptor->getOption('handles'));
	Assert::same(10, $descriptor->getOption('priority'));
	Assert::same('sync', $descriptor->getOption('from_transport'));
});

// default tag values
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
					-
						class: Tests\Mocks\Handler\NoAttributeHandler
						tags:
							- contributte.messenger.handler
			NEON
			));
		})
		->build();

	$descriptor = getHandlerDescriptor($container, new SimpleMessage('test'));
	Assert::same('messageBus', $descriptor->getOption('bus'));
	Assert::same(null, $descriptor->getOption('alias'));
	Assert::same('__invoke', $descriptor->getOption('method'));
	Assert::same(SimpleMessage::class, $descriptor->getOption('handles'));
	Assert::same(0, $descriptor->getOption('priority'));
	Assert::same(null, $descriptor->getOption('from_transport'));
});

// non-default tag values
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

					bus:
						command: []

				services:
					-
						class: Tests\Mocks\Handler\NonDefaultMethodWithoutAttributeHandler
						tags:
							contributte.messenger.handler:
								bus: command
								alias: simple
								method: nonDefaultMethod
								handles: Tests\Mocks\Message\SimpleMessage
								priority: 10
								from_transport: sync
			NEON
			));
		})
		->build();
	$descriptor = getHandlerDescriptor($container, new SimpleMessage('test'), 'command');
	Assert::same('command', $descriptor->getOption('bus'));
	Assert::same('simple', $descriptor->getOption('alias'));
	Assert::same('nonDefaultMethod', $descriptor->getOption('method'));
	Assert::same(SimpleMessage::class, $descriptor->getOption('handles'));
	Assert::same(10, $descriptor->getOption('priority'));
	Assert::same('sync', $descriptor->getOption('from_transport'));
});

// handling of multiple messages in a single handler with attributes
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
						Tests\Mocks\Message\FooMessage: [memory]
						Tests\Mocks\Message\BarMessage: [memory]

				services:
					- Tests\Mocks\Handler\MultipleMethodsWithAttributesHandler
			NEON
			));
		})
		->build();

	$descriptor = getHandlerDescriptor($container, new FooMessage('test'));
	Assert::same('messageBus', $descriptor->getOption('bus'));
	Assert::same(null, $descriptor->getOption('alias'));
	Assert::same('whenFooMessageReceived', $descriptor->getOption('method'));
	Assert::same(FooMessage::class, $descriptor->getOption('handles'));
	Assert::same(0, $descriptor->getOption('priority'));
	Assert::same(null, $descriptor->getOption('from_transport'));

	$descriptor = getHandlerDescriptor($container, new BarMessage('test'));
	Assert::same('messageBus', $descriptor->getOption('bus'));
	Assert::same(null, $descriptor->getOption('alias'));
	Assert::same('whenBarMessageReceived', $descriptor->getOption('method'));
	Assert::same(BarMessage::class, $descriptor->getOption('handles'));
	Assert::same(0, $descriptor->getOption('priority'));
	Assert::same(null, $descriptor->getOption('from_transport'));
});

// handling of multiple messages in a single handler with tags
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
						Tests\Mocks\Message\FooMessage: [memory]
						Tests\Mocks\Message\BarMessage: [memory]

				services:
					-
						class: Tests\Mocks\Handler\MultipleMethodsWithoutAttributesHandler
						tags:
							contributte.messenger.handler:
								-
									method: whenFooMessageReceived
								-
									method: whenBarMessageReceived

			NEON
			));
		})
		->build();

	$descriptor = getHandlerDescriptor($container, new FooMessage('test'));
	Assert::same('messageBus', $descriptor->getOption('bus'));
	Assert::same(null, $descriptor->getOption('alias'));
	Assert::same('whenFooMessageReceived', $descriptor->getOption('method'));
	Assert::same(FooMessage::class, $descriptor->getOption('handles'));
	Assert::same(0, $descriptor->getOption('priority'));
	Assert::same(null, $descriptor->getOption('from_transport'));

	$descriptor = getHandlerDescriptor($container, new BarMessage('test'));
	Assert::same('messageBus', $descriptor->getOption('bus'));
	Assert::same(null, $descriptor->getOption('alias'));
	Assert::same('whenBarMessageReceived', $descriptor->getOption('method'));
	Assert::same(BarMessage::class, $descriptor->getOption('handles'));
	Assert::same(0, $descriptor->getOption('priority'));
	Assert::same(null, $descriptor->getOption('from_transport'));
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

function getHandlerDescriptor(NetteContainer $container, object $message, string $busName = 'messageBus'): HandlerDescriptor
{
	/** @var HandlersLocatorInterface $handlerLocator */
	$handlerLocator = $container->getByName(sprintf('messenger.bus.%s.locator', $busName));
	/** @var HandlerDescriptor $handlerDescriptor */
	$handlerDescriptor = $handlerLocator->getHandlers(new Envelope($message))[0] ?? null;
	Assert::notNull($handlerDescriptor);

	return $handlerDescriptor;
}
