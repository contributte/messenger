<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\FooMessage;

#[AsMessageHandler]
final class ArgumentsHandler
{

	public function __invoke(FooMessage $message, string $extra): void
	{
		// For tests
	}

}
