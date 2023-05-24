<?php declare(strict_types = 1);

namespace Tests\Mocks\Vendor;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class DummyMiddleware implements MiddlewareInterface
{

	public function handle(Envelope $envelope, StackInterface $stack): Envelope
	{
		return $envelope;
	}

}
