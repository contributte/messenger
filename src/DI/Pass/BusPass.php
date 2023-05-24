<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\Bus\BusRegistry;
use Contributte\Messenger\Bus\CommandBus;
use Contributte\Messenger\Bus\MessageBus;
use Contributte\Messenger\Bus\QueryBus;
use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\BuilderMan;
use Contributte\Messenger\Handler\ContainerServiceHandlersLocator;
use Nette\DI\Definitions\ServiceDefinition;
use Symfony\Component\Messenger\MessageBus as SymfonyMessageBus;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\RoutableMessageBus;

class BusPass extends AbstractPass
{

	private const BUS_WRAPPERS = [
		'messageBus' => MessageBus::class,
		'queryBus' => QueryBus::class,
		'commandBus' => CommandBus::class,
	];

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// Iterate all buses
		foreach ($config->bus as $name => $busConfig) {
			$middlewares = [];

			$builder->addDefinition($this->prefix(sprintf('bus.%s.locator', $name)))
				->setFactory(ContainerServiceHandlersLocator::class, [[]])
				->setAutowired(false);

			foreach ($busConfig->middlewares as $index => $middlewareConfig) {
				$middlewares[] = $builder->addDefinition($this->prefix(sprintf('bus.%s.middleware.custom%sMiddleware', $name, $index)))
					->setFactory($middlewareConfig)
					->setAutowired(false);
			}

			$middlewares[] = $builder->addDefinition($this->prefix(sprintf('bus.%s.middleware.busStampMiddleware', $name)))
				->setFactory(AddBusNameStampMiddleware::class, [$name])
				->setAutowired(false);

			$middlewares[] = $builder->addDefinition($this->prefix(sprintf('bus.%s.middleware.sendMiddleware', $name)))
				->setFactory(SendMessageMiddleware::class, [$this->prefix('@routing.locator'), $this->prefix('@event.dispatcher'), $busConfig->allowNoSenders])
				->setAutowired(false)
				->addSetup('setLogger', [$this->prefix('@logger.logger')]);

			$middlewares[] = $builder->addDefinition($this->prefix(sprintf('bus.%s.middleware.handleMiddleware', $name)))
				->setFactory(HandleMessageMiddleware::class, [$this->prefix(sprintf('@bus.%s.locator', $name)), $busConfig->allowNoHandlers])
				->setAutowired(false)
				->addSetup('setLogger', [$this->prefix('@logger.logger')]);

			$builder->addDefinition($this->prefix(sprintf('bus.%s.bus', $name)))
				->setFactory($busConfig->class ?? SymfonyMessageBus::class, [$middlewares])
				->setAutowired($busConfig->autowired ?? count($builder->findByTag(MessengerExtension::BUS_TAG)) === 0)
				->setTags([MessengerExtension::BUS_TAG => $name]);

			// Register bus wrapper
			if (isset(self::BUS_WRAPPERS[$name])) {
				$builder->addDefinition($this->prefix(sprintf('bus.%s.wrapper', $name)))
					->setFactory(self::BUS_WRAPPERS[$name], [$this->prefix(sprintf('@bus.%s.bus', $name))]);
			}
		}

		// Register bus container
		$builder->addDefinition($this->prefix('bus.container'))
			->setFactory(NetteContainer::class)
			->setAutowired(false);

		// Register routable bus (for CLI)
		$builder->addDefinition($this->prefix('bus.routable'))
			->setFactory(RoutableMessageBus::class, [$this->prefix('@bus.container')]) // @TODO fallbackBus
			->setAutowired(false);

		// Register bus registry
		$builder->addDefinition($this->prefix('busRegistry'))
			->setFactory(BusRegistry::class, [$this->prefix('@bus.container')]);
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// Replace buses
		/** @var ServiceDefinition $transportContainerDef */
		$transportContainerDef = $builder->getDefinition($this->prefix('bus.container'));
		$transportContainerDef->setArgument(0, BuilderMan::of($this)->getBuses());
	}

}
