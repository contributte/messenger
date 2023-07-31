<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class MultipleParametersHandler
{

	public function __invoke(string $foo, string $bar): void
	{
		// For tests
	}

}
