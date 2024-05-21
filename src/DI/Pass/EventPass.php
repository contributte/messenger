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
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnSignalsListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnSigtermSignalListener;

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

		// Stop on signal
		if (class_exists(StopWorkerOnSignalsListener::class)) {
			$dispatcher->addSetup('addSubscriber', [
				new Statement(StopWorkerOnSignalsListener::class, [null, $this->prefix('@logger.logger')]),
			]);
		} else {
			$dispatcher->addSetup('addSubscriber', [
				new Statement(StopWorkerOnSigtermSignalListener::class, [$this->prefix('@logger.logger')]), // @phpstan-ignore-line
			]);
		}
	}

}
