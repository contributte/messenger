<?php declare(strict_types = 1);

namespace Tests\Mocks\Doctrine;

use Doctrine\Persistence\ConnectionRegistry;

final class DummyConnectionRegistry implements ConnectionRegistry
{

	public function getDefaultConnectionName(): string
	{
		return 'default';
	}

	public function getConnection(string|null $name = null): object
	{
		return new \stdClass();
	}

	/**
	 * @return array<string, object>
	 */
	public function getConnections(): array
	{
		return [];
	}

	/**
	 * @return array<string, string>
	 */
	public function getConnectionNames(): array
	{
		return [];
	}

}
