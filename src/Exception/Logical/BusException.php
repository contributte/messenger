<?php declare(strict_types = 1);

namespace Contributte\Messenger\Exception\Logical;

use Contributte\Messenger\Exception\LogicalException;

final class BusException extends LogicalException
{

	public static function busNotFound(string $name): self
	{
		return new self(sprintf("Bus '%s' not found", $name));
	}

}
