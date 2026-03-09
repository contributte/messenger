<?php declare(strict_types = 1);

namespace Tests\Mocks\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: ['memory1', 'memory2'])]
final class MultiRoutedMessage
{

	public function __construct(
		public readonly string $text,
	)
	{
	}

}
