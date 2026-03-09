<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\RoutedMessage;

#[AsMessageHandler]
final class RoutedMessageHandler
{

	public ?RoutedMessage $message = null;

	public function __invoke(RoutedMessage $message): void
	{
		$this->message = $message;
	}

}
