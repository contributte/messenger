<?php declare(strict_types = 1);

namespace Tests\Cases\Container;

use Contributte\Messenger\Container\ServiceProviderContainer;
use Contributte\Messenger\Exception\Logical\ContainerException;
use Contributte\Tester\Toolkit;
use Nette\DI\Container;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Service container
Toolkit::test(function (): void {
	$map = ['test' => 'foo'];
	$context = new Container();
	$container = new ServiceProviderContainer($map, $context);

	Assert::true($container->has('test'));
	Assert::exception(
		fn () => $container->get('test'),
		ContainerException::class,
		"Service 'test' not found"
	);
	Assert::equal($map, $container->getProvidedServices());
});
