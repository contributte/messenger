<?php declare(strict_types = 1);

namespace Tests\Cases\Bus;

use Contributte\Messenger\Bus\BusRegistry;
use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\Exception\Logical\BusException;
use Contributte\Tester\Toolkit;
use Nette\DI\Container;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			$registry = new BusRegistry(new NetteContainer([], new Container()));
			$registry->get('fake');
		},
		BusException::class,
		"Bus 'fake' not found"
	);
});
