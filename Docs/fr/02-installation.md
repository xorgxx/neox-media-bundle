# 2. Installation

## Prérequis

- PHP 8.2+ pour Symfony 7 ;
- PHP 8.4+ pour Symfony 8 ;
- Doctrine ORM 3 ;
- Twig ;
- Symfony Form ;
- Symfony Messenger.

## Compatibilité Symfony 8

Le bundle est compatible Symfony 7 et Symfony 8 sans modification du code.

- **Symfony 7** : PHP ≥ 8.2
- **Symfony 8** : PHP ≥ 8.4

Les dépendances externes supportent Symfony 8 :

- `vich/uploader-bundle` ≥ 2.10
- `liip/imagine-bundle` ≥ 2.17
- `doctrine/doctrine-bundle` ≥ 3.1

Aucune API dépréciée n'est utilisée. Voir [`COMPATIBILITY.md`](../COMPATIBILITY.md) pour le détail complet.

## Installation locale

Dans le projet hôte :

```bash
composer require xorgxx/neox-media-bundle
```

Pour un bundle développé localement, utiliser un repository Composer `path`.

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

Puis :

```bash
composer require xorgxx/neox-media-bundle:@dev
```

## Routes

Le bundle expose ses routes via `Resources/config/routes.yaml`. Dans le projet hôte :

```yaml
# config/routes.yaml
neox_media:
    resource: '@NeoxMediaBundle/Resources/config/routes.yaml'
```

> **Note** : Le bundle override `getPath()` pour retourner la racine du bundle (et non `src/`).
> Ainsi `@NeoxMediaBundle/Resources/config/routes.yaml` résout correctement vers `Resources/config/routes.yaml`.

## Templates Twig

Le bundle utilise le namespace `@NeoxMedia` dans ses templates. Comme `Bundle::getName()`
retourne `NeoxMediaBundle` (et est `final` en Symfony 7.4), le namespace `@NeoxMedia` n'est
pas auto-enregistré. Il faut l'ajouter manuellement :

```yaml
# config/packages/twig.yaml
twig:
    paths:
        '%kernel.project_dir%/vendor/xorgxx/neox-media-bundle/templates': 'NeoxMedia'
```

## VichUploader

Le bundle définit son champ `file` avec `mapping: 'neox_media'` (voir [12-vich-uploader.md](12-vich-uploader.md)),
mais **ne déclare pas ce mapping lui-même** — il doit être configuré dans le projet hôte :

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

Sans cette configuration, toute lecture ou écriture d'un `Media` de source `FILE` lève
`Vich\UploaderBundle\Exception\MappingNotFoundException: Mapping "neox_media" does not exist.`

## Assets

Le bundle fournit des fichiers dans `public/` (CSS) et `assets/controllers/` (Stimulus JS).

### 1. Assets publics (CSS)

```bash
php bin/console assets:install --symlink
```

Le bundle override `getPath()` pour retourner la racine du bundle, ce qui permet à
`assets:install` de trouver le dossier `public/` et de le publier sous `public/bundles/neoxmedia/`.

### 2. Asset Mapper (Stimulus controllers)

Pour que l'Asset Mapper découvre les fichiers du bundle, ajouter le chemin des assets
du bundle **avec un namespace dédié** (pour éviter toute collision avec les assets de
l'application hôte) :

```yaml
# config/packages/asset_mapper.yaml
framework:
    asset_mapper:
        paths:
            - assets/
            - path: '%kernel.project_dir%/vendor/xorgxx/neox-media-bundle/assets'
              namespace: 'neox-media'
```

Vérifier que les fichiers sont bien détectés :

```bash
php bin/console debug:asset-map | grep neox_media
```

> **Important** : cette étape rend les fichiers JS du bundle *importables*, mais ne les
> enregistre **pas** comme contrôleurs Stimulus. Sans l'étape suivante, `debug:asset-map`
> listera les fichiers mais les `data-controller="neox-media-*"` du bundle resteront inertes.

### 3. Enregistrement des contrôleurs Stimulus

Par défaut, `symfony/stimulus-bundle` ne scanne automatiquement que `assets/controllers/`
de l'application hôte (option `controller_paths`). Le fichier `assets/controllers.json` de
l'application hôte, lui, ne sert **qu'aux paquets Composer réels préfixés par `@`** qui
fournissent leur propre `assets/package.json` (convention des paquets `symfony/ux-*`) —
NeoxMediaBundle n'en fait pas partie et ne doit **pas** être déclaré dans ce fichier.

La bonne méthode consiste à étendre `controller_paths` pour inclure le dossier des
contrôleurs du bundle :

```yaml
# config/packages/stimulus.yaml
stimulus:
    controller_paths:
        - '%kernel.project_dir%/assets/controllers'
        - '%kernel.project_dir%/vendor/xorgxx/neox-media-bundle/assets/controllers'
```

Les identifiants Stimulus (`neox-media-library`, `neox-media-upload`, `neox-media-picker`,
`neox-media-sortable`) sont déduits automatiquement du nom de fichier
(`neox_media_library_controller.js` → `neox-media-library`), sans configuration
supplémentaire ni namespace.

## Base de données

Après installation :

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

[← Présentation](01-presentation.md) · [Configuration →](03-configuration.md)
