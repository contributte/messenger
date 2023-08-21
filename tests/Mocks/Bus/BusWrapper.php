<?php declare(strict_types = 1);

namespace Tests\Mocks\Bus;

use Symfony\Component\Messenger\MessageBusInterface;

final class BusWrapper
{

	public function __construct(
		private MessageBusInterface $bus
	)
	{
	}

}
