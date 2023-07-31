<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use ReflectionProperty;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Tester\Assert;
use Tests\Mocks\RetryStrategy\CustomRetryStrategy;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Default retry strategy is used and can be disabled
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						withDefaultRetry:
							dsn: amqp://

						withoutRetry:
							dsn: amqp://
							retryStrategy: null
			NEON
			));
		})
		->build();

	Assert::type(MultiplierRetryStrategy::class, $container->getByName('messenger.transport.withDefaultRetry.retryStrategy'));
	Assert::false($container->hasService('messenger.withoutRetry.retryStrategy'));
});


// Test retry strategy options
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						withRetry:
							dsn: amqp://
							retryStrategy:
								maxRetries: 10
								delay: 10000
								multiplier: 20
								maxDelay: 2000000
			NEON
			));
		})
		->build();

	$strategy = $container->getByName('messenger.transport.withRetry.retryStrategy');

	Assert::type(MultiplierRetryStrategy::class, $strategy);

	$maxRetriesReflection = new ReflectionProperty(MultiplierRetryStrategy::class, 'maxRetries');
	Assert::same(10, $maxRetriesReflection->getValue($strategy));

	$delayReflection = new ReflectionProperty(MultiplierRetryStrategy::class, 'delayMilliseconds');
	Assert::same(10000, $delayReflection->getValue($strategy));

	$multiplierReflection = new ReflectionProperty(MultiplierRetryStrategy::class, 'multiplier');
	Assert::same(20.0, $multiplierReflection->getValue($strategy));

	$maxDelayReflection = new ReflectionProperty(MultiplierRetryStrategy::class, 'maxDelayMilliseconds');
	Assert::same(2000000, $maxDelayReflection->getValue($strategy));
});

// Test strategy with custom params
Toolkit::test(static function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				services:
					customStrategy: Tests\Mocks\RetryStrategy\CustomRetryStrategy

				messenger:
					transport:
						custom:
							dsn: amqp://
							retryStrategy:
								service: @customStrategy
			NEON
			));
		})
		->build();

	Assert::type(CustomRetryStrategy::class, $container->getByName('messenger.transport.custom.retryStrategy'));
});
