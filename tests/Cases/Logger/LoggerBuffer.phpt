<?php declare(strict_types = 1);

namespace Tests\Cases\Logger;

use Contributte\Messenger\Logger\BufferLogger;
use Contributte\Tester\Toolkit;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$logger = new BufferLogger();
	$logger->log('error', 'test');

	Assert::count(1, $logger->obtain());
	Assert::equal([['message' => 'test']], $logger->obtain());

	$logger->log('error', 'test2');

	Assert::count(2, $logger->obtain());
	Assert::equal([['message' => 'test'], ['message' => 'test2']], $logger->obtain());
});
