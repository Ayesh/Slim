# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [4.0.0] - 2026-11-22

This release targets modern PHP versions and contains backwards-incompatible
changes. Code that extends Slim classes or implements its interfaces will
likely need updating.

### Changed

- **BC break:** The minimum supported PHP version is now 8.5 (previously 8.1).
- **BC break:** Native parameter and return types have been added to public and
  protected methods throughout the codebase. Subclasses that override these
  methods must declare compatible signatures.
- **BC break:** `Slim\Interfaces\RouteInterface` and
  `Slim\Interfaces\RouteGroupInterface` now declare return types:
  - `getPattern(): string`
  - `RouteInterface::add($callable): RouteInterface`
  - `RouteGroupInterface::add($callable): RouteGroupInterface`
- `Slim\Routable::add()`, `Slim\Route::add()` and `Slim\RouteGroup::add()` now
  return `static`.
- `Slim\Http\NonBufferedBody` methods are now typed: `detach(): null`,
  `getSize(): ?int` and `getMetadata(): mixed`.
- `Slim\Http\Headers::getOriginalKey()` is typed to return `mixed`, because it
  returns the given `$default` when the header does not exist.
- Class constants, including all `Slim\Http\StatusCode` constants, now declare
  explicit `public` visibility.
- The default service factories registered by `Slim\DefaultServicesProvider`
  are now `static` closures.
- Internal code modernized to use PHP 8 features such as `str_starts_with()`,
  `get_debug_type()`, array destructuring and the short ternary operator.

### Fixed

- `Uri::createFromString()`, `Uri::withScheme()`, `Uri::withQuery()`,
  `Uri::withFragment()` and `Response::withStatus()` threw a `TypeError` instead
  of the documented `InvalidArgumentException` when passed a non-string,
  non-object value such as an array or `null`. Stringable objects are now
  detected with the `Stringable` interface.
- `Container::get()` did not wrap errors raised by the underlying
  `phpwatch/simple-container` (for example, a factory that references an
  undefined entry) in `Slim\Exception\ContainerException` as intended.
- Fixed a PHP deprecation notice raised when `Container::get()` created a
  `ContainerException`: `null` was passed as the exception code.
- Replaced the deprecated `is_integer()` call with `is_int()` (deprecated in
  PHP 8.6).

### Development

- Upgraded the test suite from PHPUnit 9 to PHPUnit 13.4.
  - Migrated `phpunit.xml.dist` to the PHPUnit 13 schema. Tests now fail on
    any deprecation, notice, warning or risky test.
  - Replaced docblock annotations with PHPUnit attributes, removed uses of
    APIs that PHPUnit has dropped, and replaced Prophecy with PHPUnit test
    doubles.
  - Removed tests that could only run on PHP 5 or HHVM.
- `ext-mbstring` is now required for development (it is required by PHPUnit).
- CI now runs on PHP 8.5 and 8.6 and uses the latest versions of
  `actions/checkout` (v7) and `ramsey/composer-install` (v4).

## [3.12.10]

See the [commit history](https://github.com/Ayesh/Slim/commits/3.12.10) for earlier changes.

[4.0.0]: https://github.com/Ayesh/Slim/compare/3.12.10...4.0.0
[3.12.10]: https://github.com/Ayesh/Slim/tree/3.12.10
