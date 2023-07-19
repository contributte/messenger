<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Utils;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Pass\AbstractPass;
use Contributte\Messenger\Exception\LogicalException;
use Nette\DI\Definitions\Definition;

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
		return $this->getServiceNames(MessengerExtension::BUS_TAG);
	}

	/**
	 * @return array<string, Definition>
	 */
	public function getTransportFactories(): array
	{
		return $this->getServiceDefinitions(MessengerExtension::TRANSPORT_FACTORY_TAG);
	}

	/**
	 * @return array<string, string>
	 */
	public function getTransports(): array
	{
		return $this->getServiceNames(MessengerExtension::TRANSPORT_TAG);
	}

	/**
	 * @return array<string, string>
	 */
	public function getFailureTransports(): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = $builder->findByTag(MessengerExtension::FAILURE_TRANSPORT_TAG);
		$transports = $this->getTransports();

		$transportsMapping = [];
		foreach ($definitions as $serviceName => $tagValue) {
			$definition = $builder->getDefinition($serviceName);
			$transport = $definition->getTag(MessengerExtension::TRANSPORT_TAG);
			$failureTransport = $definition->getTag(MessengerExtension::FAILURE_TRANSPORT_TAG);

			if (!is_string($transport) || !is_string($failureTransport)) {
				continue;
			}

			if (!isset($transports[$failureTransport])) {
				throw new LogicalException(sprintf('Invalid failure transport "%s" defined for "%s" transport. Available transports "%s".', $failureTransport, $transport, implode(', ', array_keys($transports))));
			}

			$transportsMapping[$transport] = $transports[$failureTransport];
		}

		return $transportsMapping;
	}

	/**
	 * @return array<string, Definition>
	 */
	public function getServiceDefinitions(string $tag): array
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
	public function getServiceNames(string $tag): array
	{
		$builder = $this->pass->getContainerBuilder();

		$definitions = [];
		foreach ($builder->findByTag($tag) as $serviceName => $tagValue) {
			$definitions[(string) $tagValue] = $serviceName;
		}

		return $definitions;
	}

	/**
	 * @return array<string, string>
	 */
	public function getRetryStrategies(): array
	{
		$definitions = $this->getServiceDefinitions(MessengerExtension::TRANSPORT_TAG);

		$strategies = [];
		foreach ($definitions as $transport) {
			$transportName = $transport->getTag(MessengerExtension::TRANSPORT_TAG);
			$retryService = $transport->getTag(MessengerExtension::RETRY_STRATEGY_TAG);

			if (is_string($transportName) && is_string($retryService)) {
				$strategies[$transportName] = $retryService;
			}
		}

		return $strategies;
	}

}
