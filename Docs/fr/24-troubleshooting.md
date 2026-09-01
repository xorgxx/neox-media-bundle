# 24. Dépannage

## Le fichier n'est pas déplacé

Vérifier :

- configuration Vich ;
- mapping `neox_media` ;
- permissions du répertoire ;
- `setFile()` ;
- modification de `updatedAt`.

## Les images LiipImagine ne s'affichent pas

Vérifier :

- installation de LiipImagineBundle ;
- filtres configurés ;
- route/cache resolver ;
- URL source accessible.

## Le Media Manager est sans style

Vérifier l'installation/exposition de :

```text
public/media-manager.css
```

et des contrôleurs Stimulus.

## L'upload fonctionne mais le traitement ne démarre pas

Vérifier Messenger :

```bash
php bin/console debug:messenger
```

Si le transport est asynchrone :

```bash
php bin/console messenger:consume media_async -vv
```

## Le champ Product affiche un select classique

Vérifier :

- utilisation de `MediaPickerType` ;
- thème de formulaire du bundle ;
- contrôleur Stimulus `neox-media-picker` ;
- intégration du bridge dans le CRUD.

## "No route found for GET /admin/media"

Les routes du bundle ne sont pas chargées. Vérifier :

1. Que `config/routes.yaml` du projet hôte importe `@NeoxMediaBundle/Resources/config/routes.yaml`.
2. Que le fichier `Resources/config/routes.yaml` existe à la racine du bundle (pas dans `src/Resources/`).
3. Que le bundle override `getPath()` pour retourner la racine du bundle (`dirname(__DIR__)`).

## "There is no extension able to load the configuration for neox_media_controllers"

Ce bug se produit lorsque `routes.yaml` est chargé via le `YamlFileLoader` du container DI
au lieu du routeur Symfony. Les routes doivent être chargées via l'import dans `config/routes.yaml`
du projet hôte, **pas** via `NeoxMediaExtension::load()`.

## "There are no registered paths for namespace NeoxMedia"

Le namespace Twig `@NeoxMedia` n'est pas auto-enregistré car `Bundle::getName()` retourne
`NeoxMediaBundle` (et est `final` en Symfony 7.4). Ajouter manuellement le path dans
`config/packages/twig.yaml` :

```yaml
twig:
    paths:
        '%kernel.project_dir%/vendor/xorgxx/neox-media-bundle/templates': 'NeoxMedia'
```

## Les boutons du Media Manager ne fonctionnent pas

Les contrôleurs Stimulus ne sont pas chargés. Vérifier :

1. Que l'Asset Mapper inclut le chemin des assets du bundle (avec namespace `neox-media`) dans `config/packages/asset_mapper.yaml`.
2. Que `php bin/console debug:asset-map` liste les fichiers `neox_media_*`.
3. **Cause la plus fréquente** : le dossier `assets/controllers/` du bundle n'est pas ajouté à
   `controller_paths` dans `config/packages/stimulus.yaml` de l'application hôte. Sans cela,
   `debug:asset-map` liste les fichiers comme importables, mais Stimulus ne les instancie jamais
   (voir [Installation §3](02-installation.md#3-enregistrement-des-contrôleurs-stimulus)).

> **Ne pas** déclarer ces contrôleurs dans `assets/controllers.json` : ce fichier est réservé aux
> paquets Composer réels préfixés par `@` (convention `symfony/ux-*`). Une clé arbitraire comme
> `"app"` provoque l'erreur `Could not find package "pp" referred to from controllers.json.`
> (le code retire le premier caractère en s'attendant à un `@`).

## Le CSS du Media Manager est en 404

`assets:install` ne publie pas les assets du bundle. Vérifier :

1. Que le bundle override `getPath()` pour retourner la racine du bundle.
2. Que le dossier `public/` existe à la racine du bundle.
3. Exécuter `php bin/console assets:install --symlink`.

## "Cannot autowire service UploaderHelperInterface"

VichUploaderBundle n'alias pas automatiquement `UploaderHelperInterface`. Le bundle
déclare l'alias dans `config/services.yaml` :

```yaml
Vich\UploaderBundle\Templating\Helper\UploaderHelperInterface: '@Vich\UploaderBundle\Templating\Helper\UploaderHelper'
```

## Symfony 8 refuse l'installation

Symfony 8 nécessite PHP 8.4 ou supérieur.

---

[← API](23-api-reference.md) · [Retour au sommaire](README.md)
