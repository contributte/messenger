<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
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
						php1: Symfony\Component\Messenger\Transport\Serialization\PhpSerializer
						php2: @serializer

				services:
					serializer: Symfony\Component\Messenger\Transport\Serialization\PhpSerializer
			NEON
			));
		})
		->build();

	Assert::count(4, $container->findByType(SerializerInterface::class));
});
