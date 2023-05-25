<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use Nette\DI\Config\Adapters\NeonAdapter;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;

final class Helpers
{

	/**
	 * @return mixed[]
	 */
	public static function neon(string $str): array
	{
		return (new NeonAdapter())->process((array) Neon::decode($str));
	}

	/**
	 * @return mixed[]
	 */
	public static function neonFile(string $file): array
	{
		return (new NeonAdapter())->process((array) Neon::decode(FileSystem::read($file)));
	}

}
