<?php declare(strict_types = 1);

namespace Contributte\Messenger\Bus;

use Contributte\Messenger\Exception\LogicalException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class QueryBus
{

	private MessageBusInterface $bus;

	public function __construct(MessageBusInterface $bus)
	{
		$this->bus = $bus;
	}

	public function query(object $query): mixed
	{
		$stamp = $this->bus->dispatch($query)->last(HandledStamp::class);

		if ($stamp === null) {
			throw new LogicalException('Missing handled stamp');
		}

		return $stamp->getResult();
	}

}
