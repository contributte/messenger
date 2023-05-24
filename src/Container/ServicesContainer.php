<?php declare(strict_types = 1);

namespace Contributte\Messenger\Container;

use Contributte\Messenger\Exception\Logical\ContainerException;
use Psr\Container\ContainerInterface;

class ServicesContainer implements ContainerInterface
{

	/** @var array<string, object> */
	private array $services;

	/**
	 * @param array<string, object> $services
	 */
	public function __construct(array $services)
	{
		$this->services = $services;
	}

	public function get(string $id): object
	{
		if (!$this->has($id)) {
			throw ContainerException::serviceNotDefined($id);
		}

		return $this->services[$id];
	}

	public function has(string $id): bool
	{
		return isset($this->services[$id]);
	}

}
