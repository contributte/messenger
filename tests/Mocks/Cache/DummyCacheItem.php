<?php declare(strict_types = 1);

namespace Tests\Mocks\Cache;

use Psr\Cache\CacheItemInterface;

class DummyCacheItem implements CacheItemInterface
{

	public function __construct(
		private string $key,
	)
	{
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function get(): mixed
	{
		return null;
	}

	public function isHit(): bool
	{
		return false;
	}

	public function set(mixed $value): static
	{
		return $this;
	}

	public function expiresAt(?\DateTimeInterface $expiration): static
	{
		return $this;
	}

	public function expiresAfter(\DateInterval|int|null $time): static
	{
		return $this;
	}

}
