<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use ReflectionClass;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\DebugCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;
use Symfony\Component\Messenger\Command\SetupTransportsCommand;
use Symfony\Component\Messenger\Command\StatsCommand;
use Tester\Assert;
use Tests\Mocks\Message\SimpleMessage;
use Tests\Toolkit\Console;
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
	Assert::count(1, $container->findByType(StatsCommand::class));
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

	$rc = new ReflectionClass($debugCommand);
	$prop = $rc->getProperty('mapping');
	/** @var array<string, array<string, list<string>>> $mapping */
	$mapping = $prop->getValue($debugCommand);

	Assert::true(isset($mapping['messageBus']));
	Assert::true(isset($mapping['messageBus'][SimpleMessage::class]));
	Assert::count(1, $mapping['messageBus'][SimpleMessage::class]);
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

	$rc = new ReflectionClass($debugCommand);
	$prop = $rc->getProperty('mapping');
	/** @var array<string, array<string, list<string>>> $mapping */
	$mapping = $prop->getValue($debugCommand);

	Assert::true(isset($mapping['messageBus']));
	Assert::true(isset($mapping['commandBus']));
	// SimpleHandler has #[AsMessageHandler] without bus restriction, so it registers on all buses
	Assert::true(isset($mapping['messageBus'][SimpleMessage::class]));
	Assert::true(isset($mapping['commandBus'][SimpleMessage::class]));
});

// DebugCommand executes successfully and shows handler mapping
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

	$tester = new CommandTester($debugCommand);
	$tester->execute([]);

	$output = $tester->getDisplay(true);

	$expected = <<<'TEXT'
		Messenger
		=========

		messageBus
		----------

		 The following messages can be dispatched:

		 --------------------------------------------------
		  Tests\Mocks\Message\SimpleMessage
		      handled by Tests\Mocks\Handler\SimpleHandler

		 --------------------------------------------------
		TEXT;

	Assert::equal(
		Console::normalize($expected),
		Console::normalize($output)
	);
});

// SetupTransportsCommand receives transport names
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

	$tester = new CommandTester($setupCommand);
	$tester->execute([]);

	$output = $tester->getDisplay(true);

	Assert::match('~.*The "async" transport does not support setup.*~s', $output);
	Assert::match('~.*The "failed" transport does not support setup.*~s', $output);
	Assert::same(0, $tester->getStatusCode());
});

// StatsCommand receives transport names
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

	/** @var StatsCommand $statsCommand */
	$statsCommand = $container->getByType(StatsCommand::class);

	$tester = new CommandTester($statsCommand);
	$tester->execute([]);

	$output = $tester->getDisplay(true);

	Assert::match('~.*Unable to get message count for the following transports: "async",.*"failed".*~s', $output);
	Assert::same(0, $tester->getStatusCode());
});
