<?php declare(strict_types = 1);

namespace Tests\Mocks\Vendor;

final class DummyHandler
{

	public ?DummyMessage $message = null;

	public function __invoke(DummyMessage $message): void
	{
		$this->message = $message;
	}

}
