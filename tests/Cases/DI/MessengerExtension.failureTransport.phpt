<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\Exception\LogicalException;
use Contributte\Messenger\Transport\FailureTransportServiceProvider;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Test no global failure transport
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						transport1:
							dsn: in-memory://

						transport2:
							dsn: in-memory://
							failureTransport: transport1
			NEON
			));
		})
		->build();

	$services = $container->findByTag(MessengerExtension::FAILURE_TRANSPORT_TAG);

	Assert::same([
		'messenger.transport.transport2' => 'transport1',
	], $services);

	/** @var FailureTransportServiceProvider $serviceProvider */
	$serviceProvider = $container->getService('messenger.failureTransport.serviceProvider');
	Assert::type(FailureTransportServiceProvider::class, $serviceProvider);
	Assert::same([
		'transport1' => 'messenger.transport.transport1',
	], $serviceProvider->getProvidedServices());
});


// Test invalid global failed transport name
Toolkit::test(function (): void {
	Assert::exception(
		fn () => Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addConfig(Helpers::neon(<<<'NEON'
					messenger:
						failureTransport: invalid

						transport:
							transport1:
								dsn: in-memory://
				NEON
				));
			})
			->build(),
		LogicalException::class,
		'Invalid failure transport "invalid" defined for "transport1" transport. Available transports "transport1".',
	);
});

// Test invalid failed transport name
Toolkit::test(function (): void {
	Assert::exception(
		fn () => Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
					messenger:
						transport:
							transport1:
								dsn: in-memory://

							transport2:
								dsn: in-memory://
								failureTransport: transport3
				NEON
			));
			})
		->build(),
		LogicalException::class,
		'Invalid failure transport "transport3" defined for "transport2" transport. Available transports "transport1, transport2".',
	);
});

// Test with global failed transport defined
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					failureTransport: transport1

					transport:
						transport1:
							dsn: in-memory://

						transport2:
							dsn: in-memory://
							failureTransport: transport3

						transport3:
							dsn: in-memory://
							failureTransport: transport1
			NEON
			));
		})
		->build();

	$services = $container->findByTag(MessengerExtension::FAILURE_TRANSPORT_TAG);

	Assert::same([
		'messenger.transport.transport1' => 'transport1',
		'messenger.transport.transport2' => 'transport3',
		'messenger.transport.transport3' => 'transport1',
	], $services);

	/** @var FailureTransportServiceProvider $serviceProvider */
	$serviceProvider = $container->getService('messenger.failureTransport.serviceProvider');
	Assert::type(FailureTransportServiceProvider::class, $serviceProvider);
	Assert::same([
		'transport1' => 'messenger.transport.transport1',
		'transport3' => 'messenger.transport.transport3',
	], $serviceProvider->getProvidedServices());
});
