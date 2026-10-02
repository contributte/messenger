<?php declare(strict_types = 1);

namespace Tests\Cases\EventListener;

use Contributte\Messenger\EventListener\StopWorkerOnTimeLimitListener;
use Contributte\Messenger\Logger\BufferLogger;
use Contributte\Tester\Toolkit;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Worker;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

function createWorker(): Worker
{
	return new class ([], new MessageBus()) extends Worker {

		public int $stopped = 0;

		public function stop(): void
		{
			$this->stopped++;
		}

	};
}

// Subscribed events
Toolkit::test(function (): void {
	Assert::equal([
		WorkerStartedEvent::class => 'onWorkerStarted',
		WorkerRunningEvent::class => 'onWorkerRunning',
	], StopWorkerOnTimeLimitListener::getSubscribedEvents());
});

// Invalid time limit
Toolkit::test(function (): void {
	Assert::exception(
		static fn () => new StopWorkerOnTimeLimitListener(0),
		InvalidArgumentException::class,
		'Time limit must be greater than zero.'
	);

	Assert::exception(
		static fn () => new StopWorkerOnTimeLimitListener(-1),
		InvalidArgumentException::class,
		'Time limit must be greater than zero.'
	);
});

// Worker is not stopped before the time limit
Toolkit::test(function (): void {
	$worker = createWorker();
	$logger = new BufferLogger();
	$listener = new StopWorkerOnTimeLimitListener(60, $logger);

	$listener->onWorkerStarted();
	$listener->onWorkerRunning(new WorkerRunningEvent($worker, false));

	Assert::same(0, $worker->stopped);
	Assert::count(0, $logger->obtain());
});

// Worker is stopped once the time limit is exceeded
Toolkit::test(function (): void {
	$worker = createWorker();
	$logger = new BufferLogger();
	$listener = new StopWorkerOnTimeLimitListener(1, $logger);

	$listener->onWorkerStarted();
	usleep(1100000);
	$listener->onWorkerRunning(new WorkerRunningEvent($worker, true));

	Assert::same(1, $worker->stopped);
	Assert::equal([
		[
			'level' => 'info',
			'message' => 'Worker stopped due to time limit of {timeLimit}s exceeded',
			'context' => ['timeLimit' => 1],
		],
	], $logger->obtain());
});

// Works without logger
Toolkit::test(function (): void {
	$worker = createWorker();
	$listener = new StopWorkerOnTimeLimitListener(1);

	$listener->onWorkerStarted();
	usleep(1100000);
	$listener->onWorkerRunning(new WorkerRunningEvent($worker, false));

	Assert::same(1, $worker->stopped);
});
