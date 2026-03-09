<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\DI\Utils\BuilderMan;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\EventListener\AddErrorDetailsStampListener;
use Symfony\Component\Messenger\EventListener\DispatchPcntlSignalListener;
use Symfony\Component\Messenger\EventListener\ResetMemoryUsageListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnCustomStopExceptionListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnFailureLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMemoryLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnTimeLimitListener;

class EventPass extends AbstractPass
{

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// Register container for failure transports
		$builder->addDefinition($this->prefix('failureTransport.container'))
			->setFactory(NetteContainer::class)
			->setAutowired(false);

		// Register container for retry strategies
		$builder->addDefinition($this->prefix('retryStrategy.container'))
			->setFactory(NetteContainer::class)
			->setAutowired(false);
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var ServiceDefinition $failureTransportContainerDef */
		$failureTransportContainerDef = $builder->getDefinition($this->prefix('failureTransport.container'));
		$failureTransportContainerDef->setArgument(0, BuilderMan::of($this)->getTransportToFailureTransportsServiceMapping());

		/** @var ServiceDefinition $retryStrategyContainerDef */
		$retryStrategyContainerDef = $builder->getDefinition($this->prefix('retryStrategy.container'));
		$retryStrategyContainerDef->setArgument(0, BuilderMan::of($this)->getRetryStrategies());

		$dispatcherServiceName = $builder->getByType(EventDispatcherInterface::class);

		// Register event dispatcher
		if ($dispatcherServiceName !== null) {
			// Reuse existing dispatcher
			$builder->addDefinition($this->prefix('event.dispatcher'))
				->setFactory('@' . $dispatcherServiceName)
				->setAutowired(false);
		} else {
			// Register default fallback dispatcher
			$builder->addDefinition($this->prefix('event.dispatcher'))
				->setFactory(EventDispatcher::class)
				->setAutowired(false);
		}

		$dispatcher = $builder->getDefinition($this->prefix('event.dispatcher'));
		assert($dispatcher instanceof ServiceDefinition);

		// PCNTL
		$dispatcher->addSetup('addSubscriber', [
			new Statement(DispatchPcntlSignalListener::class),
		]);

		// Error details
		$dispatcher->addSetup('addSubscriber', [
			new Statement(AddErrorDetailsStampListener::class),
		]);

		// Retry
		$dispatcher->addSetup('addSubscriber', [
			new Statement(SendFailedMessageForRetryListener::class, [
				$this->prefix('@transport.container'),
				$this->prefix('@retryStrategy.container'),
				$this->prefix('@logger.logger'),
			]),
		]);

		// Failure
		$dispatcher->addSetup('addSubscriber', [
			new Statement(SendFailedMessageToFailureTransportListener::class, [
				$this->prefix('@failureTransport.container'),
				$this->prefix('@logger.logger'),
			]),
		]);

		// Custom stop exception
		$dispatcher->addSetup('addSubscriber', [
			new Statement(StopWorkerOnCustomStopExceptionListener::class),
		]);

		// Reset memory usage
		$dispatcher->addSetup('addSubscriber', [
			new Statement(ResetMemoryUsageListener::class),
		]);

		// Worker limits
		$config = $this->getConfig();

		if ($config->worker->memoryLimit !== null) {
			$dispatcher->addSetup('addSubscriber', [
				new Statement(StopWorkerOnMemoryLimitListener::class, [
					$config->worker->memoryLimit,
					$this->prefix('@logger.logger'),
				]),
			]);
		}

		if ($config->worker->timeLimit !== null) {
			$dispatcher->addSetup('addSubscriber', [
				new Statement(StopWorkerOnTimeLimitListener::class, [
					$config->worker->timeLimit,
					$this->prefix('@logger.logger'),
				]),
			]);
		}

		if ($config->worker->messageLimit !== null) {
			$dispatcher->addSetup('addSubscriber', [
				new Statement(StopWorkerOnMessageLimitListener::class, [
					$config->worker->messageLimit,
					$this->prefix('@logger.logger'),
				]),
			]);
		}

		if ($config->worker->failureLimit !== null) {
			$dispatcher->addSetup('addSubscriber', [
				new Statement(StopWorkerOnFailureLimitListener::class, [
					$config->worker->failureLimit,
					$this->prefix('@logger.logger'),
				]),
			]);
		}

		// Restart signal (requires cache)
		if ($config->cache !== null) {
			$dispatcher->addSetup('addSubscriber', [
				new Statement(StopWorkerOnRestartSignalListener::class, [
					$config->cache,
					$this->prefix('@logger.logger'),
				]),
			]);
		}
	}

}
