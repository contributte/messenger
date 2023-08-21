<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(
	handles: '*'
)]
final class WildcardHandler
{

	public ?object $message = null;

	public function __invoke(object $message): void
	{
		$this->message = $message;
	}

}
