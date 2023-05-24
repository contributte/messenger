<?php declare(strict_types = 1);

namespace Tests\Cases\Container;

use Contributte\Messenger\Container\ServicesContainer;
use Contributte\Messenger\Exception\Logical\ContainerException;
use Contributte\Tester\Toolkit;
use Tester\Assert;
use Tests\Mocks\Container\FooService;

require_once __DIR__ . '/../../bootstrap.php';

// Empty
Toolkit::test(function (): void {
	$container = new ServicesContainer([]);

	Assert::false($container->has('test'));
	Assert::exception(
		fn () => $container->get('test'),
		ContainerException::class,
		"Service 'test' is not defined"
	);
});

// Service
Toolkit::test(function (): void {
	$foo = new FooService();
	$container = new ServicesContainer(['foo' => $foo]);

	Assert::true($container->has('foo'));
	Assert::same($foo, $container->get('foo'));
});
