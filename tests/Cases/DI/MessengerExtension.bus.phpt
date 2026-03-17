<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\Bus\MessageBus as WrapperMessageBus;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\RoutableMessageBus;
use Tester\Assert;
use Tests\Mocks\Bus\BusWrapper;
use Tests\Mocks\Handler\SimpleHandler;
use Tests\Mocks\Message\SimpleMessage;
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

// RoutableMessageBus is registered but messageBus stays autowired by default
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	/** @var RoutableMessageBus $routableBus */
	$routableBus = $container->getService('messenger.bus.routable');
	Assert::type(RoutableMessageBus::class, $routableBus);

	Assert::same($routableBus, $container->getByType(RoutableMessageBus::class));
	Assert::type(MessageBus::class, $container->getByType(MessageBusInterface::class));
	Assert::same($container->getService('messenger.bus.messageBus.bus'), $container->getByType(MessageBusInterface::class));

	$rc = new ReflectionClass($routableBus);
	$prop = $rc->getProperty('fallbackBus');
	Assert::null($prop->getValue($routableBus));
});

// Custom bus autowiring
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						messageBus:
							autowired: false
						commandBus:
							autowired: true
			NEON
			));
		})
		->build();

	Assert::same($container->getService('messenger.bus.commandBus.bus'), $container->getByType(MessageBusInterface::class));
});

// RoutableMessageBus with explicit fallback bus
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					fallbackBus: messageBus
					bus:
						messageBus:
						commandBus:
							allowNoHandlers: true
			NEON
			));
		})
		->build();

	/** @var RoutableMessageBus $routableBus */
	$routableBus = $container->getService('messenger.bus.routable');

	$rc = new ReflectionClass($routableBus);
	$prop = $rc->getProperty('fallbackBus');
	Assert::type(MessageBus::class, $prop->getValue($routableBus));
});

// Real DI services: RoutableMessageBus requires Envelope, wrapper accepts plain messages
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					fallbackBus: messageBus
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

	/** @var RoutableMessageBus $routableBus */
	$routableBus = $container->getService('messenger.bus.routable');

	Assert::exception(
		static fn (): Envelope => $routableBus->dispatch(new SimpleMessage('plain-message')),
		InvalidArgumentException::class,
		'Messages passed to RoutableMessageBus::dispatch() must be inside an Envelope.',
	);

	/** @var SimpleHandler $handler */
	$handler = $container->getByType(SimpleHandler::class);
	Assert::null($handler->message);

	$routableBus->dispatch(Envelope::wrap(new SimpleMessage('wrapped-message')));
	Assert::type(SimpleMessage::class, $handler->message);
	Assert::same('wrapped-message', $handler->message->text);

	/** @var WrapperMessageBus $wrapperBus */
	$wrapperBus = $container->getByType(WrapperMessageBus::class);
	$wrapperBus->dispatch(new SimpleMessage('wrapper-message'));

	Assert::type(SimpleMessage::class, $handler->message);
	Assert::same('wrapper-message', $handler->message->text);
});
