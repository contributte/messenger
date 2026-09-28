# Contributte Messenger

Instructions for AI coding agents working in this repository.

## Overview

`contributte/messenger` integrates [Symfony Messenger](https://symfony.com/doc/current/messenger.html) into Nette
Framework. One DI extension registers message buses, transports, serializers, retry strategies, failure transports,
handlers and console commands from the `messenger:` section. It is a library with one DI extension, not an
application.

- **PHP**: 8.2 to 8.5 (`>=8.2` in `composer.json`)
- **Package**: `contributte/messenger`, namespace `Contributte\Messenger\`
- **Extension**: `Contributte\Messenger\DI\MessengerExtension`
- **Integrates**: `symfony/messenger`, `symfony/console` and `symfony/event-dispatcher` 7.4+ and 8.x, `nette/di` 3.1+
- **Optional**: Redis, AMQP and Doctrine transports, `tracy/tracy` (only in `require-dev`)

## Documentation

- `.docs/README.md` is the user documentation and the page on contributte.org. Update it in the same pull request
  when configuration or behaviour changes.
- Upstream behaviour is described in the [Symfony Messenger docs](https://symfony.com/doc/current/messenger.html).
- Organization rules for code, tests and tooling are in
  [contributte/contributte specs](https://github.com/contributte/contributte/tree/master/specs).

## Commands

```bash
# Install dependencies
make install

# Run all checks (PHPStan level 9 + code style), does not run tests
make qa

# Fix code style
make csf

# Run all tests, or one file
make tests
vendor/bin/tester -s -p php --colors 1 -C tests/Cases/DI/MessengerExtension.handler.phpt

# Generate code coverage (coverage.html)
make coverage
```

CI runs the tests on PHP 8.2 to 8.5 and once on PHP 8.2 with `--prefer-lowest`.

## Conventions

- DI tests are split by feature: `MessengerExtension.{feature}.phpt` in `tests/Cases/DI`. Containers are built with
  `Tests\Toolkit\Container::of()->withDefaults()->withCompiler(...)->build()` and inline NEON from
  `Helpers::neon()`.
- E2E tests in `tests/Cases/E2E` dispatch and handle real messages; `Vendor/MessengerTest.php` and
  `Scenarios/UsecasesTest.php` are `TestCase` classes. Messages, handlers and middlewares for tests are plain
  classes in `tests/Mocks`.
- Exception messages are asserted in tests. Changing a message means changing its test.

## Traps

- **`MessengerExtension` runs its passes in a fixed order.** Serializer, transport factory and transport passes
  run first, then routing and handlers, then events, logger and console, then buses and debug. Change the pass
  that owns a concern in `src/DI/Pass/`, not the extension.
- **The config schema lives in `MessengerExtension::getConfigSchema()`.** Add an option there first, then read it
  in the pass. Routing and failure transports are validated at compile time.
- **Service and tag names are public API.** `messenger.bus.{name}.bus`, `messenger.transport.{name}`,
  `messenger.serializer.{name}` and the `contributte.messenger.*` tag constants on the extension must not change.
- **The default middleware order is fixed:** bus name stamp, dispatch after current bus, failed message
  processing, custom middlewares, send, handle. `BusPass` builds it; keep that order.
- **A handler without a `bus` option is attached to every bus.** Handlers come from the
  `contributte.messenger.handler` tag or `#[AsMessageHandler]`; the message type is the first parameter type, and
  union or intersection types throw `LogicalException`.
- **Transport factories are registered only when their Symfony bridge class exists.** Doctrine also needs a
  `ConnectionRegistry` service. Keep the bridges in `require-dev`.
- **An existing `EventDispatcherInterface` service is reused.** Otherwise the extension creates its own
  dispatcher; both are registered as `messenger.event.dispatcher` with autowiring off.
- **`console.stopWorkersCommand` exists only when `cache` is set.** Without a PSR-6 pool there is no restart
  signal.
- **Retry defaults to `MultiplierRetryStrategy`** with 3 retries and a 1000 ms delay, set in the schema defaults.
- Usage, configuration and examples for users live in `.docs/README.md`, not here.
