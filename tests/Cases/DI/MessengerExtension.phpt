<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Tester\Assert;
use Tests\Toolkit\Container;

require_once __DIR__ . '/../../bootstrap.php';

// Default setup
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	Assert::count(5, $container->findByType(TransportFactoryInterface::class));
});
