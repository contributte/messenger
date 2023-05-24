<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use Contributte\Messenger\DI\MessengerExtension;
use Nette\DI\Compiler;
use Nette\DI\Container as NetteContainer;
use Nette\DI\ContainerLoader;

final class Container
{

	private string $key;

	/** @var callable[] */
	private array $onCompile = [];

	/** @var mixed[] */
	private array $parameters = [];

	public function __construct(string $key)
	{
		$this->key = $key;
	}

	public static function of(?string $key = null): Container
	{
		return new static($key ?? uniqid(random_bytes(16)));
	}

	public function withDefaults(): Container
	{
		$this->withDefaultExtensions();
		$this->withDefaultParameters();

		return $this;
	}

	public function withDefaultExtensions(): Container
	{
		$this->onCompile[] = function (Compiler $compiler): void {
			$compiler->addExtension('messenger', new MessengerExtension());
		};

		return $this;
	}

	public function withDefaultParameters(): Container
	{
		$this->onCompile[] = function (Compiler $compiler): void {
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
					'appDir' => Tests::APP_PATH,
				],
			]);
			$compiler->addConfig(Helpers::neon('
				messenger:
			'));
		};

		return $this;
	}

	/**
	 * @param mixed[] $parameters
	 */
	public function withDynamicParameters(array $parameters): Container
	{
		$this->parameters = $parameters;

		return $this;
	}

	public function withCompiler(callable $cb): Container
	{
		$this->onCompile[] = function (Compiler $compiler) use ($cb): void {
			$cb($compiler);
		};

		return $this;
	}

	public function build(): NetteContainer
	{
		$loader = new ContainerLoader(Tests::TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			foreach ($this->onCompile as $cb) {
				$cb($compiler);
			}

			$compiler->setDynamicParameterNames(array_keys($this->parameters));
		}, $this->key);

		return new $class($this->parameters);
	}

}
