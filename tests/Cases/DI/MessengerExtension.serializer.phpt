<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Nette\DI\ServiceCreationException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Serializers
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					serializer:
						default: Symfony\Component\Messenger\Transport\Serialization\PhpSerializer
						service1: @serializer

					transport:
						sync1:
							dsn: sync://
							# serializer: default
						sync2:
							dsn: sync://
							serializer: service1
						sync3:
							dsn: sync://
							serializer: @serializer
						sync4:
							dsn: sync://
							serializer: Symfony\Component\Messenger\Transport\Serialization\PhpSerializer()

				services:
					serializer: Symfony\Component\Messenger\Transport\Serialization\PhpSerializer
			NEON
			));
		})
		->build();

	Assert::count(3, $container->findByType(SerializerInterface::class));
});
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			Container::of()
				->withDefaults()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addConfig(Helpers::neon(<<<'NEON'
				messenger:
					transport:
						sync:
							dsn: sync://
							serializer: fake
			NEON
					));
				})
				->build();
		},
		ServiceCreationException::class,
		"Service 'messenger.transport.sync' (type of Symfony\Component\Messenger\Transport\TransportInterface): Reference to missing service 'messenger.serializer.fake'. (used in @messenger.transportFactory::createTransport())"
	);
});
