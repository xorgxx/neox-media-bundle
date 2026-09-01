# 24. Troubleshooting

## File is not moved

Check:

- Vich configuration;
- `neox_media` mapping;
- directory permissions;
- `setFile()`;
- `updatedAt` modification.

## LiipImagine images don't display

Check:

- LiipImagineBundle installation;
- configured filters;
- route/cache resolver;
- source URL accessible.

## Media Manager has no styles

Check the installation/exposure of:

```text
public/media-manager.css
```

and the Stimulus controllers.

## Upload works but processing doesn't start

Check Messenger:

```bash
php bin/console debug:messenger
```

If the transport is asynchronous:

```bash
php bin/console messenger:consume media_async -vv
```

## Product field displays a classic select

Check:

- usage of `MediaPickerType`;
- bundle form theme;
- `neox-media-picker` Stimulus controller;
- CRUD bridge integration.

## "No route found for GET /admin/media"

Bundle routes are not loaded. Check:

1. That the host project's `config/routes.yaml` imports `@NeoxMediaBundle/Resources/config/routes.yaml`.
2. That the `Resources/config/routes.yaml` file exists at the bundle root (not in `src/Resources/`).
3. That the bundle overrides `getPath()` to return the bundle root (`dirname(__DIR__)`).

## "There is no extension able to load the configuration for neox_media_controllers"

This bug occurs when `routes.yaml` is loaded via the DI container's `YamlFileLoader`
instead of the Symfony router. Routes must be loaded via the import in the host project's
`config/routes.yaml`, **not** via `NeoxMediaExtension::load()`.

## "There are no registered paths for namespace NeoxMedia"

The Twig namespace `@NeoxMedia` is not auto-registered because `Bundle::getName()` returns
`NeoxMediaBundle` (and is `final` in Symfony 7.4). Add the path manually in
`config/packages/twig.yaml`:

```yaml
twig:
    paths:
        '%kernel.project_dir%/vendor/xorgxx/neox-media-bundle/templates': 'NeoxMedia'
```

## Media Manager buttons don't work

Stimulus controllers are not loaded. Check:

1. That the Asset Mapper includes the bundle's asset path (with `neox-media` namespace) in `config/packages/asset_mapper.yaml`.
2. That `php bin/console debug:asset-map` lists `neox_media_*` files.
3. **Most common cause**: the bundle's `assets/controllers/` directory is not added to
   `controller_paths` in the host application's `config/packages/stimulus.yaml`. Without this,
   `debug:asset-map` lists files as importable, but Stimulus never instantiates them
   (see [Installation §3](02-installation.md#3-registering-stimulus-controllers)).

> **Do not** declare these controllers in `assets/controllers.json`: this file is reserved for
> real Composer packages prefixed with `@` (convention `symfony/ux-*`). An arbitrary key like
> `"app"` causes the error `Could not find package "pp" referred to from controllers.json.`
> (the code strips the first character expecting a `@`).

## Media Manager CSS is 404

`assets:install` doesn't publish the bundle's assets. Check:

1. That the bundle overrides `getPath()` to return the bundle root.
2. That the `public/` directory exists at the bundle root.
3. Run `php bin/console assets:install --symlink`.

## "Cannot autowire service UploaderHelperInterface"

VichUploaderBundle doesn't automatically alias `UploaderHelperInterface`. The bundle
declares the alias in `config/services.yaml`:

```yaml
Vich\UploaderBundle\Templating\Helper\UploaderHelperInterface: '@Vich\UploaderBundle\Templating\Helper\UploaderHelper'
```

## Symfony 8 refuses installation

Symfony 8 requires PHP 8.4 or higher.

---

[← API](23-api-reference.md) · [Back to index](README.md)
