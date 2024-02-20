<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\Logger\MessengerLogger;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\ServiceCreationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class LoggerPass extends AbstractPass
{

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('logger.logger'))
			->setFactory(MessengerLogger::class)
			->setAutowired(false);
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$multiple = null;
		try {
			$logger = $builder->getByType(LoggerInterface::class, false);
		} catch (ServiceCreationException $e) {
			// multiple loggers
			$multiple = $e;
		}

		// Register or resolve http logger
		if ($config->logger->httpLogger !== null) {
			$httpLogger = $builder->addDefinition($this->prefix('logger.httpLogger'))
				->setFactory($config->logger->httpLogger)
				->setAutowired(false);
		} elseif ($multiple !== null) {
			throw $e;
		} elseif ($logger !== null) {
			$httpLogger = $builder->addDefinition($this->prefix('logger.httpLogger'))
				->setFactory('@' . $logger)
				->setAutowired(false);
		} else {
			$httpLogger = $builder->addDefinition($this->prefix('logger.httpLogger'))
				->setFactory(NullLogger::class)
				->setAutowired(false);
		}

		// Register or resolve console logger
		if ($config->logger->consoleLogger !== null) {
			$consoleLogger = $builder->addDefinition($this->prefix('logger.consoleLogger'))
				->setFactory($config->logger->consoleLogger)
				->setAutowired(false);
		} elseif ($multiple !== null) {
			throw $e;
		} elseif ($logger !== null) {
			$consoleLogger = $builder->addDefinition($this->prefix('logger.consoleLogger'))
				->setFactory('@' . $logger)
				->setAutowired(false);
		} else {
			$consoleLogger = $builder->addDefinition($this->prefix('logger.consoleLogger'))
				->setFactory(ConsoleLogger::class, [
					new Statement(ConsoleOutput::class, [
						OutputInterface::VERBOSITY_VERY_VERBOSE,
					]),
				])
				->setAutowired(false);
		}

		/** @var ServiceDefinition $loggerDef */
		$loggerDef = $builder->getDefinition($this->prefix('logger.logger'));
		$loggerDef->setArguments([$httpLogger, $consoleLogger]);
	}

}
