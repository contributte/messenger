<?php declare(strict_types = 1);

namespace Tests\Cases\E2E\Scenarios;

use LogicException;
use Nette\Utils\Finder;
use Tester\TestCase;
use Tests\Toolkit\ConfigGuardian;
use Tests\Toolkit\Helpers;
use Throwable;

require_once __DIR__ . '/../../../bootstrap.php';

final class UsecasesTest extends TestCase
{

	/**
	 * @dataProvider provideUsecases
	 */
	public function testUsecase(string $file, array $usecase): void
	{
		try {
			ConfigGuardian::create()->guard($usecase);
		} catch (Throwable $e) {
			throw new LogicException("File: {$file}", 0, $e);
		}
	}

	public function provideUsecases(): iterable
	{
		$finder = Finder::findFiles('config-bus3.neon')->from(__DIR__ . '/__files__');

		foreach ($finder as $file) {
			yield $file => [$file->getRealPath(), Helpers::neonFile($file->getRealPath())];
		}
	}

}

(new UsecasesTest())->run();
