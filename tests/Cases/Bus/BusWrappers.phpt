<?php declare(strict_types = 1);

namespace Tests\Cases\Bus;

use Contributte\Messenger\Bus\CommandBus;
use Contributte\Messenger\Bus\MessageBus as WrapperMessageBus;
use Contributte\Messenger\Bus\QueryBus;
use Contributte\Messenger\Container\ServicesContainer;
use Contributte\Tester\Toolkit;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\RoutableMessageBus;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Tester\Assert;
use Tests\Mocks\Message\FooMessage;
use Tests\Mocks\Message\SimpleMessage;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$handler = new class {

		public ?SimpleMessage $message = null;

		public function __invoke(SimpleMessage $message): void
		{
			$this->message = $message;
		}

	};

	$fallbackBus = new MessageBus([
		new HandleMessageMiddleware(new HandlersLocator([
			SimpleMessage::class => [$handler],
		])),
	]);

	$bus = new WrapperMessageBus(new RoutableMessageBus(new ServicesContainer([]), $fallbackBus));
	$envelope = $bus->dispatch(new SimpleMessage('message'));

	Assert::type(Envelope::class, $envelope);
	Assert::type(SimpleMessage::class, $handler->message);
	Assert::same('message', $handler->message->text);
});

Toolkit::test(function (): void {
	$handler = new class {

		public ?FooMessage $message = null;

		public function __invoke(FooMessage $message): void
		{
			$this->message = $message;
		}

	};

	$eventBus = new MessageBus([
		new HandleMessageMiddleware(new HandlersLocator([
			FooMessage::class => [$handler],
		])),
	]);

	$bus = new WrapperMessageBus(new RoutableMessageBus(new ServicesContainer([
		'eventBus' => $eventBus,
	])));

	$envelope = $bus->dispatch(new Envelope(new FooMessage('stamped'), [
		new BusNameStamp('eventBus'),
	]));

	Assert::type(FooMessage::class, $handler->message);
	Assert::same('stamped', $handler->message->text);
	Assert::type(BusNameStamp::class, $envelope->last(BusNameStamp::class));
	Assert::same('eventBus', $envelope->last(BusNameStamp::class)?->getBusName());
});

Toolkit::test(function (): void {
	$handler = new class {

		public ?FooMessage $message = null;

		public function __invoke(FooMessage $message): void
		{
			$this->message = $message;
		}

	};

	$fallbackBus = new MessageBus([
		new HandleMessageMiddleware(new HandlersLocator([
			FooMessage::class => [$handler],
		])),
	]);

	$bus = new CommandBus(new RoutableMessageBus(new ServicesContainer([]), $fallbackBus));
	$bus->handle(new FooMessage('command'));

	Assert::type(FooMessage::class, $handler->message);
	Assert::same('command', $handler->message->text);
});

Toolkit::test(function (): void {
	$fallbackBus = new MessageBus([
		new HandleMessageMiddleware(new HandlersLocator([
			FooMessage::class => [
				static fn (FooMessage $message): string => 'handled:' . $message->text,
			],
		])),
	]);

	$bus = new QueryBus(new RoutableMessageBus(new ServicesContainer([]), $fallbackBus));

	Assert::same('handled:query', $bus->query(new FooMessage('query')));
});

Toolkit::test(function (): void {
	$bus = new RoutableMessageBus(new ServicesContainer([]), new MessageBus());

	Assert::exception(
		static fn (): Envelope => $bus->dispatch(new FooMessage('plain-message')),
		InvalidArgumentException::class,
		'Messages passed to RoutableMessageBus::dispatch() must be inside an Envelope.',
	);
});
