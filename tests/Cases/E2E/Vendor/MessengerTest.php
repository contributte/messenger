<?php declare(strict_types = 1);

namespace Tests\Cases\E2E\Vendor;

use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\Container\ServicesContainer;
use Contributte\Messenger\Logger\BufferLogger;
use Nette\DI\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\FailedMessageProcessingMiddleware;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Transport\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Worker;
use Tester\Assert;
use Tester\TestCase;
use Tests\Mocks\Vendor\DummyHandler;
use Tests\Mocks\Vendor\DummyMessage;
use Tests\Mocks\Vendor\DummyRetryFailureHandler;
use Tests\Mocks\Vendor\DummyRetryFailureMessage;

require_once __DIR__ . '/../../../bootstrap.php';

final class MessengerTest extends TestCase
{

	public function testHandlerMiddleware(): void
	{
		$handler = new DummyHandler();

		$bus = new MessageBus([
			new HandleMessageMiddleware(
				new HandlersLocator([
					DummyMessage::class => [$handler],
				])
			),
		]);

		$bus->dispatch(new DummyMessage('foobar'));

		Assert::type(DummyMessage::class, $handler->message);
		Assert::equal('foobar', $handler->message->text);
	}

	public function testEmptySenderMiddleware(): void
	{
		$handler = new DummyHandler();

		$bus = new MessageBus([
			new SendMessageMiddleware(
				new SendersLocator(
					[],
					new NetteContainer([], new Container())
				)
			),
			new HandleMessageMiddleware(
				new HandlersLocator([
					DummyMessage::class => [$handler],
				])
			),
		]);

		$bus->dispatch(new DummyMessage('foobar'));

		Assert::type(DummyMessage::class, $handler->message);
		Assert::equal('foobar', $handler->message->text);
	}

	public function testSenderMiddleware(): void
	{
		$handler = new DummyHandler();

		$inMemoryTransport1 = (new InMemoryTransportFactory())->createTransport('in-memory://', [], new PhpSerializer());
		$inMemoryTransport2 = (new InMemoryTransportFactory())->createTransport('in-memory://', [], new PhpSerializer());

		$container = new Container();
		$container->addService('inMemoryTransport1', $inMemoryTransport1);
		$container->addService('inMemoryTransport2', $inMemoryTransport2);

		$bus = new MessageBus([
			new SendMessageMiddleware(
				new SendersLocator(
					[
						DummyMessage::class => ['memory1', 'memory2'],
					],
					new NetteContainer(
						[
							'memory1' => 'inMemoryTransport1',
							'memory2' => 'inMemoryTransport2',
						],
						$container
					)
				)
			),
			new HandleMessageMiddleware(
				new HandlersLocator([
					DummyMessage::class => [$handler],
				])
			),
		]);

		$bus->dispatch(new DummyMessage('foobar'));

		Assert::null($handler->message);

		$envelops1 = $inMemoryTransport1->get();
		Assert::count(1, $envelops1);

		$envelops2 = $inMemoryTransport2->get();
		Assert::count(1, $envelops2);
	}

	public function testRetryListener(): void
	{
		$handler = DummyRetryFailureHandler::create(2);

		$inMemoryTransport1 = (new InMemoryTransportFactory())->createTransport('in-memory://', [], new PhpSerializer());

		$senderLocator = new ServicesContainer([
			'transport1' => $inMemoryTransport1,
		]);

		$retryLocator = new ServicesContainer([
			'transport1' => new MultiplierRetryStrategy(3, 1000, 1, 0),
		]);

		$retryListener = new SendFailedMessageForRetryListener(
			$senderLocator,
			$retryLocator,
		);

		$eventDispatcher = new EventDispatcher();
		$eventDispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(3));
		$eventDispatcher->addSubscriber($retryListener);

		$bus = new MessageBus([
			new SendMessageMiddleware(
				new SendersLocator(
					[
						DummyRetryFailureMessage::class => ['transport1'],
					],
					$senderLocator,
				)
			),
			new HandleMessageMiddleware(
				new HandlersLocator([
					DummyRetryFailureMessage::class => [$handler],
				])
			),
		]);

		$bus->dispatch(new DummyRetryFailureMessage('foobar'));

		Assert::null($handler->message);

		$envelops1 = $inMemoryTransport1->get();
		Assert::count(1, $envelops1);

		$worker = new Worker(['transport1' => $inMemoryTransport1], $bus, $eventDispatcher);
		$worker->run();

		Assert::notNull($handler->message);
	}

	public function testFailureListener(): void
	{
		$handler = DummyRetryFailureHandler::create(1);

		$logger = new BufferLogger();

		$inMemoryTransport1 = (new InMemoryTransportFactory())->createTransport('in-memory://', [], new PhpSerializer());
		$inMemoryTransport2 = (new InMemoryTransportFactory())->createTransport('in-memory://', [], new PhpSerializer());

		$senderLocator = new ServicesContainer([
			'transport1' => $inMemoryTransport1,
		]);

		$failedLocator = new ServicesContainer([
			'transport1' => $inMemoryTransport2,
		]);

		$failureListener = new SendFailedMessageToFailureTransportListener(
			$failedLocator,
			$logger
		);

		$eventDispatcher = new EventDispatcher();
		$eventDispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(1, $logger));
		$eventDispatcher->addSubscriber($failureListener);

		$sendMessageMiddleware = new SendMessageMiddleware(
			new SendersLocator(
				[
					DummyRetryFailureMessage::class => ['transport1'],
				],
				$senderLocator,
			)
		);
		$sendMessageMiddleware->setLogger($logger);

		$handleMessageMiddleware = new HandleMessageMiddleware(
			new HandlersLocator([
				DummyRetryFailureMessage::class => [$handler],
			])
		);
		$handleMessageMiddleware->setLogger($logger);

		$bus = new MessageBus([
			new FailedMessageProcessingMiddleware(),
			$sendMessageMiddleware,
			$handleMessageMiddleware,
		]);

		$bus->dispatch(new DummyRetryFailureMessage('foobar'));

		$envelops1 = $inMemoryTransport1->get();
		Assert::count(1, $envelops1);

		$envelops2 = $inMemoryTransport2->get();
		Assert::count(0, $envelops2);

		$worker = new Worker(['transport1' => $inMemoryTransport1], $bus, $eventDispatcher);
		$worker->run();

		$envelops1 = $inMemoryTransport1->get();
		Assert::count(0, $envelops1);

		$envelops2 = $inMemoryTransport2->get();
		Assert::count(1, $envelops2);

		Assert::equal([
			['message' => 'Sending message {class} with {alias} sender using {sender}'],
			['message' => 'Received message {class}'],
			['message' => 'Rejected message {class} will be sent to the failure transport {transport}.'],
			['message' => 'Worker stopped due to maximum count of {count} messages processed'],
		], $logger->obtain());
	}

}

(new MessengerTest())->run();
