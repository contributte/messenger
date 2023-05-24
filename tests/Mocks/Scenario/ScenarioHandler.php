<?php declare(strict_types = 1);

namespace Tests\Mocks\Scenario;

final class ScenarioHandler
{

	public ?ScenarioMessage $message = null;

	public function __invoke(ScenarioMessage $message): void
	{
		$this->message = $message;
	}

}
