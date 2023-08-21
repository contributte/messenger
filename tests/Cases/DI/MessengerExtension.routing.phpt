<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\Bus\BusRegistry;
use Contributte\Messenger\Exception\LogicalException;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;
use Tester\Assert;
use Tests\Mocks\Handler\InterfaceHandler;
use Tests\Mocks\Handler\SameHandler1;
use Tests\Mocks\Handler\SameHandler2;
use Tests\Mocks\Handler\SimpleHandler;
use Tests\Mocks\Handler\WildcardHandler;
use Tests\Mocks\Message\MessageImpl1;
use Tests\Mocks\Message\MessageImpl2;
use Tests\Mocks\Message\SameMessage;
use Tests\Mocks\Message\SimpleMessage;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Routing
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						sync:
							dsn: sync://

					routing:
						Tests\Mocks\Message\SimpleMessage: [sync]

				services:
					- Tests\Mocks\Handler\SimpleHandler
			NEON
			));
		})
		->build();

	/** @var BusRegistry $busRegistry */
	$busRegistry = $container->getByType(BusRegistry::class);
	$messageBus = $busRegistry->get('messageBus');

	$messageBus->dispatch(new SimpleMessage('foobar'));

	/** @var SimpleHandler $handler */
	$handler = $container->getByType(SimpleHandler::class);

	Assert::equal('foobar', $handler->message->text);
});

// No handler
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						sync:
							dsn: sync://

					routing:
						Tests\Mocks\Message\SimpleMessage: [sync]
			NEON
			));
		})
		->build();

	/** @var MessageBus $messageBus */
	$messageBus = $container->getByType(MessageBus::class);

	Assert::exception(
		static fn () => $messageBus->dispatch(new SimpleMessage('foobar')),
		NoHandlerForMessageException::class,
		'No handler for message "Tests\Mocks\Message\SimpleMessage".'
	);
});

// One message handled by more handlers
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						sync:
							dsn: sync://

					routing:
						Tests\Mocks\Message\SameMessage: [sync]

				services:
					- Tests\Mocks\Handler\SameHandler1
					- Tests\Mocks\Handler\SameHandler2
			NEON
			));
		})
		->build();

	/** @var MessageBus $messageBus */
	$messageBus = $container->getByType(MessageBus::class);
	$messageBus->dispatch(new SameMessage('foobar'));

	/** @var SameHandler2 $handler1 */
	$handler1 = $container->getByType(SameHandler1::class);
	Assert::equal('foobar', $handler1->message->text);

	/** @var SameHandler2 $handler2 */
	$handler2 = $container->getByType(SameHandler2::class);
	Assert::equal('foobar', $handler2->message->text);

	Assert::equal($handler1->message->text, $handler2->message->text);
});

// No transport
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			Container::of()
				->withDefaults()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addConfig(Helpers::neon(<<<'NEON'
						messenger:
							routing:
								Tests\Mocks\Message\SimpleMessage: [test]
					NEON
					));
				})
				->build();
		},
		LogicalException::class,
		'Invalid transport "test" defined for "Tests\Mocks\Message\SimpleMessage". Available transports "".'
	);
});

// Invalid transport
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			Container::of()
				->withDefaults()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addConfig(Helpers::neon(<<<'NEON'
						messenger:
							transport:
								memory1:
									dsn: in-memory://
								memory2:
									dsn: in-memory://
							routing:
								Tests\Mocks\Message\SimpleMessage: [test]
					NEON
					));
				})
				->build();
		},
		LogicalException::class,
		'Invalid transport "test" defined for "Tests\Mocks\Message\SimpleMessage". Available transports "memory1,memory2".'
	);
});

// Routing via interface
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						sync:
							dsn: sync://

					routing:
						Tests\Mocks\Message\MessageInterface: [sync]

				services:
					- Tests\Mocks\Handler\InterfaceHandler
			NEON
			));
		})
		->build();

	/** @var BusRegistry $busRegistry */
	$busRegistry = $container->getByType(BusRegistry::class);
	$messageBus = $busRegistry->get('messageBus');

	/** @var InterfaceHandler $handler */
	$handler = $container->getByType(InterfaceHandler::class);

	Assert::null($handler->message);

	$messageBus->dispatch(new MessageImpl1('1'));
	Assert::type(MessageImpl1::class, $handler->message);

	$messageBus->dispatch(new MessageImpl2('2'));
	Assert::type(MessageImpl2::class, $handler->message);
});

// Routing via wildcard
Toolkit::test(function: function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						sync:
							dsn: sync://

					routing:
						*: [sync]

				services:
					- Tests\Mocks\Handler\WildcardHandler
			NEON
			));
		})
		->build();

	/** @var BusRegistry $busRegistry */
	$busRegistry = $container->getByType(BusRegistry::class);
	$messageBus = $busRegistry->get('messageBus');

	/** @var WildcardHandler $handler */
	$handler = $container->getByType(WildcardHandler::class);

	Assert::null($handler->message);

	$messageBus->dispatch(new MessageImpl1('1'));
	Assert::type(MessageImpl1::class, $handler->message);
});
