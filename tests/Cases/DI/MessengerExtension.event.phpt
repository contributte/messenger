<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\EventListener\DispatchPcntlSignalListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnSignalsListener;
use Tester\Assert;
use Tests\Toolkit\Container;

require_once __DIR__ . '/../../bootstrap.php';

// Default event dispatcher
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	Assert::count(1, $container->findByType(EventDispatcherInterface::class));
	Assert::true($container->hasService('messenger.event.dispatcher'));
});

// Provided event dispatcher
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
		})
		->build();

	Assert::count(2, $container->findByType(EventDispatcherInterface::class));
	Assert::true($container->hasService('messenger.event.dispatcher'));

	Assert::false($container->isCreated('events.dispatcher'));
	Assert::false($container->isCreated('messenger.event.dispatcher'));
	Assert::type(EventDispatcher::class, $container->getByType(EventDispatcherInterface::class));
	Assert::true($container->isCreated('events.dispatcher'));
	Assert::false($container->isCreated('messenger.event.dispatcher'));
});

// Event listeners should be registered
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
		})
		->build();

	/** @var EventDispatcher $dispatcher */
	$dispatcher = $container->getService('messenger.event.dispatcher');

	$dispatcherListeners = $dispatcher->getListeners();
	$listeners = [];

	foreach ($dispatcherListeners as $listenersForEvent) {
		if (is_array($listenersForEvent)) {
			foreach ($listenersForEvent as $listenerForEvent) {
				$listeners[] = $listenerForEvent[0]::class;
			}
		}
	}

	$expectedRegisteredListeners = [
		DispatchPcntlSignalListener::class,
		SendFailedMessageForRetryListener::class,
		SendFailedMessageToFailureTransportListener::class,
		StopWorkerOnSignalsListener::class,
	];

	foreach ($expectedRegisteredListeners as $expectedRegisteredListener) {
		Assert::true(in_array($expectedRegisteredListener, $listeners, true));
	}
});
