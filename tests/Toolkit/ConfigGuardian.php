<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use Nette\DI\Compiler;
use Nette\DI\Definitions\Statement;
use Nette\DI\Extensions\ExtensionsExtension;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use stdClass;
use Tester\Assert;
use Throwable;

final class ConfigGuardian
{

	public static function create(): self
	{
		return new self();
	}

	/**
	 * @param mixed[] $data
	 */
	public function guard(array $data): void
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
				})
				->build();

			// Existence
			foreach ($usecase->output->services->existence ?? [] as $serviceBlock) {
				$class = key($serviceBlock);
				$serviceBlock = current($serviceBlock);

				if ($serviceBlock->exists) {
					Assert::notNull($container->getByType($class));
				} else {
					Assert::notNull($container->getByType($class));
				}
			}

			// Counting
			foreach ($usecase->output->services->counting ?? [] as $serviceCounting) {
				$class = key($serviceCounting);
				$serviceCounting = current($serviceCounting);

				$items = $container->findByType($class);
				Assert::count($serviceCounting->count, $items, sprintf('Class guarding "%s"', $class));
			}

			// Tags
			foreach ($usecase->output->services->tags ?? [] as $serviceTags) {
				$tag = key($serviceTags);
				$serviceTags = current($serviceTags);

				$items = $container->findByTag($tag);
				Assert::count($serviceTags->count, $items);
			}
		} catch (Throwable $e) {
			if ($usecase->output->exception) {
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

	protected function schema(): Structure
	{
		return Expect::structure([
			'version' => Expect::anyOf(1),
			'input' => Expect::structure([
				'extensions' => Expect::arrayOf(implode('|', ['string', Statement::class])),
				'config' => Expect::arrayOf('array'),
			]),
			'output' => Expect::structure([
				'exception' => Expect::structure([
					'type' => Expect::string()->required()->assert(fn ($input) => class_exists($input) || interface_exists($input)),
					'message' => Expect::string()->required(),
				])->required(false),
				'services' => Expect::structure([
					'existence' => Expect::arrayOf(
						Expect::arrayOf(
							Expect::structure([
								'exists' => Expect::bool()->required(),
							]),
							Expect::string()->required()->assert(fn ($input) => class_exists($input) || interface_exists($input))
						),
					),
					'counting' => Expect::arrayOf(
						Expect::arrayOf(
							Expect::structure([
								'count' => Expect::int()->required(),
							]),
							Expect::string()->required()->assert(fn ($input) => class_exists($input) || interface_exists($input))
						),
					),
					'tags' => Expect::arrayOf(
						Expect::arrayOf(
							Expect::structure([
								'count' => Expect::int()->required(),
							]),
							Expect::string()->required()
						)
					),
				]),
			]),
		]);
	}

}
