# Contributte Messenger

## Content

- [Setup](#usage)
- [Relying](#relying)
- [Configuration](#configuration)
- [Integrations](#integrations)
- [Limitations](#limitations)
- [Examples](#examples)

## Setup

```bash
composer require contributte/messenger
```

```neon
extensions:
  messenger: Contributte\Messenger\DI\MessengerExtension
```

## Relying

Take advantage of enpowering this package with 4 extra packages:

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

Since this moment when you type `bin/console`, there'll be registered commands from Doctrine DBAL.

![Console Commands](https://raw.githubusercontent.com/contributte/messenger/master/.docs/assets/console.png)

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

> At first please take a look at official documentation.
> https://symfony.com/doc/current/components/messenger.html
> https://symfony.com/doc/current/messenger.html

Minimal configuration example:

```neon
messenger:
    transport:
        sync:
            dsn: "sync://"

    routing:
        App\Domain\SimpleMessage: [sync]

services:
    - App\Domain\SimpleMessageHandler
```

Full configuration example:

```neon
messenger:
    debug:
        panel: %debugMode%

    bus:
        messageBus:
            middlewares:
                #- LoggerMiddleware()
                #- @loggerMiddleware
            autowired: true
            allowNoHandlers: false
            allowNoSenders: true
            class: App\Model\Bus\MyMessageBus

        queryBus:
            autowired: false

    serializer:
        default: Symfony\Component\Messenger\Transport\Serialization\PhpSerializer
        # custom: @customSerializer

    logger:
        httpLogger: Psr\Log\NullLogger
        # httpLogger: @specialLogger
        consoleLogger: Symfony\Component\Console\Logger\ConsoleLogger
        # consoleLogger: @specialLogger

    transportFactory:
        # redis: Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory
        # sync: Symfony\Component\Messenger\Transport\Sync\SyncTransportFactorya
        # amqp: Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory
        # doctrine: Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory
        # inMemory: Symfony\Component\Messenger\Transport\InMemoryTransportFactory
        # inMemory: @customMemoryTransportFactory

    transport:
        redis:
            dsn: "redis://localhost?dbIndex=1"
            options: []
            serializer: default
            failureTransport: db

        memory:
            dsn: in-memory://
            serializer: @customSerializer

        db:
            dsn: doctrine://postgres:password@localhost:5432

    routing:
        App\Domain\NewUserEmail: [redis]
        App\Domain\ForgotPasswordEmail: [db, redis]
        App\Domain\LogText: [db]

services:
    - App\Domain\LogTextHandler
    - App\Domain\NewUserEmailHandler
    - App\Domain\ForgotPasswordEmailHandler
```

### Message

All messages are just
simple [POJO](https://stackoverflow.com/questions/41188002/what-does-the-term-plain-old-php-object-popo-exactly-mean.

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

### Handlers

All handlers must be registered to your [DIC container](https://doc.nette.org/en/dependency-injection)
via [Neon files](https://doc.nette.org/en/neon/format). All handlers must have `#[AsMessageHandler]` attribute.

```neon
services:
    - App\Domain\SimpleMessageHandler
```

```php
<?php declare(strict_types = 1);

namespace App\Domain;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tests\Mocks\Message\SimpleMessage;

#[AsMessageHandler]
final class SimpleMessageHandler
{

  public function __invoke(SimpleMessage $message): void
  {
    // Do your magic
  }

}
```

### Errors

Handling errors in async environments is little bit tricky. You need to setup logger to display errors in CLI environments.



## Integrations

### Doctrine

Take advantage of enpowering this package with 2 extra packages:

- `doctrine/dbal` via [nettrine/dbal](https://github.com/contributte/doctrine-dbal)
- `doctrine/orm` via [nettrine/orm](https://github.com/contributte/doctrine-orm)

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
- No PCNTL listeners registered.
- No retry_strategy overrides (max_retries, delay, multiply, max_delay).
- No failing queue settings.
- No fallbackBus in RoutableMessageBus.
- No proper console commands.

**No ETA**
- MessageHandler can handle only 1 message.
- MessageHandler can have only `__invoke` method.


## Examples

### 1. Manual example

```sh
composer require contributte/messenger
```

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

We've made a few skeletons with preconfigured Symfony Messenger nad Contributte packages.

- https://github.com/contributte/messenger-skeleton

### 3. Example playground

- https://contributte.org/examples.html (more examples)

## Other

This repository is inspired by these packages.

- https://github.com/fmasa/messenger
- https://gitlab.com/symfony/messenger
- https://gitlab.com/symfony/redis-messenger

Thank you folks.
