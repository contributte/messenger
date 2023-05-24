<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Utils;

use Contributte\Messenger\DI\Pass\AbstractPass;
use Nette\DI\Definitions\Statement;

final class ServiceMan
{

	private AbstractPass $pass;

	private function __construct(AbstractPass $pass)
	{
		$this->pass = $pass;
	}

	public static function of(AbstractPass $pass): self
	{
		return new self($pass);
	}

	public function getSerializer(string|Statement|null $serializer): Statement|string
	{
		if ($serializer === null) {
			return $this->pass->prefix('@serializer.default');
		}

		if (is_string($serializer) && !str_starts_with($serializer, '@') && !str_contains($serializer, '\\')) {
			return $this->pass->prefix(sprintf('@serializer.%s', $serializer));
		}

		if ($serializer instanceof Statement) {
			return $serializer;
		}

		return new Statement($serializer);
	}

}
