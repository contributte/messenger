<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\SameMessage;

#[AsMessageHandler]
final class SameHandler1
{

	public ?SameMessage $message = null;

	public function __invoke(SameMessage $message): void
	{
		$this->message = $message;
	}

}
