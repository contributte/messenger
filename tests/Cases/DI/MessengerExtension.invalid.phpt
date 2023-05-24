<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Invalid config
Toolkit::test(function (): void {
	Assert::exception(static function (): void {
		Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					foo: bar
				NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, "Unexpected item 'messenger › foo'.");
});

