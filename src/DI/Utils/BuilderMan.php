<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Utils;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Pass\AbstractPass;
use Contributte\Messenger\Exception\LogicalException;
use Nette\DI\Definitions\Definition;
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
