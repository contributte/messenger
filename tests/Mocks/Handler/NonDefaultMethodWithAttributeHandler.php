<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\SimpleMessage;

#[AsMessageHandler(
	bus: 'command',
	fromTransport: 'sync',
	handles: SimpleMessage::class,
	method: 'nonDefaultMethod',
	priority: 10,
)]
final class NonDefaultMethodWithAttributeHandler
{

	public ?SimpleMessage $message = null;

	public function nonDefaultMethod(SimpleMessage $message): void
	{
		$this->message = $message;
	}

}
