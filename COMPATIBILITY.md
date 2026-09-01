# Compatibility

NeoxMediaBundle is designed to support:

- Symfony 7.x (PHP >= 8.2)
- Symfony 8.x (PHP >= 8.4)

## Requirements

| Dependency | Constraint | Symfony 7 | Symfony 8 |
|---|---|---|---|
| `symfony/framework-bundle` | `^7.0 \|\| ^8.0` | ✅ | ✅ |
| `symfony/twig-bundle` | `^7.0 \|\| ^8.0` | ✅ | ✅ |
| `symfony/form` | `^7.0 \|\| ^8.0` | ✅ | ✅ |
| `symfony/validator` | `^7.0 \|\| ^8.0` | ✅ | ✅ |
| `symfony/messenger` | `^7.0 \|\| ^8.0` | ✅ | ✅ |
| `doctrine/orm` | `^3.0` | ✅ | ✅ |
| `doctrine/doctrine-bundle` | `^2.12 \|\| ^3.0` | ✅ | ✅ (3.1+) |
| `vich/uploader-bundle` | `^2.9` | ✅ | ✅ (2.10+) |
| `liip/imagine-bundle` | `^2.16` | ✅ | ✅ (2.17+) |

## CI Matrix

The bundle is tested against:

- PHP 8.2 + Symfony ^7.0
- PHP 8.3 + Symfony ^7.4
- PHP 8.4 + Symfony ^7.4
- PHP 8.4 + Symfony ^8.0
- PHP 8.5 + Symfony ^8.0

## Code-level compatibility

The bundle source does not branch on the Symfony major version.

- No use of `Request::get()` (removed in Symfony 8) — all controllers use `$request->query->get()`, `$request->files->get()`, `$request->headers->get()`.
- No deprecated APIs: uses PHP 8 attributes (`#[Route]`, `#[IsGranted]`, `#[AsMessageHandler]`, `#[AutoconfigureTag]`).
- `NeoxMediaBundle` does not override `registerCommands()` (deprecated in Symfony 8.1).
- Twig templates use only standard functions (`path()`, `asset()`, `csrf_token()`, `include`, `extends`).

## Optional dependencies

| Dependency | Purpose | Symfony 8 compatible |
|---|---|---|
| `symfony/asset-mapper` | Expose Stimulus controllers + CSS | ✅ |
| `symfony/mercure-bundle` | Live processing updates | ✅ |
| `php-ffmpeg/php-ffmpeg` | Video/audio duration detection | ✅ (independent of Symfony) |
