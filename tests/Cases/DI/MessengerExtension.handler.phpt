<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Tester\Assert;
use Tests\Mocks\Handler\SimpleHandler;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Handler
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
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

	Assert::count(1, $container->findByType(TransportInterface::class));
	Assert::count(1, $container->findByType(SimpleHandler::class));
});
