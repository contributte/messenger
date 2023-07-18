<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\Container\NetteContainer;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\EventListener\DispatchPcntlSignalListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnSignalsListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnSigtermSignalListener;

class EventPass extends AbstractPass
{
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// Register container for failure transports
		$builder->addDefinition($this->prefix('failure_transport.container'))
			->setFactory(NetteContainer::class)
			->setAutowired(false);

		// Register container for retry strategies
		$builder->addDefinition($this->prefix('retry_strategy.container'))
			->setFactory(NetteContainer::class)
			->setAutowired(false);

		// Register container for retry strategies
		$builder->addDefinition($this->prefix('sender.container'))
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
		$failureTransportContainerDef = $builder->getDefinition($this->prefix('failure_transport.container'));
		$failureTransportContainerDef->setArgument(0, []); // TODO

		/** @var ServiceDefinition $retryStrategyContainerDef */
		$retryStrategyContainerDef = $builder->getDefinition($this->prefix('retry_strategy.container'));
		$retryStrategyContainerDef->setArgument(0, []); // TODO

		/** @var ServiceDefinition $sendersContainerDef */
		$sendersContainerDef = $builder->getDefinition($this->prefix('sender.container'));
		$sendersContainerDef->setArgument(0, []); // TODO

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

		foreach ($this->getSubscribers() as $subscriber) {
			$dispatcher->addSetup('addSubscriber', [$subscriber]);
		}
	}

	/**
	 * @return array<Statement>
	 */
	private function getSubscribers(): array
	{
		$subscribers = [
			new Statement(DispatchPcntlSignalListener::class),
			new Statement(
				SendFailedMessageForRetryListener::class,
				[
					$this->prefix('@sender.container'),
					$this->prefix('@failure_transport.container'),
					$this->prefix('@logger.logger'),
				]
			),
			new Statement(
				SendFailedMessageToFailureTransportListener::class,
				[
					$this->prefix('@failure_transport.container'),
					$this->prefix('@logger.logger'),
				]
			),
		];

		// For symfony/messenger >= 6.3
		if (class_exists(StopWorkerOnSignalsListener::class)) {
			$subscribers[] = new Statement(StopWorkerOnSignalsListener::class);
		} else {
			$subscribers[] = new Statement(StopWorkerOnSigtermSignalListener::class);
		}

		return $subscribers;
	}
}
