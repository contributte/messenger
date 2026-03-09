<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\MultiRoutedMessage;

#[AsMessageHandler]
final class MultiRoutedMessageHandler
{

	public ?MultiRoutedMessage $message = null;

	public function __invoke(MultiRoutedMessage $message): void
	{
		$this->message = $message;
	}

}
