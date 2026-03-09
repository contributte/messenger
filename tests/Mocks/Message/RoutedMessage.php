<?php declare(strict_types = 1);

namespace Tests\Mocks\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: 'memory')]
final class RoutedMessage
{

	public function __construct(
		public readonly string $text,
	)
	{
	}

}
