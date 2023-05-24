<?php declare(strict_types = 1);

namespace Contributte\Messenger\Bus;

use Contributte\Messenger\Exception\Logical\BusException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class BusRegistry
{

	private ContainerInterface $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function get(string $name): MessageBusInterface
	{
		if (!$this->container->has($name)) {
			throw BusException::busNotFound($name);
		}

		$bus = $this->container->get($name);
		assert($bus instanceof MessageBusInterface);

		return $bus;
	}

}
