<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\MessageInterface;

#[AsMessageHandler]
final class InterfaceHandler
{

	public ?MessageInterface $message = null;

	public function __invoke(MessageInterface $message): void
	{
		$this->message = $message;
	}

}
