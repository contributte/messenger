<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\Exception\LogicalException;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;

class RoutingPass extends AbstractPass
{

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('routing.locator'))
			->setFactory(SendersLocator::class, [$config->routing, $this->prefix('@transport.container')]);
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$config = $this->getConfig();
		$transports = array_values($this->getContainerBuilder()->findByTag(MessengerExtension::TRANSPORT_TAG));

		foreach ($config->routing as $routingEntity => $routingTransports) {
			if (($diff = array_diff($routingTransports, $transports)) !== []) {
				throw new LogicalException(sprintf('Invalid transport "%s" defined for "%s". Available transports "%s".', implode(',', $diff), $routingEntity, implode(',', $transports)));
			}
		}
	}

}
