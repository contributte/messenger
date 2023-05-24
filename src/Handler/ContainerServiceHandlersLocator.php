<?php declare(strict_types = 1);

namespace Contributte\Messenger\Handler;

use Nette\DI\Container;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class ContainerServiceHandlersLocator implements HandlersLocatorInterface
{

	/** @var array<class-string,array<int, array{service: string, method: string}>> */
	private array $map;

	private Container $context;

	/**
	 * @param array<class-string,array<int, array{service: string, method: string}>> $map
	 */
	public function __construct(array $map, Container $context)
	{
		$this->map = $map;
		$this->context = $context;
	}

	/**
	 * @return array<int, HandlerDescriptor>
	 */
	public function getHandlers(Envelope $envelope): iterable
	{
		$handlers = [];

		$class = get_class($envelope->getMessage());

		foreach ($this->map[$class] ?? [] as $mapConfig) {
			$service = $this->context->getService($mapConfig['service']);

			// Handler has Handler::__invoke method
			$handler = [$service, $mapConfig['method']];
			assert(is_callable($handler));

			$descriptor = new HandlerDescriptor($handler, $mapConfig);

			// Check if from_transport is OK
			if (!$this->shouldHandle($envelope, $descriptor)) {
				continue;
			}

			// Create handler descriptor
			$handlers[] = $descriptor;
		}

		return $handlers;
	}

	protected function shouldHandle(Envelope $envelope, HandlerDescriptor $handlerDescriptor): bool
	{
		$received = $envelope->last(ReceivedStamp::class);

		if (!$received instanceof ReceivedStamp) {
			return true;
		}

		$expectedTransport = $handlerDescriptor->getOption('from_transport');

		if ($expectedTransport === null) {
			return true;
		}

		return $received->getTransportName() === $expectedTransport;
	}

}
