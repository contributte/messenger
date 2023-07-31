<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\BuilderMan;
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
				BuilderMan::of($this)->getSerializer($transport->serializer),
			]);

			$transportDef->addTag(MessengerExtension::TRANSPORT_TAG, $name);

			// Failure transport
			if ($transport->failureTransport !== null || $config->failureTransport !== null) {
				$transportDefinition = $builder->getDefinition($this->prefix(sprintf('transport.%s', $name)));
				$transportDefinition->addTag(MessengerExtension::FAILURE_TRANSPORT_TAG, $transport->failureTransport ?? $config->failureTransport);
			}

			// Retry strategy
			if ($transport->retryStrategy !== null) {
				$strategyDefinition = $builder->addDefinition($this->prefix(sprintf('transport.%s.retryStrategy', $name)));
				$strategyDefinition->addTag(MessengerExtension::RETRY_STRATEGY_TAG, $name);

				if ($transport->retryStrategy->service !== null) {
					$strategyDefinition->setFactory($transport->retryStrategy->service);
				} else {
					$strategyDefinition->setFactory(MultiplierRetryStrategy::class, [
						$transport->retryStrategy->maxRetries,
						$transport->retryStrategy->delay,
						(float) $transport->retryStrategy->multiplier,
						$transport->retryStrategy->maxDelay,
					]);
				}
			}
		}

		// Register transports container
		$builder->addDefinition($this->prefix('transport.container'))
			->setFactory(NetteContainer::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('failureTransport.serviceProvider'))
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
		$failureTransportProviderDef = $builder->getDefinition($this->prefix('failureTransport.serviceProvider'));
		$failureTransportProviderDef->setArgument(0, BuilderMan::of($this)->getFailedTransports());
	}

}
