<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\Logger\BufferLogger;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Psr\Log\LoggerInterface;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Default logger
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	Assert::count(3, $container->findByType(LoggerInterface::class));
	Assert::true($container->hasService('messenger.logger.logger'));
	Assert::true($container->hasService('messenger.logger.consoleLogger'));
	Assert::true($container->hasService('messenger.logger.httpLogger'));
});

// Provided event dispatcher
Toolkit::test(static function () {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				services:
					mylogger: Contributte\Messenger\Logger\BufferLogger
				NEON
			));
		})
		->build();

	Assert::count(4, $container->findByType(LoggerInterface::class));
	Assert::true($container->hasService('messenger.logger.logger'));
	Assert::true($container->hasService('messenger.logger.consoleLogger'));
	Assert::true($container->hasService('messenger.logger.httpLogger'));

	Assert::false($container->isCreated('mylogger'));
	Assert::false($container->isCreated('messenger.logger.logger'));
	Assert::type(BufferLogger::class, $container->getByType(LoggerInterface::class));
	Assert::true($container->isCreated('mylogger'));
	Assert::false($container->isCreated('messenger.logger.logger'));
});
