# AGENTS.md

Nette DI integration for Symfony Messenger. Provides message buses (command/query/message), async transports (AMQP, Redis, Doctrine), handler auto-discovery, retry strategies, and failure transport routing — all configured via Nette DI extension and compiled through ordered passes.

## Upstream

- Changelog: https://github.com/symfony/messenger/blob/8.1/CHANGELOG.md
- Source: https://github.com/symfony/messenger
- Doc: https://symfony.com/doc/current/messenger.html

## Stack

- PHP >=8.2, Symfony 7.x/8.x, Nette DI ^3.1
- PHPStan level 9 (phpVersion 80200)
- Nette Tester (`.phpt` files)
- Contributte code style (`ruleset.xml`)

## Codebase

- `src/DI/` — Main entry point. `MessengerExtension` delegates work to 10 passes in `Pass/`, each handling one concern (serializers, transports, routing, handlers, events, logging, console commands, buses, debug). Passes run in priority order (10->20->30->40) through three lifecycle hooks: load -> beforeCompile -> afterCompile.
- `src/Bus/` — MessageBus, CommandBus, QueryBus, BusRegistry
- `src/Container/` — PSR-11 wrappers for Nette DI
- `src/Handler/` — Handler locator
- `src/Logger/` — Dual HTTP/console logger
- `tests/Cases/` — Tests grouped by concern (DI/, Bus/, E2E/)
- `tests/Mocks/` — Test doubles
- `tests/Toolkit/` — Container builder, helpers

## Key conventions

- **Service tags**: `contributte.messenger.handler`, `contributte.messenger.transport`, `contributte.messenger.bus`, etc. (constants on `MessengerExtension`)
- **Service naming**: `messenger.bus.<name>`, `messenger.transport.<name>`
- **Handler discovery**: DI tag `contributte.messenger.handler` or PHP attribute `#[AsMessageHandler]`
- **Handler method**: defaults to `__invoke`, configurable via tag/attribute
- **Message type detection**: auto-detected from handler method's first parameter type-hint
- **Default middleware chain**: AddBusNameStamp -> DispatchAfterCurrentBus -> FailedMessageProcessing -> [custom] -> SendMessage -> HandleMessage

## Development

```bash
make install    # composer update
make tests      # run Nette Tester
make phpstan    # static analysis
make cs         # check code style
make csf        # fix code style
make qa         # phpstan + cs
```

## Tests

- Nette Tester with `.phpt` files, multiple test cases per file via `Toolkit::test(function (): void { ... })`
- Container built with `Container::of()->withDefaults()->withCompiler(fn ($compiler) => ...)->build()`
- NEON config snippets via `Helpers::neon(<<<'NEON' ... NEON)`
- Assertions: `Assert::type()`, `Assert::count()`, `Assert::equal()`, `Assert::exception()`
- DI tests (`tests/Cases/DI/`) verify service registration, tags, and config validation
- E2E tests (`tests/Cases/E2E/`) extend `TestCase`, test full dispatch->handle workflows
- File naming: `MessengerExtension.{feature}.phpt`
- Mocks are simple DTOs/handlers in `tests/Mocks/` with public properties for assertions
