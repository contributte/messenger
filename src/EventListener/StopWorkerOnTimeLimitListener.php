<?php declare(strict_types = 1);

namespace Contributte\Messenger\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;

/**
 * Stops the worker after the configured time limit (messenger.worker.timeLimit).
 *
 * Symfony deprecated its own StopWorkerOnTimeLimitListener in 8.1 in favour of
 * the "time_limit" worker option, which can only be passed per consume command.
 * This listener keeps the global config option working on Symfony 7.4 and 8.x.
 */
class StopWorkerOnTimeLimitListener implements EventSubscriberInterface
{

	private float $endTime = 0;

	public function __construct(
		private int $timeLimitInSeconds,
		private ?LoggerInterface $logger = null,
	)
	{
		if ($timeLimitInSeconds <= 0) {
			throw new InvalidArgumentException('Time limit must be greater than zero.');
		}
	}

	/**
	 * @return array<class-string, string>
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			WorkerStartedEvent::class => 'onWorkerStarted',
			WorkerRunningEvent::class => 'onWorkerRunning',
		];
	}

	public function onWorkerStarted(): void
	{
		$this->endTime = microtime(true) + $this->timeLimitInSeconds;
	}

	public function onWorkerRunning(WorkerRunningEvent $event): void
	{
		if ($this->endTime < microtime(true)) {
			$event->getWorker()->stop();
			$this->logger?->info('Worker stopped due to time limit of {timeLimit}s exceeded', ['timeLimit' => $this->timeLimitInSeconds]);
		}
	}

}
