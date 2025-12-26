<?php declare(strict_types = 1);

namespace Tests\Mocks\Message;

/**
 * Event message without a handler - used for testing allowNoHandlers on eventBus
 */
final class EventMessage
{

	public string $text;

	public function __construct(string $text)
	{
		$this->text = $text;
	}

}
