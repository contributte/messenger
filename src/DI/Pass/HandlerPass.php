<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\Reflector;
use Contributte\Messenger\Exception\LogicalException;
use Nette\DI\Definitions\ServiceDefinition;
use ReflectionClass;
use ReflectionException;
use stdClass;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class HandlerPass extends AbstractPass
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
		$config = $this->getConfig();

		// Attach message handlers to bus
		foreach ($config->bus as $busName => $busConfig) {
			$handlers = [];

			// Collect all message handlers from DIC
			$serviceHandlers = $this->getMessageHandlers();

			// Iterate all found handlers
			foreach ($serviceHandlers as $serviceName) {
				$serviceDef = $builder->getDefinition($serviceName);
				/** @var class-string $serviceClass */
				$serviceClass = $serviceDef->getType();

				// Ensure handler class exists
				try {
					$rc = new ReflectionClass($serviceClass);
				} catch (ReflectionException $e) {
					throw new LogicalException(sprintf('Handler "%s" class not found', $serviceClass), 0, $e);
				}

				// Drain service tag
				$tag = (array) $serviceDef->getTag(MessengerExtension::HANDLER_TAG);
				$tagOptions = [
					'bus' => $tag['bus'] ?? null,
					'alias' => $tag['alias'] ?? null,
					'method' => $tag['method'] ?? null,
					'handles' => $tag['handles'] ?? null,
					'priority' => $tag['priority'] ?? null,
					'from_transport' => $tag['from_transport'] ?? null,
				];

				// Drain service attribute
				/** @var AsMessageHandler[] $attributes */
				$attributes = $rc->getAttributes(AsMessageHandler::class);
				$attributeHandler = $attributes[0] ?? new stdClass();
				$attributeOptions = [
					'bus' => $attributeHandler->bus ?? null,
					'method' => $attributeHandler->method ?? null,
					'priority' => $attributeHandler->priority ?? null,
					'handles' => $attributeHandler->handles ?? null,
					'from_transport' => $attributeHandler->fromTransport ?? null,
				];

				// Complete final options
				$options = [
					'service' => $serviceName,
					'bus' => $tagOptions['bus'] ?? $attributeOptions['bus'] ?? $busName,
					'alias' => $tagOptions['alias'] ?? null,
					'method' => $tagOptions['method'] ?? $attributeOptions['method'] ?? '__invoke',
					'handles' => $tagOptions['handles'] ?? $attributeOptions['handles'] ?? null,
					'priority' => $tagOptions['priority'] ?? $attributeOptions['priority'] ?? 0,
					'from_transport' => $tagOptions['from_transport'] ?? $attributeOptions['from_transport'] ?? null,
				];

				// Autodetect handled message
				if (!isset($options['handles'])) {
					$options['handles'] = Reflector::getMessageHandlerMessage($serviceClass, $options);
				}

				// If handler is not for current bus, then skip it
				if (($tagOptions['bus'] ?? $attributeOptions['bus'] ?? $busName) !== $busName) {
					continue;
				}

				$handlers[$options['handles']][$options['priority']][] = $options;
			}

			// Sort handlers by priority
			foreach ($handlers as $message => $handlersByPriority) {
				krsort($handlersByPriority);
				$handlers[$message] = array_merge(...$handlersByPriority);
			}

			// Replace handlers in bus
			/** @var ServiceDefinition $busHandlerLocator */
			$busHandlerLocator = $builder->getDefinition($this->prefix(sprintf('bus.%s.locator', $busName)));
			$busHandlerLocator->setArgument(0, $handlers);
		}
	}

	/**
	 * @return array<int, string>
	 */
	private function getMessageHandlers(): array
	{
		$builder = $this->getContainerBuilder();

		// Find all handlers
		$serviceHandlers = [];
		$serviceHandlers = array_merge($serviceHandlers, array_keys($builder->findByTag(MessengerExtension::HANDLER_TAG)));
		$serviceHandlers = array_merge($serviceHandlers, array_keys($builder->findByType(MessageHandlerInterface::class)));

		foreach ($builder->getDefinitions() as $definition) {
			/** @var class-string $class */
			$class = $definition->getType();

			// Skip definitions without type
			if ($definition->getType() === null) {
				continue;
			}

			// Skip definitions without name
			if ($definition->getName() === null) {
				continue;
			}

			// Skip services without attribute
			if (Reflector::getMessageHandler($class) === null) {
				continue;
			}

			$serviceHandlers[] = $definition->getName();
		}

		// Clean duplicates
		return array_unique($serviceHandlers);
	}

}
