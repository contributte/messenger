<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class SerializerPass extends AbstractPass
{

	public const DEFAULT_SERIALIZER = [
		'default' => PhpSerializer::class,
	];

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$serializers = array_merge(self::DEFAULT_SERIALIZER, (array) $config->serializer);
		foreach ($serializers as $name => $factory) {
			$builder->addDefinition($this->prefix(sprintf('serializer.%s', $name)))
				->setFactory($factory)
				->setAutowired(false);
		}
	}

}
