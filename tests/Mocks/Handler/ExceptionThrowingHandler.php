<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\SimpleMessage;

#[AsMessageHandler]
final class ExceptionThrowingHandler
{

	public function __invoke(SimpleMessage $message): void
	{
		throw new Exception('foo');
	}

}
