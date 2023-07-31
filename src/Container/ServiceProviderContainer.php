<?php declare(strict_types = 1);

namespace Contributte\Messenger\Container;

use Nette\DI\Container;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @implements ServiceProviderInterface<object>
 */
class ServiceProviderContainer implements ServiceProviderInterface
{

	/** @var array<string, string> */
	private array $map;

	private NetteContainer $container;

	/**
	 * @param array<string, string> $map
	 */
	public function __construct(array $map, Container $context)
	{
		$this->map = $map;
		$this->container = new NetteContainer($map, $context);
	}

	public function get(string $id): object
	{
		return $this->container->get($id);
	}

	public function has(string $id): bool
	{
		return $this->container->has($id);
	}

	/**
	 * @return array<string, string>
	 */
	public function getProvidedServices(): array
	{
		return $this->map;
	}

}
