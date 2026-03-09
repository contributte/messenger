<?php declare(strict_types = 1);

namespace Contributte\Messenger\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class MessengerLogger extends AbstractLogger
{

	private LoggerInterface $httpLogger;

	private LoggerInterface $consoleLogger;

	public function __construct(
		?LoggerInterface $httpLogger = null,
		?LoggerInterface $consoleLogger = null
	)
	{
		$this->httpLogger = $httpLogger ?? new NullLogger();

		$this->consoleLogger = $consoleLogger ?? new ConsoleLogger(
			new ConsoleOutput(
				OutputInterface::VERBOSITY_VERY_VERBOSE
			)
		);
	}

	/**
	 * @param mixed[] $context
	 */
	public function log(mixed $level, Stringable|string $message, array $context = []): void
	{
		$logger = PHP_SAPI === 'cli' ? $this->consoleLogger : $this->httpLogger;
		$logger->log($level, $message, $context);
	}

}
