<?php declare(strict_types = 1);

namespace Tests\Toolkit;

final class Console
{

	public static function normalize(string $s): string
	{
		return implode("\n", array_map(
			static fn (string $line): string => rtrim(ltrim($line, "\t")),
			explode("\n", trim($s)),
		));
	}

}
