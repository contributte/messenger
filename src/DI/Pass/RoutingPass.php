<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\BuilderMan;
use Contributte\Messenger\Exception\LogicalException;
use Nette\DI\Definitions\ServiceDefinition;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;

class RoutingPass extends AbstractPass
{

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('routing.locator'))
			->setFactory(SendersLocator::class, [[], $this->prefix('@transport.container')]);
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();
		$transports = array_values($builder->findByTag(MessengerExtension::TRANSPORT_TAG));

		// Scan message classes for #[AsMessage] attribute routing
		$attributeRouting = BuilderMan::of($this)->getAttributeRouting();

		// Merge: NEON config takes precedence over attributes
		$routing = array_merge($attributeRouting, (array) $config->routing);

		// Validate
		foreach ($routing as $routingEntity => $routingTransports) {
			if (($diff = array_diff($routingTransports, $transports)) !== []) {
				throw new LogicalException(sprintf('Invalid transport "%s" defined for "%s". Available transports "%s".', implode(',', $diff), $routingEntity, implode(',', $transports)));
			}
		}

		// Update SendersLocator with merged routing
		/** @var ServiceDefinition $locatorDef */
		$locatorDef = $builder->getDefinition($this->prefix('routing.locator'));
		$locatorDef->setArgument(0, $routing);
	}

}
