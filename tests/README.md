# Running Tests

## PHPUnit Tests

Tests sind im `tests/` Verzeichnis organisiert:

```
tests/
├── bootstrap.php           # Test bootstrap
├── Unit/                   # Unit Tests
│   ├── Db/                # Database Tests
│   ├── Controller/        # Controller Tests
│   └── Util/             # Utility Tests
└── Integration/          # Integration Tests (optional)
```

## Lokal testen

```bash
# Alle Tests
npm run test

# Mit Coverage-Report
npm run test:coverage

# Nur spezifische Test-Suite
vendor/bin/phpunit tests/Unit/Db/

# Mit verbosem Output
vendor/bin/phpunit --testdox
```

## Erforderliche Dependencies

Automatisch in GitHub Actions installiert via `composer install`.

Lokal kannst du hinzufügen:
```bash
composer require --dev phpunit/phpunit ^9.5
composer require --dev phpunit/php-code-coverage
```

## Test-Abdeckung

Coverage Reports werden generiert und hochgeladen zu Codecov:
https://codecov.io/gh/lmaertin/nextcloud-digitalsignage

## Neue Tests schreiben

Struktur:
```php
namespace OCA\DigitalSignage\Tests\Unit\...;

class MyTest extends TestCase {
    public function testSomething(): void {
        $this->assertEquals($expected, $actual);
    }
}
```

Namespacing: `OCA\DigitalSignage\Tests\Unit\[Namespace]`
Dateiname: `[Class]Test.php`
