<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Tests\Mocks\Message\SimpleMessage;

final class NonDefaultMethodWithoutAttributeHandler
{

	public ?SimpleMessage $message = null;

	public function nonDefaultMethod(SimpleMessage $message): void
	{
		$this->message = $message;
	}

}
