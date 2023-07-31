<?php declare(strict_types = 1);

namespace Contributte\Messenger\Transport;

use Contributte\Messenger\Exception\Logical\ContainerException;
use Nette\DI\Container;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @implements ServiceProviderInterface<TransportInterface>
 */
class FailureTransportServiceProvider implements ServiceProviderInterface
{

	/** @var array<string, string> */
	private array $map;

	private Container $context;

	/**
	 * @param array<string, string> $map
	 */
	public function __construct(array $map, Container $context)
	{
		$this->map = $map;
		$this->context = $context;
	}

	public function get(string $id): object
	{
		if (!$this->has($id)) {
			throw ContainerException::serviceNotDefined($id);
		}

		if (!$this->context->hasService($this->map[$id])) {
			throw ContainerException::serviceNotFound($id);
		}

		return $this->context->getService($this->map[$id]);
	}

	public function has(string $id): bool
	{
		return isset($this->map[$id]);
	}

	/**
	 * @return array<string, string>
	 */
	public function getProvidedServices(): array
	{
		return $this->map;
	}

}
