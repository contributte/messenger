<?php declare(strict_types = 1);

namespace Tests\Mocks\Message;

final class EventMessage
{

	public string $text;

	public function __construct(string $text)
	{
		$this->text = $text;
	}

}
