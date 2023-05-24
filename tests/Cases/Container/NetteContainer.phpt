<?php declare(strict_types = 1);

namespace Tests\Cases\Container;

use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\Exception\Logical\ContainerException;
use Contributte\Tester\Toolkit;
use Nette\DI\Container;
use Tester\Assert;
use Tests\Mocks\Container\FooService;

require_once __DIR__ . '/../../bootstrap.php';

// Empty
Toolkit::test(function (): void {
	$context = new Container();
	$container = new NetteContainer([], $context);

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

	$context = new Container();
	$context->addService('foo', $foo);

	$container = new NetteContainer(['test' => 'foo'], $context);

	Assert::true($container->has('test'));
	Assert::same($foo, $container->get('test'));
});

// Service not found
Toolkit::test(function (): void {
	$context = new Container();
	$container = new NetteContainer(['test' => 'foo'], $context);

	Assert::true($container->has('test'));
	Assert::exception(
		fn () => $container->get('test'),
		ContainerException::class,
		"Service 'test' not found"
	);
});
