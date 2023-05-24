<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Tests\Mocks\Message\SimpleMessage;

final class NoAttributeHandler
{

	public ?SimpleMessage $message = null;

	public function __invoke(SimpleMessage $message): void
	{
		$this->message = $message;
	}

}
