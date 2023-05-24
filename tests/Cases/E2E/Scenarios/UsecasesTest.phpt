<?php declare(strict_types = 1);

namespace Tests\Cases\E2E\Scenarios;

use Contributte\Messenger\Bus\BusRegistry;
use Contributte\Messenger\Logger\BufferLogger;
use LogicException;
use Nette\DI\Compiler;
use Nette\DI\Definitions\Statement;
use Nette\DI\Extensions\ExtensionsExtension;
use Nette\Neon\Neon;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Utils\Finder;
use stdClass;
use Tester\Assert;
use Tester\TestCase;
use Tests\Toolkit\Container;
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
			$this->process($usecase);
		} catch (Throwable $e) {
			throw new LogicException("File: {$file}", 0, $e);
		}
	}

	public function provideUsecases(): iterable
	{
		$finder = Finder::findFiles('*.neon')->from(__DIR__ . '/__files__');

		foreach ($finder as $file) {
			yield $file => [$file->getRealPath(), Helpers::neonFile($file->getRealPath())];
		}
	}

	/**
	 * @param mixed[] $data
	 */
	public function process(array $data): void
	{
		/** @var stdClass $usecase */
		$usecase = (new Processor())->process($this->schema(), $data);

		try {
			$container = Container::of()
				->withCompiler(static function (Compiler $compiler) use ($usecase): void {
					$compiler->addExtension('extensions', new ExtensionsExtension());

					if (isset($usecase->input->config)) {
						$compiler->addConfig($usecase->input->config);
					}

					$compiler->addConfig(Helpers::neon(<<<'NEON'
						messenger:
							logger:
								httpLogger: @logger
								consoleLogger: @logger
						services:
							logger: Contributte\Messenger\Logger\BufferLogger
					NEON
					));
				})
				->build();

			/** @var BusRegistry $busRegistry */
			$busRegistry = $container->getByType(BusRegistry::class);
			$bus = $busRegistry->get($usecase->input->dispatch->bus);

			/** @var Statement $ms */
			$ms = $usecase->input->dispatch->message;
			$message = new ($ms->entity)(...$ms->arguments);

			$bus->dispatch($message);

			/** @var BufferLogger $logger */
			$logger = $container->getByType(BufferLogger::class);
			Assert::equal($usecase->output->logger, $logger->obtain());
		} catch (Throwable $e) {
			if (isset($usecase->output->exception)) {
				Assert::exception(
					static fn () => throw $e,
					$usecase->output->exception->type,
					$usecase->output->exception->message
				);
			} else {
				throw $e;
			}
		}
	}

	private function schema(): Structure
	{
		return Expect::structure([
			'input' => Expect::structure([
				'config' => Expect::arrayOf('array')->required(),
				'dispatch' => Expect::structure([
					'bus' => Expect::string()->required(),
					'message' => Expect::type(Statement::class)->required(),
				])->required(),
			]),
			'output' => Expect::structure([
				'logger' => Expect::array(),
			])->required(),
		]);
	}

}

(new UsecasesTest())->run();
