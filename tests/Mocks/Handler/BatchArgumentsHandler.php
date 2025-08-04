<?php declare(strict_types = 1);

namespace Tests\Mocks\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Tests\Mocks\Message\FooMessage;

#[AsMessageHandler]
final class BatchArgumentsHandler implements BatchHandlerInterface
{

	use BatchHandlerTrait;

	/**
	 * @param list<array{0: object, 1: Acknowledger}> $jobs
	 */
	private function process(array $jobs): void
	{
		foreach ($jobs as [, $ack]) {
			$ack->ack();
		}
	}

	public function __invoke(FooMessage $message, ?Acknowledger $ack = null, string $extra = ''): mixed
	{
		return $this->handle($message, $ack);
	}

}
