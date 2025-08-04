<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\BarMessage;
use Tests\Mocks\Message\FooMessage;

#[AsMessageHandler]
final class UnionHandler
{

	public function __invoke(FooMessage|BarMessage $message): void
	{
		// For tests
	}

}
