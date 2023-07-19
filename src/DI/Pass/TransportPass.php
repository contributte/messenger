<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\BuilderMan;
use Contributte\Messenger\DI\Utils\ServiceMan;
use Contributte\Messenger\Transport\FailureTransportServiceProvider;
use Nette\DI\Definitions\ServiceDefinition;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;

class TransportPass extends AbstractPass
{

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// Register transports
		foreach ($config->transport as $name => $transport) {
			$transportDef = $builder->addDefinition($this->prefix(sprintf('transport.%s', $name)));
			$transportDef->setFactory($this->prefix('@transportFactory::createTransport'), [
				$transport->dsn,
				$transport->options,
				ServiceMan::of($this)->getSerializer($transport->serializer),
			]);

			$transportDef->addTag(MessengerExtension::TRANSPORT_TAG, $name);

			$this->defineFailureForTransport($name, $transport->failureTransport);
			$this->registerRetryStrategyForTransport($name, $transport->retryStrategy);
		}

		// Register transports container
		$builder->addDefinition($this->prefix('transport.container'))
			->setFactory(NetteContainer::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('failure_transport.service_provider'))
			->setFactory(FailureTransportServiceProvider::class)
			->setAutowired(false);
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var ServiceDefinition $transportContainerDef */
		$transportContainerDef = $builder->getDefinition($this->prefix('transport.container'));
		$transportContainerDef->setArgument(0, BuilderMan::of($this)->getTransports());

		/** @var ServiceDefinition $failureTransportProviderDef */
		$failureTransportProviderDef = $builder->getDefinition($this->prefix('failure_transport.service_provider'));
		$failureTransportProviderDef->setArgument(0, BuilderMan::of($this)->getFailedTransports());
	}

	private function defineFailureForTransport(string $transportName, null|string $failureTransport): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		/** @var string|null $globalFailureTransport */
		$globalFailureTransport = $config->failureTransport;

		if ($globalFailureTransport === null && $failureTransport === null) {
			return;
		}

		$failureTransport ??= $globalFailureTransport;

		$transportDefinition = $builder->getDefinition($this->prefix(sprintf('transport.%s', $transportName)));
		$transportDefinition->addTag(MessengerExtension::FAILURE_TRANSPORT_TAG, $failureTransport);
	}

	/**
	 * @param object{service: string|null, maxRetries: int, delay: int, multiplier: int, maxDelay: int}|null $strategy
	 */
	private function registerRetryStrategyForTransport(string $transportName, null|object $strategy): void
	{
		if ($strategy === null) {
			return;
		}

		$builder = $this->getContainerBuilder();

		$strategyServiceName = $this->prefix(sprintf('retry_strategy.%s', $transportName));
		$strategyDefinition = $builder->addDefinition($strategyServiceName);
		$strategyDefinition->addTag(MessengerExtension::RETRY_STRATEGY_TAG, $transportName);

		if ($strategy->service !== null) {
			$strategyDefinition->setFactory($strategy->service);

			return;
		}

		$strategyDefinition->setFactory(MultiplierRetryStrategy::class, [
			$strategy->maxRetries,
			$strategy->delay,
			(float) $strategy->multiplier,
			$strategy->maxDelay,
		]);
	}

}
