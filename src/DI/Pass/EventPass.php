<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventPass extends AbstractPass
{

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		// Nothing to register
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$dispatcher = $builder->getByType(EventDispatcherInterface::class);

		// Register event dispatcher
		if ($dispatcher !== null) {
			// Reuse existing dispatcher
			$builder->addDefinition($this->prefix('event.dispatcher'))
				->setFactory('@' . $dispatcher)
				->setAutowired(false);
		} else {
			// Register default fallback dispatcher
			$builder->addDefinition($this->prefix('event.dispatcher'))
				->setFactory(EventDispatcher::class)
				->setAutowired(false);
		}
	}

}
