<?php declare(strict_types = 1);

namespace Contributte\Messenger\Exception\Logical;

use Contributte\Messenger\Exception\LogicalException;

final class ContainerException extends LogicalException
{

	public string $service;

	public static function serviceNotDefined(string $id): self
	{
		$exception = new self(sprintf("Service '%s' is not defined", $id));
		$exception->service = $id;

		return $exception;
	}

	public static function serviceNotFound(string $id): self
	{
		$exception = new self(sprintf("Service '%s' not found", $id));
		$exception->service = $id;

		return $exception;
	}

}
