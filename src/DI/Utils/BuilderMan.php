<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Utils;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Pass\AbstractPass;
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

}
