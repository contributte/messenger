![](https://heatbadger.now.sh/github/readme/contributte/messenger/)

<p align=center>
  <a href="https://github.com/contributte/messenger/actions"><img src="https://badgen.net/github/checks/contributte/messenger/master?cache=300"></a>
  <a href="https://codecov.io/gh/contributte/messenger"><img src="https://badgen.net/codecov/c/github/contributte/messenger"></a>
  <a href="https://packagist.org/packages/contributte/messenger"> <img src="https://badgen.net/packagist/dm/contributte/messenger"> </a>
  <a href="https://packagist.org/packages/contributte/messenger"> <img src="https://badgen.net/packagist/v/contributte/messenger"> </a>
</p>
<p align=center>
  <a href="https://packagist.org/packages/contributte/messenger"><img src="https://badgen.net/packagist/php/contributte/messenger"></a>
  <a href="https://github.com/contributte/messenger"><img src="https://badgen.net/github/license/contributte/messenger"></a>
  <a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
  <a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
  <a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/become/a%20patron/F96854"></a>
</p>

<p align=center>
Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

Best Symfony Messenger for Nette framework. It provides multiple bus types, async transports, retry strategies, failure transport, and handler auto-discovery.

## Versions

| State  | Version | Branch   | Nette | PHP     |
|--------|---------|----------|-------|---------|
| dev    | `^0.3`  | `master` | 3.2+  | `>=8.2` |
| stable | `^0.2`  | `master` | 3.2+  | `>=8.2` |

## Contents

- [Setup](#setup)
- [Optional integrations](#optional-integrations)
- [Configuration](#configuration)
- [Integrations](#integrations)
- [Limitations](#limitations)
- [Examples](#examples)
- [Features](#features)
- [Credits](#credits)
- [Development](#development)

## Setup

```bash
composer require contributte/messenger
```

```neon
extensions:
  messenger: Contributte\Messenger\DI\MessengerExtension
```

## Optional integrations

Take advantage of empowering this package with extra packages:

- `symfony/console` via [contributte/console](https://github.com/contributte/console)
- `symfony/event-dispatcher` via [contributte/event-dispatcher](https://github.com/contributte/event-dispatcher)

### `symfony/console`

This package relies on `symfony/console`, use prepared [contributte/console](https://github.com/contributte/console)
integration.

```bash
composer require contributte/console
```

```neon
extensions:
  console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
```

Since this moment when you type `bin/console`, there will be registered commands from Symfony Messenger.

![Console Commands](.docs/assets/console.png)

### `symfony/event-dispatcher`

This package relies on `symfony/event-dispatcher`, use
prepared [contributte/event-dispatcher](https://github.com/contributte/event-dispatcher) integration.

```bash
composer require contributte/event-dispatcher
```

```neon
extensions:
  events: Contributte\EventDispatcher\DI\EventDispatcherExtension
```

## Configuration

> [!TIP]
> See [Symfony Messenger documentation](https://symfony.com/doc/current/messenger.html) for more details.

### Transports

| Transport | DSN | Description |
|-----------|-----|-------------|
| [Sync](https://symfony.com/doc/current/messenger.html#transport-configuration) | `sync://` | Handles messages immediately |
| [In-Memory](https://symfony.com/doc/current/messenger.html#in-memory-transport) | `in-memory://` | Stores messages in memory (testing) |
| [Redis](https://symfony.com/doc/current/messenger.html#redis-transport) | `redis://localhost:6379` | Async via Redis streams |
| [Doctrine](https://symfony.com/doc/current/messenger.html#doctrine-transport) | `doctrine://default` | Async via database table |
| [AMQP](https://symfony.com/doc/current/messenger.html#amqp-transport) | `amqp://guest:guest@localhost:5672` | Async via RabbitMQ |

### Console Commands

| Command | Description |
|---------|-------------|
| [`messenger:consume`](https://symfony.com/doc/current/messenger.html#consuming-messages-running-the-worker) | Consume messages from transport |
| [`messenger:stop-workers`](https://symfony.com/doc/current/messenger.html#deploying-to-production) | Gracefully stop workers |
| `messenger:setup-transports` | Setup transport infrastructure |
| [`messenger:failed:show`](https://symfony.com/doc/current/messenger.html#saving-retrying-failed-messages) | Show failed messages |
| [`messenger:failed:retry`](https://symfony.com/doc/current/messenger.html#saving-retrying-failed-messages) | Retry failed messages |
| [`messenger:failed:remove`](https://symfony.com/doc/current/messenger.html#saving-retrying-failed-messages) | Remove failed messages |
| `messenger:debug` | Debug routing and handlers |

Minimal configuration example:

```neon
messenger:
    transport:
        sync:
            dsn: sync://

    routing:
        App\Domain\SimpleMessage: [sync]

services:
    - App\Domain\SimpleMessageHandler
```

Full configuration example:

```neon
messenger:
    # Enable or disable Tracy debug panel
    debug:
        panel: %debugMode%

    # Worker limits for production environments.
    # Workers will gracefully stop when configured limit is reached.
    worker:
        memoryLimit: 134217728  # int|null (bytes), e.g. 128 MB
        timeLimit: 3600         # int|null (seconds), e.g. 1 hour
        messageLimit: 1000      # int|null, stop after N messages
        failureLimit: 5         # int|null, stop after N failures

    # PSR-6 cache pool for messenger:stop-workers command and worker restart signal.
    # When configured, registers StopWorkersCommand and StopWorkerOnRestartSignalListener.
    cache: @cache.pool

    # Fallback bus for RoutableMessageBus. Used when no bus stamp is present on the envelope.
    # Defaults to null (no fallback). Set to a bus name to enable.
    fallbackBus: messageBus

    # Defines buses, default one are messageBus, queryBus and commandBus.
    bus:
        messageBus:
            # To disable default middlewares stack (see https://symfony.com/doc/current/messenger.html#middleware)
            defaultMiddlewares: false

            # Define middlewares just for this bus.
            middlewares:
                #- LoggerMiddleware()
                #- @loggerMiddleware
            autowired: true
            allowNoHandlers: false
            allowNoSenders: true

            # Defined class must implement MessageBusInterface
            class: App\Model\Bus\MyMessageBus

            # Define wrapper class for easy autowiring between multiple buses (eventBus, messageBus, commandBus, ...)
            wrapper: App\Model\Bus\CommandBus

        queryBus:
            autowired: false

    # Defines serializers.
    serializer:
        default: Symfony\Component\Messenger\Transport\Serialization\PhpSerializer
        # custom: @customSerializer

    # Defines loggers.
    # - httpLogger is used in your presenters/controllers
    # - consoleLogger is used in symfony/console commands
    logger:
        httpLogger: Psr\Log\NullLogger
        # httpLogger: @specialLogger
        consoleLogger: Symfony\Component\Console\Logger\ConsoleLogger
        # consoleLogger: @specialLogger

    # Defines transport factories.
    # Built-in factories (sync, inMemory, amqp, redis) are auto-registered when their classes exist.
    # Doctrine factory is auto-registered when ConnectionRegistry is available in the container.
    transportFactory:
        # redis: Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory
        # sync: Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory
        # amqp: Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory
        # doctrine: Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory
        # inMemory: Symfony\Component\Messenger\Transport\InMemory\InMemoryTransportFactory
        # inMemory: @customMemoryTransportFactory

    # Defines global failure transport. Default is none.
    # - After retrying, messages will be sent to the "failed" transport.
    # - By default if no "failureTransport" is configured inside a transport global will be used.
    failureTransport: failed

    # Define transports (async or sync)
    transport:

        # Redis (async) transport
        redis:
            dsn: "redis://localhost?dbIndex=1"
            options: []
            serializer: default
            failureTransport: db

        # Doctrine (async) transport
        db:
            dsn: doctrine://postgres:password@localhost:5432
            # to disable retry
            retryStrategy: null

        # Sync transport
        sync:
            dsn: sync://

        # In memory (sync) transport
        memory:
            dsn: in-memory://
            serializer: @customSerializer

            # Since no failed transport is configured, the one used will be the global "failureTransport" set
            # failureTransport: db

            # Retry configuration.
            retryStrategy:
                maxRetries: 3
                # milliseconds delay
                delay: 1000
                # causes the delay to be higher before each retry
                # e.g. 1 second delay, 2 seconds, 4 seconds
                multiplier: 2
                maxDelay: 0
                # override all of this with a service that
                # implements Symfony\Component\Messenger\Retry\RetryStrategyInterface
                # service: @App\RetryStrategy\CustomRetryStrategy


        # Doctrine (async) transport
        failed:
            dsn: doctrine://postgres:password@localhost:5432?queue_name=failed

    # Defines routing (message -> transport)
    # If the routing for message is missing, the message will be handled by handler immediately when dispatched
    # Routing can also be defined via #[AsMessage] attribute on message classes (see below).
    # NEON config takes precedence over attributes.
    routing:
        App\Domain\NewUserEmail: [redis]
        App\Domain\ForgotPasswordEmail: [db, redis]
        App\Domain\LogText: [db]

        # Route interface
        App\Domain\SomeMessageInterface: [db]

        # Route wildcard
        *: [sync]

services:
    - App\Domain\LogTextHandler
    - App\Domain\NewUserEmailHandler
    - App\Domain\ForgotPasswordEmailHandler
    - App\RetryStrategy\CustomRetryStrategy
```

### Message

All messages are just simple [POJO](https://stackoverflow.com/questions/41188002/what-does-the-term-plain-old-php-object-popo-exactly-mean).

```php
<?php declare(strict_types = 1);

namespace App\Domain;

final class SimpleMessage
{

  public string $text;

  public function __construct(string $text)
  {
    $this->text = $text;
  }

}
```

#### Routing via `#[AsMessage]` attribute

Instead of configuring routing in NEON, you can use the `#[AsMessage]` attribute directly on your message class.
The attribute-based routing is auto-discovered from handler method parameters. NEON config takes precedence over attributes.

```php
<?php declare(strict_types = 1);

namespace App\Domain;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: 'async')]
final class NewUserEmail
{

  public function __construct(
    public readonly string $email,
  )
  {
  }

}
```

Multiple transports are also supported:

```php
#[AsMessage(transport: ['async', 'audit'])]
final class ImportantMessage
{
}
```

### Handlers

All handlers must be registered to your [DIC container](https://doc.nette.org/en/dependency-injection) via [Neon files](https://doc.nette.org/en/neon/format).<br>
All handlers must also be marked as message handlers to handle messages.
There are 2 different ways to mark your handlers:
1. with the neon tag [`contributte.messenger.handler`]:
```neon
services:
    -
        class: App\SimpleMessageHandler
        tags:
            contributte.messenger.handler: # the configuration below is optional
                bus: event
                alias: simple
                method: __invoke
                handles: App\SimpleMessage
                priority: 0
                from_transport: sync
```

2. with the attribute [`#[AsMessageHandler]`](https://github.com/symfony/messenger/blob/6e749550d539f787023878fad675b744411db003/Attribute/AsMessageHandler.php).
```php
<?php declare(strict_types = 1);

namespace App;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SimpleMessageHandler
{
  public function __invoke(SimpleMessage $message): void
  {
    // Do your magic
  }
}
```

It is also possible to handle multiple different kinds of messages in a single handler by defining 2 or more methods in it.
Both the neon tag [`contributte.messenger.handler`] and symfony attribute [`#[AsMessageHandler]`] support this kind of handler setup.
```neon
services:
    -
        class: App\MultipleMessagesHandler
        tags:
            contributte.messenger.handler:
                -
                    method: whenFooMessageReceived
                -
                    method: whenBarMessageReceived
```
```php
<?php declare(strict_types = 1);

namespace App;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class MultipleMessagesHandler
{
  #[AsMessageHandler]
  public function whenFooMessageReceived(FooMessage $message): void
  {
    // Do your magic
  }

  #[AsMessageHandler]
  public function whenBarMessageReceived(BarMessage $message): void
  {
    // Do your magic
  }
}
```

### Errors

Handling errors in async environments is little bit tricky. You need to setup logger to display errors in CLI environments.

## Integrations

### Doctrine

Take advantage of empowering this package with Doctrine packages:

- `doctrine/dbal` via [nettrine/dbal](https://github.com/contributte/doctrine-dbal)
- `doctrine/orm` via [nettrine/orm](https://github.com/contributte/doctrine-orm)
- `doctrine/annotations` via [nettrine/annotations](https://github.com/contributte/doctrine-annotations)
- `doctrine/cache` via [nettrine/cache](https://github.com/contributte/doctrine-cache)
- `doctrine/migrations` via [nettrine/migrations](https://github.com/contributte/doctrine-migrations)
- `doctrine/fixtures` via [nettrine/fixtures](https://github.com/contributte/doctrine-fixtures)

```sh
composer require nettrine/annotations nettrine/cache nettrine/migrations nettrine/fixtures nettrine/dbal nettrine/orm
```

```neon
# Extension > Nettrine
# => order is crucial
#
extensions:
  # Common
  nettrine.annotations: Nettrine\Annotations\DI\AnnotationsExtension
  nettrine.cache: Nettrine\Cache\DI\CacheExtension
  nettrine.migrations: Nettrine\Migrations\DI\MigrationsExtension
  nettrine.fixtures: Nettrine\Fixtures\DI\FixturesExtension

  # DBAL
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
  nettrine.dbal.console: Nettrine\DBAL\DI\DbalConsoleExtension

  # ORM
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.cache: Nettrine\ORM\DI\OrmCacheExtension
  nettrine.orm.console: Nettrine\ORM\DI\OrmConsoleExtension
  nettrine.orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension
```

## Limitations

**Roadmap**

- No Tracy debug panel integration (`TraceableMessageBus`).

## Examples

### 1. Manual example

```neon
# Extension > Messenger
#
extensions:
    messenger: Contributte\Messenger\DI\MessengerExtension

messenger:
    transport:
        sync:
            dsn: "sync://"

    routing:
        App\Domain\LogText: [sync]

services:
    - App\Domain\LogTextHandler
```

### 2. Example projects

We've made a few skeletons with preconfigured Symfony Messenger and Contributte packages.

- https://github.com/contributte/messenger-skeleton

### 3. Example playground

- https://contributte.org/examples.html (more examples)

## Features

- Multiple bus types (`messageBus`, `commandBus`, `queryBus`)
- Async transports (Redis, Doctrine, AMQP)
- Retry strategies with exponential backoff
- Failure transport for dead letters
- Handler auto-discovery via `#[AsMessageHandler]` attribute
- Message routing via `#[AsMessage]` attribute
- Interface and wildcard routing
- Worker limits (memory, time, message count)
- Batch handlers support

## Credits

This repository is inspired by these packages:

- [fmasa/messenger](https://github.com/fmasa/messenger)
- [symfony/messenger](https://github.com/symfony/messenger)
- [symfony/redis-messenger](https://github.com/symfony/redis-messenger)

Thank you folks.

## Development

See [how to contribute](https://contributte.org/contributing.html) to this package.

This package is currently maintained by these authors.

<a href="https://github.com/f3l1x">
  <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>

-----

Consider to [support](https://contributte.org/partners.html) **contributte** development team.
Also thank you for using this package.
