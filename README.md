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
<p>

<p align=center>
Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

Contributte Messenger integrates [Symfony Messenger](https://symfony.com/doc/current/messenger.html) into Nette
Framework. You configure buses, transports and routing in NEON, and every service with `#[AsMessageHandler]`
becomes a message handler without extra registration.

## Usage

To install the latest version of `contributte/messenger`, use [Composer](https://getcomposer.org):

```bash
composer require contributte/messenger
```

Requires PHP 8.2 or later, Nette 3.2 or later and Symfony Messenger 7.4 or later.

Register the extension in your `config.neon`, add a transport and route a message to it:

```neon
extensions:
	messenger: Contributte\Messenger\DI\MessengerExtension

messenger:
	transport:
		sync:
			dsn: sync://

	routing:
		App\Domain\SimpleMessage: [sync]

services:
	- App\Domain\SimpleMessageHandler
```

Write the handler. The type of the first parameter says which message it handles:

```php
namespace App\Domain;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SimpleMessageHandler
{

	public function __invoke(SimpleMessage $message): void
	{
		// handle the message
	}

}
```

Dispatch the message with the autowired `Symfony\Component\Messenger\MessageBusInterface`:

```php
$bus->dispatch(new SimpleMessage());
```

## Documentation

For details on how to use this package, check out the [documentation](.docs).

## Versions

| State  | Version | Branch   | Nette | PHP     |
|--------|---------|----------|-------|---------|
| dev    | `^0.3`  | `master` | 3.2+  | `>=8.2` |
| stable | `^0.2`  | `master` | 3.2+  | `>=8.2` |

## Development

See [how to contribute](https://contributte.org/contributing.html) to this package.

This package is currently maintained by these authors.

<a href="https://github.com/f3l1x">
  <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>

-----

Consider [supporting](https://contributte.org/partners.html) the **contributte** development team.
Thank you for using this package.
