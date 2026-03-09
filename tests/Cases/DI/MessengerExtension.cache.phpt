<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Command\StopWorkersCommand;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// StopWorkersCommand not registered without cache
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	Assert::count(0, $container->findByType(StopWorkersCommand::class));
});

// StopWorkersCommand registered with cache
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					cache: @cachePool

				services:
					cachePool: Tests\Mocks\Cache\DummyCachePool
			NEON
			));
		})
		->build();

	Assert::count(1, $container->findByType(StopWorkersCommand::class));
});

// StopWorkerOnRestartSignalListener registered with cache
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					cache: @cachePool

				services:
					cachePool: Tests\Mocks\Cache\DummyCachePool
			NEON
			));
		})
		->build();

	/** @var EventDispatcher $dispatcher */
	$dispatcher = $container->getService('messenger.event.dispatcher');
	$listeners = [];

	foreach ($dispatcher->getListeners() as $listenersForEvent) {
		if (is_array($listenersForEvent)) {
			foreach ($listenersForEvent as $listenerForEvent) {
				$listeners[] = $listenerForEvent[0]::class;
			}
		}
	}

	Assert::true(in_array(StopWorkerOnRestartSignalListener::class, $listeners, true));
});

// StopWorkerOnRestartSignalListener not registered without cache
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
		})
		->build();

	/** @var EventDispatcher $dispatcher */
	$dispatcher = $container->getService('messenger.event.dispatcher');
	$listeners = [];

	foreach ($dispatcher->getListeners() as $listenersForEvent) {
		if (is_array($listenersForEvent)) {
			foreach ($listenersForEvent as $listenerForEvent) {
				$listeners[] = $listenerForEvent[0]::class;
			}
		}
	}

	Assert::false(in_array(StopWorkerOnRestartSignalListener::class, $listeners, true));
});
