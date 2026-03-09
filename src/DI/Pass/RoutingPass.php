<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\BuilderMan;
use Contributte\Messenger\DI\Utils\Reflector;
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
		$attributeRouting = $this->discoverAttributeRouting();

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

	/**
	 * Discover routing from #[AsMessage] attributes on message classes
	 * by scanning handler method parameters.
	 *
	 * @return array<class-string, array<int, string>>
	 */
	private function discoverAttributeRouting(): array
	{
		$builder = $this->getContainerBuilder();
		$routing = [];

		foreach (BuilderMan::of($this)->getHandlerServiceNames() as $serviceName) {
			$definition = $builder->getDefinition($serviceName);
			/** @var class-string|null $handlerClass */
			$handlerClass = $definition->getType();

			if ($handlerClass === null || !class_exists($handlerClass)) {
				continue;
			}

			foreach (Reflector::getHandlerMessageClasses($handlerClass) as $messageClass) {
				if (isset($routing[$messageClass]) || !class_exists($messageClass)) {
					continue;
				}

				foreach (Reflector::getMessageRouting($messageClass) as $attribute) {
					if ($attribute->transport === null) {
						continue;
					}

					$transports = is_array($attribute->transport) ? $attribute->transport : [$attribute->transport];
					$routing[$messageClass] = array_merge($routing[$messageClass] ?? [], $transports);
				}
			}
		}

		return $routing;
	}

}
