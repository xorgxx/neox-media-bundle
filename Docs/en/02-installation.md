# 2. Installation

## Prerequisites

- PHP 8.2+ for Symfony 7;
- PHP 8.4+ for Symfony 8;
- Doctrine ORM 3;
- Twig;
- Symfony Form;
- Symfony Messenger.

## Symfony 8 Compatibility

The bundle is compatible with Symfony 7 and Symfony 8 without code modification.

- **Symfony 7**: PHP ≥ 8.2
- **Symfony 8**: PHP ≥ 8.4

External dependencies support Symfony 8:

- `vich/uploader-bundle` ≥ 2.10
- `liip/imagine-bundle` ≥ 2.17
- `doctrine/doctrine-bundle` ≥ 3.1

No deprecated API is used. See [`COMPATIBILITY.md`](../COMPATIBILITY.md) for full details.

## Local Installation

In the host project:

```bash
composer require xorgxx/neox-media-bundle
```

For a locally developed bundle, use a Composer `path` repository.

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../neox-media-bundle",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

Then:

```bash
composer require xorgxx/neox-media-bundle:@dev
```

## Routes

The bundle exposes its routes via `Resources/config/routes.yaml`. In the host project:

```yaml
# config/routes.yaml
neox_media:
    resource: '@NeoxMediaBundle/Resources/config/routes.yaml'
```

> **Note**: The bundle overrides `getPath()` to return the bundle root (not `src/`).
> Thus `@NeoxMediaBundle/Resources/config/routes.yaml` correctly resolves to `Resources/config/routes.yaml`.

## Twig Templates

The bundle uses the `@NeoxMedia` namespace in its templates. Since `Bundle::getName()`
returns `NeoxMediaBundle` (and is `final` in Symfony 7.4), the `@NeoxMedia` namespace is
not auto-registered. It must be added manually:

```yaml
# config/packages/twig.yaml
twig:
    paths:
        '%kernel.project_dir%/vendor/xorgxx/neox-media-bundle/templates': 'NeoxMedia'
```

## VichUploader

The bundle defines its `file` field with `mapping: 'neox_media'` (see [12-vich-uploader.md](12-vich-uploader.md)),
but **does not declare this mapping itself** — it must be configured in the host project:

```yaml
# config/packages/vich_uploader.yaml
vich_uploader:
    db_driver: orm
    mappings:
        neox_media:
            uri_prefix: /uploads/media
            upload_destination: '%kernel.project_dir%/public/uploads/media'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
```

Without this configuration, any read or write of a `Media` with source `FILE` throws
`Vich\UploaderBundle\Exception\MappingNotFoundException: Mapping "neox_media" does not exist.`

## Assets

The bundle provides files in `public/` (CSS) and `assets/controllers/` (Stimulus JS).

### 1. Public Assets (CSS)

```bash
php bin/console assets:install --symlink
```

The bundle overrides `getPath()` to return the bundle root, which allows
`assets:install` to find the `public/` directory and publish it under `public/bundles/neoxmedia/`.

### 2. Asset Mapper (Stimulus controllers)

For the Asset Mapper to discover the bundle's files, add the bundle's asset path
**with a dedicated namespace** (to avoid collisions with the host application's assets):

```yaml
# config/packages/asset_mapper.yaml
framework:
    asset_mapper:
        paths:
            - assets/
            - path: '%kernel.project_dir%/vendor/xorgxx/neox-media-bundle/assets'
              namespace: 'neox-media'
```

Verify that the files are detected:

```bash
php bin/console debug:asset-map | grep neox_media
```

> **Important**: this step makes the bundle's JS files *importable*, but does **not** register
> them as Stimulus controllers. Without the next step, `debug:asset-map` will list the files
> but the bundle's `data-controller="neox-media-*"` will remain inert.

### 3. Registering Stimulus Controllers

By default, `symfony/stimulus-bundle` only automatically scans the host application's
`assets/controllers/` directory (`controller_paths` option). The host application's
`assets/controllers.json` file only serves **real Composer packages prefixed with `@`**
that provide their own `assets/package.json` (convention of `symfony/ux-*` packages) —
NeoxMediaBundle is not one of them and should **not** be declared in this file.

The correct method is to extend `controller_paths` to include the bundle's controllers directory:

```yaml
# config/packages/stimulus.yaml
stimulus:
    controller_paths:
        - '%kernel.project_dir%/assets/controllers'
        - '%kernel.project_dir%/vendor/xorgxx/neox-media-bundle/assets/controllers'
```

Stimulus identifiers (`neox-media-library`, `neox-media-upload`, `neox-media-picker`,
`neox-media-sortable`) are automatically derived from the filename
(`neox_media_library_controller.js` → `neox-media-library`), with no additional
configuration or namespace required.

## Database

After installation:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

[← Presentation](01-presentation.md) · [Configuration →](03-configuration.md)
