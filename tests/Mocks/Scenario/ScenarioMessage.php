<?php declare(strict_types = 1);

namespace Tests\Mocks\Scenario;

final class ScenarioMessage
{

	public string $text;

	public function __construct(string $text)
	{
		$this->text = $text;
	}

}
