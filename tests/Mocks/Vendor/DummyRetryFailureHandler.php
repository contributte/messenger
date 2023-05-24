<?php declare(strict_types = 1);

namespace Tests\Mocks\Vendor;

use RuntimeException;

final class DummyRetryFailureHandler
{

	public ?DummyRetryFailureMessage $message = null;

	public int $maxTries = 1;

	public int $tries = 0;

	public static function create(int $maxTries = 1): self
	{
		$self = new self();
		$self->maxTries = $maxTries;

		return $self;
	}

	public function __invoke(DummyRetryFailureMessage $message): void
	{
		$this->tries++;

		if ($this->maxTries > 0) {
			$this->maxTries--;

			throw new RuntimeException();
		}

		$this->message = $message;
	}

}
