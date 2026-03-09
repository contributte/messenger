<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\DebugCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;
use Symfony\Component\Messenger\Command\SetupTransportsCommand;
use Symfony\Component\Messenger\Command\StatsCommand;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Console commands are registered
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	Assert::count(1, $container->findByType(ConsumeMessagesCommand::class));
	Assert::count(1, $container->findByType(DebugCommand::class));
	Assert::count(1, $container->findByType(SetupTransportsCommand::class));
	Assert::count(1, $container->findByType(FailedMessagesRemoveCommand::class));
	Assert::count(1, $container->findByType(FailedMessagesRetryCommand::class));
	Assert::count(1, $container->findByType(FailedMessagesShowCommand::class));

	if (class_exists(StatsCommand::class)) {
		Assert::count(1, $container->findByType(StatsCommand::class));
	}
});

// DebugCommand receives handler mapping
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						memory:
							dsn: in-memory://

					routing:
						Tests\Mocks\Message\SimpleMessage: [memory]

				services:
					- Tests\Mocks\Handler\SimpleHandler
			NEON
			));
		})
		->build();

	/** @var DebugCommand $debugCommand */
	$debugCommand = $container->getByType(DebugCommand::class);

	$rc = new \ReflectionClass($debugCommand);
	$prop = $rc->getProperty('mapping');
	/** @var array<string, array<string, list<string>>> $mapping */
	$mapping = $prop->getValue($debugCommand);

	Assert::true(isset($mapping['messageBus']));
	Assert::true(isset($mapping['messageBus']['Tests\Mocks\Message\SimpleMessage']));
	Assert::count(1, $mapping['messageBus']['Tests\Mocks\Message\SimpleMessage']);
});

// DebugCommand with multiple buses
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					bus:
						messageBus:
						commandBus:
							allowNoHandlers: true

				services:
					- Tests\Mocks\Handler\SimpleHandler
			NEON
			));
		})
		->build();

	/** @var DebugCommand $debugCommand */
	$debugCommand = $container->getByType(DebugCommand::class);

	$rc = new \ReflectionClass($debugCommand);
	$prop = $rc->getProperty('mapping');
	/** @var array<string, array<string, list<string>>> $mapping */
	$mapping = $prop->getValue($debugCommand);

	Assert::true(isset($mapping['messageBus']));
	Assert::true(isset($mapping['commandBus']));
	// SimpleHandler has #[AsMessageHandler] without bus restriction, so it registers on all buses
	Assert::true(isset($mapping['messageBus']['Tests\Mocks\Message\SimpleMessage']));
	Assert::true(isset($mapping['commandBus']['Tests\Mocks\Message\SimpleMessage']));
});

// SetupTransportsCommand and StatsCommand receive transport names
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						async:
							dsn: in-memory://
						failed:
							dsn: in-memory://
			NEON
			));
		})
		->build();

	/** @var SetupTransportsCommand $setupCommand */
	$setupCommand = $container->getByType(SetupTransportsCommand::class);

	$rc = new \ReflectionClass($setupCommand);
	$prop = $rc->getProperty('transportNames');
	/** @var list<string> $transportNames */
	$transportNames = $prop->getValue($setupCommand);

	Assert::contains('async', $transportNames);
	Assert::contains('failed', $transportNames);

	if (class_exists(StatsCommand::class)) {
		/** @var StatsCommand $statsCommand */
		$statsCommand = $container->getByType(StatsCommand::class);

		$rc = new \ReflectionClass($statsCommand);
		$prop = $rc->getProperty('transportNames');
		/** @var list<string> $statsTransportNames */
		$statsTransportNames = $prop->getValue($statsCommand);

		Assert::contains('async', $statsTransportNames);
		Assert::contains('failed', $statsTransportNames);
	}
});
