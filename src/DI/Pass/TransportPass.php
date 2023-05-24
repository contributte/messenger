<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\BuilderMan;
use Contributte\Messenger\DI\Utils\ServiceMan;
use Nette\DI\Definitions\ServiceDefinition;

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

			if ($transport->failureTransport) {
				$transportDef->addTag(MessengerExtension::FAILURE_TRANSPORT_TAG, $transport->failureTransport);
			}
		}

		// Register transports container
		$builder->addDefinition($this->prefix('transport.container'))
			->setFactory(NetteContainer::class)
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
	}

}
