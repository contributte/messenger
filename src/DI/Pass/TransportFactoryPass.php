<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\BuilderMan;
use Doctrine\Persistence\ConnectionRegistry;
use Nette\DI\Definitions\ServiceDefinition;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory;
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
		'doctrine' => DoctrineTransportFactory::class,
	];

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// Filter out class factory that cannot be found, exclude doctrine (requires ConnectionRegistry)
		$defaultFactories = array_filter(
			self::DEFAULT_TRANSPORT_FACTORY,
			static fn ($class, $name) => class_exists($class) && $name !== 'doctrine',
			ARRAY_FILTER_USE_BOTH,
		);

		// Merge default + user defined factories
		$transportFactories = array_merge($defaultFactories, (array) $config->transportFactory);

		foreach ($transportFactories as $name => $factory) {
			$builder->addDefinition($this->prefix(sprintf('transportFactory.%s', $name)))
				->setFactory($factory)
				->setAutowired(false)
				->addTag(MessengerExtension::TRANSPORT_FACTORY_TAG, $name);
		}

		// Placeholder: TransportFactory will be finalized in beforePassCompile
		$builder->addDefinition($this->prefix('transportFactory'))
			->setFactory(TransportFactory::class, [[]]);
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// Register Doctrine transport factory when ConnectionRegistry is available
		if (class_exists(DoctrineTransportFactory::class) && interface_exists(ConnectionRegistry::class)) {
			if ($builder->getByType(ConnectionRegistry::class, false) !== null) {
				$builder->addDefinition($this->prefix('transportFactory.doctrine'))
					->setFactory(DoctrineTransportFactory::class)
					->setAutowired(false)
					->addTag(MessengerExtension::TRANSPORT_FACTORY_TAG, 'doctrine');
			}
		}

		// Finalize TransportFactory with all registered factories
		/** @var ServiceDefinition $transportFactoryDef */
		$transportFactoryDef = $builder->getDefinition($this->prefix('transportFactory'));
		$transportFactoryDef->setArgument(0, BuilderMan::of($this)->getTransportFactories());
	}

}
