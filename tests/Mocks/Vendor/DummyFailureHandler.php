<?php declare(strict_types = 1);

namespace Tests\Mocks\Vendor;

use RuntimeException;

final class DummyFailureHandler
{

	public function __invoke(DummyFailureMessage $message): void
	{
		throw new RuntimeException('Foo');
	}

}
