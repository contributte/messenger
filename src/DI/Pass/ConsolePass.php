<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\Utils\BuilderMan;
use Nette\DI\Definitions\ServiceDefinition;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\DebugCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;
use Symfony\Component\Messenger\Command\SetupTransportsCommand;
use Symfony\Component\Messenger\Command\StatsCommand;
use Symfony\Component\Messenger\Command\StopWorkersCommand;

class ConsolePass extends AbstractPass
{

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('console.consumeCommand'))
			->setFactory(ConsumeMessagesCommand::class, [
				$this->prefix('@bus.routable'),
				$this->prefix('@transport.container'),
				$this->prefix('@event.dispatcher'),
				$this->prefix('@logger.logger'),
			]);

		$builder->addDefinition($this->prefix('console.debugCommand'))
			->setFactory(DebugCommand::class, [[]]);

		$builder->addDefinition($this->prefix('console.setupTransportsCommand'))
			->setFactory(SetupTransportsCommand::class, [$this->prefix('@transport.container'), []]);

		$builder->addDefinition($this->prefix('console.statsCommand'))
			->setFactory(StatsCommand::class, [$this->prefix('@transport.container'), []]);

		$builder->addDefinition($this->prefix('console.failedMessageRemoveCommand'))
			->setFactory(FailedMessagesRemoveCommand::class, [
				$config->failureTransport,
				$this->prefix('@failureTransport.serviceProvider'),
				$this->prefix('@serializer.default'),
			]);

		$builder->addDefinition($this->prefix('console.failedMessageRetryCommand'))
			->setFactory(FailedMessagesRetryCommand::class, [
				$config->failureTransport,
				$this->prefix('@failureTransport.serviceProvider'),
				$this->prefix('@bus.routable'),
				$this->prefix('@event.dispatcher'),
				$this->prefix('@logger.logger'),
				$this->prefix('@serializer.default'),
			]);

		$builder->addDefinition($this->prefix('console.failedMessageShowCommand'))
			->setFactory(FailedMessagesShowCommand::class, [
				$config->failureTransport,
				$this->prefix('@failureTransport.serviceProvider'),
				$this->prefix('@serializer.default'),
			]);

		if ($config->cache !== null) {
			$builder->addDefinition($this->prefix('console.stopWorkersCommand'))
				->setFactory(StopWorkersCommand::class, [$config->cache]);
		}
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$builderMan = BuilderMan::of($this);

		// Transport names
		$transportNames = array_keys($builderMan->getTransports());

		/** @var ServiceDefinition $setupTransportsCommandDef */
		$setupTransportsCommandDef = $builder->getDefinition($this->prefix('console.setupTransportsCommand'));
		$setupTransportsCommandDef->setArgument(1, $transportNames);

		/** @var ServiceDefinition $statsCommandDef */
		$statsCommandDef = $builder->getDefinition($this->prefix('console.statsCommand'));
		$statsCommandDef->setArgument(1, $transportNames);

		// Handler mapping
		/** @var ServiceDefinition $debugCommandDef */
		$debugCommandDef = $builder->getDefinition($this->prefix('console.debugCommand'));
		$debugCommandDef->setArgument(0, $builderMan->getHandlerMapping());
	}

}
