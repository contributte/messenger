<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI;

use Contributte\DI\Extension\CompilerExtension;
use Contributte\Messenger\DI\Pass\AbstractPass;
use Contributte\Messenger\DI\Pass\BusPass;
use Contributte\Messenger\DI\Pass\ConsolePass;
use Contributte\Messenger\DI\Pass\DebugPass;
use Contributte\Messenger\DI\Pass\EventPass;
use Contributte\Messenger\DI\Pass\HandlerPass;
use Contributte\Messenger\DI\Pass\LoggerPass;
use Contributte\Messenger\DI\Pass\RoutingPass;
use Contributte\Messenger\DI\Pass\SerializerPass;
use Contributte\Messenger\DI\Pass\TransportFactoryPass;
use Contributte\Messenger\DI\Pass\TransportPass;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;
use stdClass;

/**
 * @property-write stdClass $config
 */
class MessengerExtension extends CompilerExtension
{

	public const TRANSPORT_FACTORY_TAG = 'contributte.messenger.transport_factory';
	public const TRANSPORT_TAG = 'contributte.messenger.transport';
	public const FAILURE_TRANSPORT_TAG = 'contributte.messenger.failure_transport';
	public const BUS_TAG = 'contributte.messenger.bus';
	public const HANDLER_TAG = 'contributte.messenger.handler';
	public const RETRY_STRATEGY_TAG = 'contributte.messenger.retry_strategy';

	/** @var AbstractPass[] */
	protected array $passes = [];

	public function __construct()
	{
		// priority 10
		$this->passes[] = new SerializerPass($this);
		$this->passes[] = new TransportFactoryPass($this);
		$this->passes[] = new TransportPass($this);

		// priority 20
		$this->passes[] = new RoutingPass($this);
		$this->passes[] = new HandlerPass($this);

		// priority 30
		$this->passes[] = new EventPass($this);
		$this->passes[] = new LoggerPass($this);
		$this->passes[] = new ConsolePass($this);

		// priority 40
		$this->passes[] = new BusPass($this);
		$this->passes[] = new DebugPass($this);
	}

	public function getConfigSchema(): Schema
	{
		$expectClass = Expect::string()->required()->assert(fn ($input) => class_exists($input) || interface_exists($input));
		$expectService = Expect::anyOf(
			Expect::string()->required()->assert(fn ($input) => str_starts_with($input, '@') || class_exists($input) || interface_exists($input)),
			Expect::type(Statement::class)->required(),
		);
		$expectLoosyService = Expect::anyOf(
			Expect::string()->required(),
			Expect::type(Statement::class)->required(),
		);

		return Expect::structure([
			'debug' => Expect::structure([
				'panel' => Expect::bool(false),
			]),
			'bus' => Expect::arrayOf(
				Expect::structure([
					'defaultMiddlewares' => Expect::bool(true),
					'middlewares' => Expect::arrayOf($expectService),
					'allowNoHandlers' => Expect::bool(false),
					'allowNoSenders' => Expect::bool(true),
					'autowired' => Expect::bool(),
					'class' => Expect::string(),
				]),
				Expect::string()->required(),
			)->default(ArrayHash::from([
				'messageBus' => [
					'defaultMiddlewares' => true,
					'middlewares' => [],
					'class' => null,
					'autowired' => true,
					'allowNoHandlers' => false,
					'allowNoSenders' => false,
				],
			])),
			'serializer' => Expect::arrayOf(
				$expectService,
				Expect::string()->required()
			),
			'transportFactory' => Expect::arrayOf(
				$expectService,
				Expect::string()->required()
			),
			'failureTransport' => Expect::string(),
			'transport' => Expect::arrayOf(
				Expect::structure([
					'dsn' => Expect::string()->required()->dynamic(),
					'options' => Expect::array(),
					'serializer' => $expectLoosyService,
					'failureTransport' => Expect::string(),
					'retryStrategy' => Expect::anyOf(
						null,
						Expect::structure([
							'maxRetries' => Expect::int(),
							'delay' => Expect::int(),
							'multiplier' => Expect::anyOf(Expect::int(), Expect::float())->castTo('float'),
							'maxDelay' => Expect::int(),
							'service' => (clone $expectService)->nullable(),
						]),
					)->default(ArrayHash::from([
						'maxRetries' => 3,
						'delay' => 1000,
						'multiplier' => 1,
						'maxDelay' => 0,
						'service' => null,
					])),
				]),
				Expect::string()->required()
			),
			'routing' => Expect::arrayOf(
				Expect::arrayOf(
					Expect::string()->required(),
				),
				Expect::anyOf(
					Expect::string()->required(),
					$expectClass
				)
			),
			'logger' => Expect::structure([
				'httpLogger' => $expectService,
				'consoleLogger' => $expectService,
			]),
		]);
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->loadPassConfiguration();
		}
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->beforePassCompile();
		}
	}

	public function afterCompile(ClassType $class): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->afterPassCompile($class);
		}
	}

}
