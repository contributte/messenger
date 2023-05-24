<?php declare(strict_types = 1);

namespace Contributte\Messenger\Bus;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageBus
{

	private MessageBusInterface $bus;

	public function __construct(MessageBusInterface $bus)
	{
		$this->bus = $bus;
	}

	public function dispatch(object $command): Envelope
	{
		return $this->bus->dispatch($command);
	}

}
