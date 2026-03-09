<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBusInterface;
use Tester\Assert;
use Tests\Mocks\Message\EventMessage;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// SyncTransport routes through correct bus via BusNameStamp
// eventBus (allowNoHandlers: true) should NOT throw, messageBus (allowNoHandlers: false) should throw
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
						Tests\Mocks\Message\EventMessage: [sync]

					bus:
						messageBus:
						eventBus:
							allowNoHandlers: true
			NEON
			));
		})
		->build();

	// Dispatching through eventBus (allowNoHandlers: true) — no exception
	/** @var MessageBusInterface $eventBus */
	$eventBus = $container->getService('messenger.bus.eventBus.bus');
	$eventBus->dispatch(new EventMessage('hello'));

	// Dispatching through messageBus (allowNoHandlers: false) — throws
	/** @var MessageBusInterface $messageBus */
	$messageBus = $container->getService('messenger.bus.messageBus.bus');
	Assert::exception(
		static fn () => $messageBus->dispatch(new EventMessage('hello')),
		NoHandlerForMessageException::class,
		'No handler for message "Tests\Mocks\Message\EventMessage".',
	);
});
