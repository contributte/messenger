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

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();
		$transportFactories = (array) $config->transportFactory;

		foreach ($transportFactories as $name => $factory) {
			$builder->addDefinition($this->prefix(sprintf('transportFactory.%s', $name)))
				->setFactory($factory)
				->setAutowired(false)
				->addTag(MessengerExtension::TRANSPORT_FACTORY_TAG, $name);
		}

		if (class_exists(SyncTransportFactory::class) && !$builder->hasDefinition($this->prefix('transportFactory.sync'))) {
			$builder->addDefinition($this->prefix('transportFactory.sync'))
				->setFactory(SyncTransportFactory::class, [$this->prefix('@bus.routable')])
				->setAutowired(false)
				->addTag(MessengerExtension::TRANSPORT_FACTORY_TAG, 'sync');
		}

		if (class_exists(InMemoryTransportFactory::class) && !$builder->hasDefinition($this->prefix('transportFactory.inMemory'))) {
			$builder->addDefinition($this->prefix('transportFactory.inMemory'))
				->setFactory(InMemoryTransportFactory::class)
				->setAutowired(false)
				->addTag(MessengerExtension::TRANSPORT_FACTORY_TAG, 'inMemory');
		}

		if (class_exists(AmqpTransportFactory::class) && !$builder->hasDefinition($this->prefix('transportFactory.amqp'))) {
			$builder->addDefinition($this->prefix('transportFactory.amqp'))
				->setFactory(AmqpTransportFactory::class)
				->setAutowired(false)
				->addTag(MessengerExtension::TRANSPORT_FACTORY_TAG, 'amqp');
		}

		if (class_exists(RedisTransportFactory::class) && !$builder->hasDefinition($this->prefix('transportFactory.redis'))) {
			$builder->addDefinition($this->prefix('transportFactory.redis'))
				->setFactory(RedisTransportFactory::class)
				->setAutowired(false)
				->addTag(MessengerExtension::TRANSPORT_FACTORY_TAG, 'redis');
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
			if (
				$builder->getByType(ConnectionRegistry::class, false) !== null
				&& !$builder->hasDefinition($this->prefix('transportFactory.doctrine'))
			) {
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
