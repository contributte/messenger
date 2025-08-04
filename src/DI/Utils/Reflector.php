<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Utils;

use Contributte\Messenger\Exception\LogicalException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use Symfony\Component\Messenger\Attribute\AsMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function array_map;
use function array_merge;
use function implode;
use function sprintf;

final class Reflector
{

	/**
	 * @param class-string $class
	 * @return array<AsMessageHandler>
	 */
	public static function getMessageHandlers(string $class): array
	{
		$rc = new ReflectionClass($class);

		$classAttributes = array_map(
			static fn (ReflectionAttribute $attribute): AsMessageHandler => $attribute->newInstance(),
			$rc->getAttributes(AsMessageHandler::class),
		);

		$methodAttributes = [];

		foreach ($rc->getMethods() as $method) {
			$methodAttributes[] = array_map(
				static function (ReflectionAttribute $reflectionAttribute) use ($method): AsMessageHandler {
					$attribute = $reflectionAttribute->newInstance();
					$attribute->method = $method->getName();

					return $attribute;
				},
				$method->getAttributes(AsMessageHandler::class),
			);
		}

		return array_merge($classAttributes, ...$methodAttributes);
	}

	/**
	 * The classes of the message a handler method handles, the way Symfony's MessengerPass guesses them: the first
	 * parameter is the message, every further one is the handler's own (an Acknowledger, arguments of
	 * HandlerArgumentsStamp), and a union handles each of its classes.
	 *
	 * @param class-string $class
	 * @param array{method: string} $options
	 * @return list<string>
	 */
	public static function getMessageHandlerMessage(string $class, array $options): array
	{
		try {
			$rc = new ReflectionClass($class);
		} catch (ReflectionException $e) {
			throw new LogicalException(sprintf('Handler "%s" class not found', $class), 0, $e);
		}

		try {
			$rcMethod = $rc->getMethod($options['method']);
		} catch (ReflectionException) {
			throw new LogicalException(sprintf('Handler must have "%s::%s()" method.', $class, $options['method']));
		}

		if ($rcMethod->getNumberOfRequiredParameters() === 0) {
			throw new LogicalException(sprintf('Handler "%s::%s()" requires at least one argument, first one being the message it handles.', $class, $options['method']));
		}

		$parameter = $rcMethod->getParameters()[0];

		/** @var ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type */
		$type = $parameter->getType();

		if ($type === null) {
			throw new LogicalException(sprintf('Cannot detect parameter type for "%s::%s()."', $class, $options['method']));
		}

		if ($type instanceof ReflectionIntersectionType) {
			throw new LogicalException(sprintf('Intersection parameter type for "%s::%s() is not supported."', $class, $options['method']));
		}

		if ($type instanceof ReflectionUnionType) {
			$types = [];
			$invalidTypes = [];

			foreach ($type->getTypes() as $subType) {
				if ($subType instanceof ReflectionNamedType && !$subType->isBuiltin()) {
					$types[] = $subType->getName();
				} else {
					$invalidTypes[] = (string) $subType;
				}
			}

			if ($types !== []) {
				return $types;
			}

			throw new LogicalException(sprintf('Type of the argument "$%s" of "%s::%s()" must be a class, "%s" given.', $parameter->getName(), $class, $options['method'], implode('|', $invalidTypes)));
		}

		if ($type->isBuiltin()) {
			throw new LogicalException(sprintf('Type of the argument "$%s" of "%s::%s()" must be a class, "%s" given.', $parameter->getName(), $class, $options['method'], $type->getName()));
		}

		return [$type->getName()];
	}

	/**
	 * @param class-string $handlerClass
	 * @return list<string>
	 */
	public static function getHandlerMessageClasses(string $handlerClass): array
	{
		$messages = [];

		$handlers = self::getMessageHandlers($handlerClass);

		foreach ($handlers as $handler) {
			$method = $handler->method ?? '__invoke';

			try {
				$messages = array_merge($messages, self::getMessageHandlerMessage($handlerClass, ['method' => $method]));
			} catch (\Throwable) {
				// Skip handlers that can't be resolved
			}
		}

		// Try default __invoke method only if no handlers were found from attributes
		if ($handlers === []) {
			try {
				$messages = array_merge($messages, self::getMessageHandlerMessage($handlerClass, ['method' => '__invoke']));
			} catch (\Throwable) {
				// Skip
			}
		}

		return $messages;
	}

	/**
	 * @param class-string $class
	 * @return list<AsMessage>
	 */
	public static function getMessageRouting(string $class): array
	{
		$rc = new ReflectionClass($class);

		return array_map(
			static fn (ReflectionAttribute $attribute): AsMessage => $attribute->newInstance(),
			$rc->getAttributes(AsMessage::class),
		);
	}

}
