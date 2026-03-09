# AGENTS.md

Nette DI integration for Symfony Messenger. Library and DI extension, not an application. Provides message buses, async transports, handler auto-discovery, retry strategies, and failure transport routing — all configured via Nette DI extension and compiled through ordered passes.

## Stack

- PHP >=8.2, Symfony 7.x/8.x, Nette DI ^3.1
- PHPStan level 9 (phpVersion 80200)
- Nette Tester (`.phpt` files)
- Contributte code style (`ruleset.xml`)

## Codebase

- `src/DI/` — main entry point; extension delegates to ordered passes in `Pass/`, each handling one concern (serializers, transports, routing, handlers, events, logging, console, buses, debug)
- `src/Bus/` — bus wrappers (message, command, query) and registry
- `src/Container/` — PSR-11 adapters for Nette DI
- `src/Handler/` — runtime handler locator with wildcard/interface/parent matching
- `src/Logger/` — dual HTTP/console logger bridge
- `tests/Cases/` — tests grouped by concern (DI/, Bus/, E2E/)
- `tests/Mocks/` — simple DTOs and handlers used as test doubles
- `tests/Toolkit/` — container builder and test helpers

## Architecture

### Compilation lifecycle

The extension delegates work to passes through 3 lifecycle hooks: load -> beforeCompile -> afterCompile. Each pass handles exactly one concern. Pass order matters — later passes depend on services registered by earlier ones.

Priority order: serializers and transports first, then routing and handlers, then events/logging/console, and finally buses and debug.

When changing behavior, modify the responsible pass rather than the extension itself.

### Configuration

Config lives under `messenger:` key with sub-keys for debug, buses, serializers, transport factories, failure transports, transports, routing, and logging. The schema is defined in the extension class. When adding or changing config, update the schema first, then wire the option into the appropriate pass.

Config values may be class names, `@service` references, or DI statements depending on the key. Routing and failure transports are validated against defined transport names at compile time.

### Service naming and tags

Extension constants define all tag names (transport factory, transport, failure transport, bus, handler, retry strategy). Service names follow patterns like `messenger.bus.<name>.*`, `messenger.transport.<name>`, `messenger.serializer.<name>`. Preserve these conventions — tests and utilities depend on them.

### Handler discovery

Handlers are registered via DI tag or `#[AsMessageHandler]` attribute. The handled message type is inferred from the handler method's first parameter type-hint (default method `__invoke`). Union/intersection types are rejected. Handlers are grouped per bus and sorted by priority.

At runtime, the handler locator matches messages by concrete class, parent classes, implemented interfaces, namespace wildcards, and catch-all `*`.

### Buses and middleware

Each bus gets a handler locator, an optional default middleware stack, custom middlewares, and optionally a typed wrapper (message/command/query bus). Default middleware order: bus name stamp -> dispatch after current bus -> failed message processing -> [custom] -> send message -> handle message. Preserve this ordering.

### Transports and events

Built-in transport factories are registered only when the corresponding Symfony bridge class exists. Retry strategies default to multiplier unless a custom service is configured. The event pass wires retry and failure listeners. If an event dispatcher already exists in the container, it is reused.

## Code Style

- `<?php declare(strict_types = 1);` on one line in every file
- Indentation with tabs
- Contributte/Nette formatting enforced via `contributte/qa` ruleset (Slevomat Coding Standard)
- Type name must match file name
- Root namespaces: `Contributte\Messenger` for `src/`, `Tests` for `tests/`
- One `use` per line, alphabetically ordered, no unused imports
- No superfluous `Interface` suffix in production code (allowed in tests)
- Exception messages must be explicit — tests assert on them
- Avoid comments unless the logic is genuinely non-obvious
- Prefer small, focused changes inside the responsible pass or utility
- Run `make csf` to auto-fix before committing

## Testing

Nette Tester, not PHPUnit.

```bash
make tests         # run all tests
make phpstan       # static analysis
make csf           # fix code style
make qa            # phpstan + cs

# Single test file
vendor/bin/tester -s -p php --colors 1 -C tests/Cases/DI/MessengerExtension.handler.phpt
```

### Conventions

- `.phpt` files with multiple test cases per file
- Containers built via toolkit: `Container::of()->withDefaults()->withCompiler(...)->build()`
- Inline NEON config via `Helpers::neon(<<<'NEON' ... NEON)`
- Assertions: `Assert::type()`, `Assert::count()`, `Assert::equal()`, `Assert::exception()`
- DI tests verify service registration, tags, and config validation errors
- E2E tests extend `TestCase` for full dispatch->handle workflows
- File naming: `MessengerExtension.{feature}.phpt`
- Mocks are simple DTOs/handlers with public properties for assertions
- Compiled containers written to `tests/tmp` for debugging

## Upstream

- Docs: https://symfony.com/doc/current/messenger.html
- Source: https://github.com/symfony/messenger
- Changelog: https://github.com/symfony/messenger/blob/8.1/CHANGELOG.md
