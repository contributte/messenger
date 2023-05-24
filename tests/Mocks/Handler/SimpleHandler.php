<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\SimpleMessage;

#[AsMessageHandler]
final class SimpleHandler
{

	public ?SimpleMessage $message = null;

	public function __invoke(SimpleMessage $message): void
	{
		$this->message = $message;
	}

}
