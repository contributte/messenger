<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\BuilderMan;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactory;

class TransportFactoryPass extends AbstractPass
{

	public const DEFAULT_TRANSPORT_FACTORY = [
		'sync' => SyncTransportFactory::class,
		'inMemory' => InMemoryTransportFactory::class,
		'amqp' => AmqpTransportFactory::class,
		'redis' => RedisTransportFactory::class,
	];

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// Filter out class factory that cannot be found
		$defaultFactories = array_filter(self::DEFAULT_TRANSPORT_FACTORY, static fn ($class, $name) => class_exists($class), ARRAY_FILTER_USE_BOTH);

		// Merge default + user defined factories
		$transportFactories = array_merge($defaultFactories, (array) $config->transportFactory);

		foreach ($transportFactories as $name => $factory) {
			$builder->addDefinition($this->prefix(sprintf('transportFactory.%s', $name)))
				->setFactory($factory)
				->setAutowired(false)
				->addTag(MessengerExtension::TRANSPORT_FACTORY_TAG, $name);
		}

		$builder->addDefinition($this->prefix('transportFactory'))
			->setFactory(TransportFactory::class, [BuilderMan::of($this)->getTransportFactories()]);
	}

}
