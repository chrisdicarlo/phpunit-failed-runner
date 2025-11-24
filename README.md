# Run Only Your Failed Tests

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chrisdicarlo/phpunit-failed-runner.svg?style=flat-square)](https://packagist.org/packages/chrisdicarlo/phpunit-failed-runner)
[![Total Downloads](https://img.shields.io/packagist/dt/chrisdicarlo/phpunit-failed-runner.svg?style=flat-square)](https://packagist.org/packages/chrisdicarlo/phpunit-failed-runner)

Small package that allows you to incrementally run only your previously failed tests.

## Installation

You can install the package via composer:

```bash
composer require chrisdicarlo/phpunit-failed-runner --dev
```

### Install XmlStarlet

On Linux:

```bash
sudo apt-get update
sudo apt-get install xmlstarlet
```

For XmlStarlet on other platforms, see [here](http://xmlstar.sourceforge.net/doc/UG/xmlstarlet-ug.html#idm47077139681232).

## Usage

```bash
./vendor/bin/phpunit-failed-runner
```

## Testing

This package includes a comprehensive test suite that demonstrates the incremental test-fixing workflow.

Run the test suite:

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email chris@dicarlosystems.ca instead of using the issue tracker.

## Credits

-   [Chris Di Carlo](https://github.com/chrisdicarl)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
