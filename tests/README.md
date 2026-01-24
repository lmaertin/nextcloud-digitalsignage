# Running Tests

## PHPUnit Tests

Tests are organized under the `tests/` directory:

```
tests/
├── bootstrap.php           # Test bootstrap
├── Unit/                   # Unit tests
│   ├── Db/                 # Database tests
│   ├── Controller/         # Controller tests
│   └── Util/               # Utility tests
└── Integration/            # Integration tests (optional)
```

## Running locally

```bash
# All tests (via npm scripts)
npm run test

# With coverage report (HTML)
npm run test:coverage

# Specific test suite
vendor/bin/phpunit tests/Unit/Db/

# Verbose output
vendor/bin/phpunit --testdox
```

## Required dependencies

Installed automatically in GitHub Actions via `composer install`.

Install locally:
```bash
composer require --dev phpunit/phpunit ^9.5
composer require --dev phpunit/php-code-coverage
```

## Coverage

Coverage reports are generated and uploaded to Codecov:
https://codecov.io/gh/lmaertin/nextcloud-digitalsignage

## Writing new tests

Structure:
```php
namespace OCA\DigitalSignage\Tests\Unit\...;

class MyTest extends TestCase {
    public function testSomething(): void {
        $this->assertEquals($expected, $actual);
    }
}
```

Namespacing: `OCA\DigitalSignage\Tests\Unit\[Namespace]`
Filename: `[Class]Test.php`
