<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\Bus\BusRegistry;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;
use Tester\Assert;
use Tests\Mocks\Handler\SimpleHandler;
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
