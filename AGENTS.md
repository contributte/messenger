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

- Extension delegates to ordered passes via load -> beforeCompile -> afterCompile hooks, each handling one concern
- Pass priority: serializers/transports -> routing/handlers -> events/logging/console -> buses/debug
- Modify the responsible pass, not the extension
- Config under `messenger:` key — schema defined in extension class, update schema first, then wire into the pass
- Config values can be class names, `@service` references, or DI statements; routing/failure transports validated at compile time
- Tag names defined as extension constants; service names follow `messenger.bus.<name>.*`, `messenger.transport.<name>`, `messenger.serializer.<name>` — preserve these
- Handlers registered via DI tag or `#[AsMessageHandler]`, message type inferred from first parameter type-hint (`__invoke`)
- Union/intersection types rejected; handlers grouped per bus, sorted by priority
- Runtime handler matching: concrete class, parents, interfaces, namespace wildcards, `*`
- Default middleware order: bus name stamp -> dispatch after current bus -> failed message processing -> [custom] -> send -> handle — preserve this order
- Transport factories registered only when corresponding Symfony bridge class exists
- Retry defaults to multiplier; event pass wires retry/failure listeners, reuses existing event dispatcher

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
```

**Always run `make cs phpstan tests` and fix all errors.**

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
