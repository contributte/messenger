<?php declare(strict_types = 1);

namespace Tests\Mocks\RetryStrategy;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

final class CustomRetryStrategy implements RetryStrategyInterface
{

	public function isRetryable(Envelope $message, ?\Throwable $throwable = null): bool
	{
		return true;
	}

	public function getWaitingTime(Envelope $message, ?\Throwable $throwable = null): int
	{
		return 1;
	}

}
