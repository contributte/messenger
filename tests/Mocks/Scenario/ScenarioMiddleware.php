<?php declare(strict_types = 1);

namespace Tests\Mocks\Scenario;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class ScenarioMiddleware implements MiddlewareInterface
{

	public function handle(Envelope $envelope, StackInterface $stack): Envelope
	{
		return $envelope;
	}

}
