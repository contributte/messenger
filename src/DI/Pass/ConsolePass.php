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
		$builder = $this->extension->getContainerBuilder();
		$config = $this->getConfig();

		$builder->addDefinition($this->extension->prefix('console.consumeCommand'))
			->setFactory(ConsumeMessagesCommand::class, [
				$this->prefix('@bus.routable'),
				$this->prefix('@transport.container'),
				$this->prefix('@event.dispatcher'),
				$this->prefix('@logger.logger'),
			]);

		$builder->addDefinition($this->extension->prefix('console.debugCommand'))
			->setFactory(DebugCommand::class, [[]]); // @TODO mapping

		$builder->addDefinition($this->extension->prefix('console.setupTransportsCommand'))
			->setFactory(SetupTransportsCommand::class, [$this->prefix('@transport.container'), []]); // @TODO transportNames

		if (class_exists(StatsCommand::class)) {
			$builder->addDefinition($this->extension->prefix('console.statsCommand'))
				->setFactory(StatsCommand::class, [$this->prefix('@transport.container'), []]); // @TODO transportNames
		}

		$builder->addDefinition($this->extension->prefix('console.failedMessageRemoveCommand'))
			->setFactory(FailedMessagesRemoveCommand::class, [
				$config->failureTransport,
				$this->prefix('@failureTransport.serviceProvider'),
				$this->prefix('@serializer.default'),
			]);

		$builder->addDefinition($this->extension->prefix('console.failedMessageRetryCommand'))
			->setFactory(FailedMessagesRetryCommand::class, [
				$config->failureTransport,
				$this->prefix('@failureTransport.serviceProvider'),
				$this->prefix('@bus.routable'),
				$this->prefix('@event.dispatcher'),
				$this->prefix('@logger.logger'),
				$this->prefix('@serializer.default'),
			]);

		$builder->addDefinition($this->extension->prefix('console.failedMessageShowCommand'))
			->setFactory(FailedMessagesShowCommand::class, [
				$config->failureTransport,
				$this->prefix('@failureTransport.serviceProvider'),
				$this->prefix('@serializer.default'),
			]);

		if (PHP_VERSION === 'fake') {
			// TODO
			$builder->addDefinition($this->extension->prefix('console.stopWorkersCommand'))
				->setFactory(StopWorkersCommand::class);
		}
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var ServiceDefinition $setupTransportsCommandDef */
		$setupTransportsCommandDef = $builder->getDefinition($this->prefix('console.setupTransportsCommand'));
		$setupTransportsCommandDef->setArgument(1, array_keys(BuilderMan::of($this)->getTransports()));
	}

}
