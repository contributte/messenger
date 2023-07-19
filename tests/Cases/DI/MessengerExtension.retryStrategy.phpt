<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use ReflectionProperty;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
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
						with_default_retry:
							dsn: amqp://

						without_retry:
							dsn: amqp://
							retryStrategy: null
			NEON
			));
		})
		->build();

	Assert::type(MultiplierRetryStrategy::class, $container->getByName('messenger.retry_strategy.with_default_retry'));
	Assert::false($container->hasService('messenger.retry_strategy.without_retry'));
});


// Test retry strategy options
Toolkit::test(static function () {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						with_retry:
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

	$strategy = $container->getByName('messenger.retry_strategy.with_retry');

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
Toolkit::test(static function () {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				services:
					customStrategy: Tests\Mocks\RetryStrategy\CustomRetryStrategy

				messenger:
					transport:
						custom_strategy:
							dsn: amqp://
							retryStrategy:
								service: @customStrategy
			NEON
			));
		})
		->build();

	Assert::type(CustomRetryStrategy::class, $container->getByName('messenger.retry_strategy.custom_strategy'));
});
