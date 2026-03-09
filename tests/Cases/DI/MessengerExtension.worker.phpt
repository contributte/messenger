<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\EventListener\ResetMemoryUsageListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnCustomStopExceptionListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnFailureLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMemoryLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnTimeLimitListener;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Default always-on listeners are registered
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
		})
		->build();

	/** @var EventDispatcher $dispatcher */
	$dispatcher = $container->getService('messenger.event.dispatcher');
	$listeners = getListenerClasses($dispatcher);

	Assert::true(in_array(StopWorkerOnCustomStopExceptionListener::class, $listeners, true));
	Assert::true(in_array(ResetMemoryUsageListener::class, $listeners, true));
});

// Worker limit listeners not registered by default
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
		})
		->build();

	/** @var EventDispatcher $dispatcher */
	$dispatcher = $container->getService('messenger.event.dispatcher');
	$listeners = getListenerClasses($dispatcher);

	Assert::false(in_array(StopWorkerOnMemoryLimitListener::class, $listeners, true));
	Assert::false(in_array(StopWorkerOnTimeLimitListener::class, $listeners, true));
	Assert::false(in_array(StopWorkerOnMessageLimitListener::class, $listeners, true));
	Assert::false(in_array(StopWorkerOnFailureLimitListener::class, $listeners, true));
});

// Worker limit listeners registered when configured
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					worker:
						memoryLimit: 134217728
						timeLimit: 3600
						messageLimit: 1000
						failureLimit: 5
			NEON
			));
		})
		->build();

	/** @var EventDispatcher $dispatcher */
	$dispatcher = $container->getService('messenger.event.dispatcher');
	$listeners = getListenerClasses($dispatcher);

	Assert::true(in_array(StopWorkerOnMemoryLimitListener::class, $listeners, true));
	Assert::true(in_array(StopWorkerOnTimeLimitListener::class, $listeners, true));
	Assert::true(in_array(StopWorkerOnMessageLimitListener::class, $listeners, true));
	Assert::true(in_array(StopWorkerOnFailureLimitListener::class, $listeners, true));
});

// Partial worker limits
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					worker:
						memoryLimit: 134217728
			NEON
			));
		})
		->build();

	/** @var EventDispatcher $dispatcher */
	$dispatcher = $container->getService('messenger.event.dispatcher');
	$listeners = getListenerClasses($dispatcher);

	Assert::true(in_array(StopWorkerOnMemoryLimitListener::class, $listeners, true));
	Assert::false(in_array(StopWorkerOnTimeLimitListener::class, $listeners, true));
	Assert::false(in_array(StopWorkerOnMessageLimitListener::class, $listeners, true));
	Assert::false(in_array(StopWorkerOnFailureLimitListener::class, $listeners, true));
});

/**
 * @return list<string>
 */
function getListenerClasses(EventDispatcher $dispatcher): array
{
	$listeners = [];

	foreach ($dispatcher->getListeners() as $listenersForEvent) {
		if (is_array($listenersForEvent)) {
			foreach ($listenersForEvent as $listenerForEvent) {
				$listeners[] = $listenerForEvent[0]::class;
			}
		}
	}

	return $listeners;
}
