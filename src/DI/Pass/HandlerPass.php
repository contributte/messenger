<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\Reflector;
use Contributte\Messenger\Exception\LogicalException;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use function array_merge;
use function is_numeric;
use function is_string;

class HandlerPass extends AbstractPass
{

	private const DEFAULT_METHOD_NAME = '__invoke';
	private const DEFAULT_PRIORITY = 0;

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
					new ReflectionClass($serviceClass);
				} catch (ReflectionException $e) {
					throw new LogicalException(sprintf('Handler "%s" class not found', $serviceClass), 0, $e);
				}

				$tagsOptions = $this->getTagsOptions($serviceDef, $serviceName, $busName);
				$attributesOptions = $this->getAttributesOptions($serviceClass, $serviceName, $busName);

				foreach (array_merge($tagsOptions, $attributesOptions) as $options) {
					// Autodetect handled message
					if (!isset($options['handles'])) {
						$options['handles'] = Reflector::getMessageHandlerMessage($serviceClass, $options);
					}

					// If handler is not for current bus, then skip it
					if ($options['bus'] !== $busName) {
						continue;
					}

					$handlers[$options['handles']][$options['priority']][] = $options;
				}
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
			if (Reflector::getMessageHandlers($class) === []) {
				continue;
			}

			$serviceHandlers[] = $definition->getName();
		}

		// Clean duplicates
		return array_unique($serviceHandlers);
	}

	/**
	 * @return list<array{
	 *     service: string,
	 *     bus: string,
	 *     alias: string|null,
	 *     method: string,
	 *     handles: string|null,
	 *     priority: int,
	 *     from_transport: string|null
	 *  }>
	 */
	private function getTagsOptions(Definition $serviceDefinition, string $serviceName, string $defaultBusName): array
	{
		// Drain service tag
		$tags = (array) $serviceDefinition->getTag(MessengerExtension::HANDLER_TAG);
		$isList = $tags === [] || array_keys($tags) === range(0, count($tags) - 1);
		/** @var list<array<mixed>> $tags */
		$tags = $isList ? $tags : [$tags];
		$tagsOptions = [];

		foreach ($tags as $tag) {
			$tagsOptions[] = [
				'service' => $serviceName,
				'bus' => isset($tag['bus']) && is_string($tag['bus']) ? $tag['bus'] : $defaultBusName,
				'alias' => isset($tag['alias']) && is_string($tag['alias']) ? $tag['alias'] : null,
				'method' => isset($tag['method']) && is_string($tag['method']) ? $tag['method'] : self::DEFAULT_METHOD_NAME,
				'handles' => isset($tag['handles']) && is_string($tag['handles']) ? $tag['handles'] : null,
				'priority' => isset($tag['priority']) && is_numeric($tag['priority']) ? (int) $tag['priority'] : self::DEFAULT_PRIORITY,
				'from_transport' => isset($tag['from_transport']) && is_string($tag['from_transport']) ? $tag['from_transport'] : null,
			];
		}

		return $tagsOptions;
	}

	/**
	 * @param class-string $serviceClass
	 * @return list<array{
	 *     service: string,
	 *     bus: string,
	 *     alias: null,
	 *     method: string,
	 *     priority: int,
	 *     handles: string|null,
	 *     from_transport: string|null
	 *  }>
	 */
	private function getAttributesOptions(string $serviceClass, string $serviceName, string $defaultBusName): array
	{
		// Drain service attribute
		$attributes = Reflector::getMessageHandlers($serviceClass);
		$attributesOptions = [];

		foreach ($attributes as $attribute) {
			$attributesOptions[] = [
				'service' => $serviceName,
				'bus' => $attribute->bus ?? $defaultBusName,
				'alias' => null,
				'method' => $attribute->method ?? self::DEFAULT_METHOD_NAME,
				'priority' => $attribute->priority ?? self::DEFAULT_PRIORITY,
				'handles' => $attribute->handles ?? null,
				'from_transport' => $attribute->fromTransport ?? null,
			];
		}

		return $attributesOptions;
	}

}
