<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\Logger\MessengerLogger;
use Nette\DI\Definitions\ServiceDefinition;

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
			->setType(MessengerLogger::class)
			->setAutowired(false);
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		/** @var ServiceDefinition $loggerDef */
		$loggerDef = $builder->getDefinition($this->prefix('logger.logger'));

		// Register or resolve http logger
		if ($config->logger->httpLogger !== null) {
			$httpLogger = $builder->addDefinition($this->prefix('logger.httpLogger'))
				->setFactory($config->logger->httpLogger)
				->setAutowired(false);

			$loggerDef->setArgument(0, $httpLogger);
		}

		// Register or resolve console logger
		if ($config->logger->consoleLogger !== null) {
			$consoleLogger = $builder->addDefinition($this->prefix('logger.consoleLogger'))
				->setFactory($config->logger->consoleLogger)
				->setAutowired(false);

			$loggerDef->setArgument(1, $consoleLogger);
		}
	}

}
