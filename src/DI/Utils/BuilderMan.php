<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Utils;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Pass\AbstractPass;
use Contributte\Messenger\Exception\LogicalException;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;

final class BuilderMan
{

	private AbstractPass $pass;

	private function __construct(AbstractPass $pass)
	{
		$this->pass = $pass;
	}

	public static function of(AbstractPass $pass): self
	{
		return new self($pass);
	}

	/**
	 * @return array<string, string>
	 */
	public function getBuses(): array
	{
		return $this->getServiceNamesByTag(MessengerExtension::BUS_TAG);
	}

	/**
	 * @return array<string, Definition>
	 */
	public function getTransportFactories(): array
	{
		return $this->getServiceDefinitionsByTag(MessengerExtension::TRANSPORT_FACTORY_TAG);
	}

	/**
	 * @return array<string, string>
	 */
	public function getTransports(): array
	{
		return $this->getServiceNamesByTag(MessengerExtension::TRANSPORT_TAG);
	}

	/**
	 * @return array<string, string>
	 */
	public function getRetryStrategies(): array
	{
		return $this->getServiceNamesByTag(MessengerExtension::RETRY_STRATEGY_TAG);
	}

	/**
	 * @return array<string, string>
	 */
	public function getTransportToFailureTransportsServiceMapping(): array
	{
		$builder = $this->pass->getContainerBuilder();

		$transports = $this->getTransports();
		$definitions = $builder->findByTag(MessengerExtension::FAILURE_TRANSPORT_TAG);

		$transportsMapping = [];
		foreach ($definitions as $serviceName => $failureTransport) {
			$definition = $builder->getDefinition($serviceName);
			/** @var string $transport */
			$transport = $definition->getTag(MessengerExtension::TRANSPORT_TAG);

			if (!isset($transports[$failureTransport])) {
				throw new LogicalException(sprintf('Invalid failure transport "%s" defined for "%s" transport. Available transports "%s".', $failureTransport, $transport, implode(', ', array_keys($transports))));
			}

			$transportsMapping[$transport] = $transports[$failureTransport];
		}

		return $transportsMapping;
	}

	/**
	 * @return array<string, string>
	 */
	public function getFailedTransports(): array
	{
		$builder = $this->pass->getContainerBuilder();

		$transports = $this->getTransports();
		/** @var array<string, string> $definitions */
		$definitions = $builder->findByTag(MessengerExtension::FAILURE_TRANSPORT_TAG);

		$transportsMapping = [];

		foreach ($definitions as $serviceName => $failureTransport) {
			$definition = $builder->getDefinition($serviceName);
			/** @var string $transport */
			$transport = $definition->getTag(MessengerExtension::TRANSPORT_TAG);

			if (!isset($transports[$failureTransport])) {
				throw new LogicalException(sprintf('Invalid failure transport "%s" defined for "%s" transport. Available transports "%s".', $failureTransport, $transport, implode(', ', array_keys($transports))));
			}

			$transportsMapping[$failureTransport] = $transports[$failureTransport];
		}

		return $transportsMapping;
	}

	/**
	 * @return list<string>
	 */
	public function getHandlerServiceNames(): array
	{
		$builder = $this->pass->getContainerBuilder();

		$serviceHandlers = array_keys($builder->findByTag(MessengerExtension::HANDLER_TAG));

		foreach ($builder->getDefinitions() as $definition) {
			/** @var class-string|null $class */
			$class = $definition->getType();

			if ($class === null || !class_exists($class)) {
				continue;
			}

			$name = $definition->getName();

			if ($name === null) {
				continue;
			}

			if (Reflector::getMessageHandlers($class) !== []) {
				$serviceHandlers[] = $name;
			}
		}

		return array_values(array_unique($serviceHandlers));
	}

	/**
	 * @return array<string, array<string, list<string>>>
	 */
	public function getHandlerMapping(): array
	{
		$builder = $this->pass->getContainerBuilder();
		$config = $this->pass->getConfig();
		$mapping = [];

		foreach ($config->bus as $busName => $busConfig) {
			/** @var ServiceDefinition $locator */
			$locator = $builder->getDefinition($this->pass->prefix(sprintf('bus.%s.locator', $busName)));

			/** @var array<string, list<array{service: string, method: string}>> $handlers */
			$handlers = $locator->getFactory()->arguments[0] ?? [];

			$busMapping = [];

			foreach ($handlers as $messageClass => $handlerDescriptors) {
				$descriptions = [];

				foreach ($handlerDescriptors as $descriptor) {
					$description = $descriptor['service'];

					if ($descriptor['method'] !== '__invoke') {
						$description .= '::' . $descriptor['method'];
					}

					$descriptions[] = $description;
				}

				$busMapping[$messageClass] = $descriptions;
			}

			$mapping[$busName] = $busMapping;
		}

		return $mapping;
	}

	public function getSerializer(string|Statement|null $serializer): Statement|string
	{
		if ($serializer === null) {
			return $this->pass->prefix('@serializer.default');
		}

		if (is_string($serializer) && !str_starts_with($serializer, '@') && !str_contains($serializer, '\\')) {
			return $this->pass->prefix(sprintf('@serializer.%s', $serializer));
		}

		if ($serializer instanceof Statement) {
			return $serializer;
		}

		return new Statement($serializer);
	}

	/**
	 * @return array<string, Definition>
	 */
	private function getServiceDefinitionsByTag(string $tag): array
	{
		$builder = $this->pass->getContainerBuilder();

		$definitions = [];
		foreach ($builder->findByTag($tag) as $serviceName => $tagValue) {
			$definitions[(string) $tagValue] = $builder->getDefinition($serviceName);
		}

		return $definitions;
	}

	/**
	 * @return array<string, string>
	 */
	private function getServiceNamesByTag(string $tag): array
	{
		$builder = $this->pass->getContainerBuilder();

		$definitions = [];
		foreach ($builder->findByTag($tag) as $serviceName => $tagValue) {
			$definitions[(string) $tagValue] = $serviceName;
		}

		return $definitions;
	}

}
