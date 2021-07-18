# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chrisdicarl/phpunit-failed-runner.svg?style=flat-square)](https://packagist.org/packages/chrisdicarl/phpunit-failed-runner)
[![Total Downloads](https://img.shields.io/packagist/dt/chrisdicarl/phpunit-failed-runner.svg?style=flat-square)](https://packagist.org/packages/chrisdicarl/phpunit-failed-runner)
![GitHub Actions](https://github.com/chrisdicarl/phpunit-failed-runner/actions/workflows/main.yml/badge.svg)

Small package that allows you to run only your previously failed tests.

## Installation

You can install the package via composer:

```bash
composer require chrisdicarl/phpunit-failed-runner
```

## Configuration

This package requires Phpunit logging in textdox XML format.  Add the following to your phpunit.xml file:

```xml
<logging>
    <testdoxXml outputFile="testdox.xml"/>
</logging>
```

Optionally, add the logfile to your .gitignore:

```bash
echo testdox.xml >> .gitignore
```

## Usage

```bash
./vendor/bin/phpunit-failed-runner
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
