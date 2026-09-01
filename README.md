# NeoxMediaBundle

📚 [Documentation complète](Docs/README.md) — [Français](Docs/fr/README.md) | [English](Docs/en/README.md)

## Compatibilité

| Runtime | Version |
|---|---|
| PHP | >= 8.2 |
| Symfony 7 | PHP >= 8.2 selon la version Symfony utilisée |
| Symfony 8 | PHP >= 8.4 obligatoire |
| Doctrine ORM | ^3.0 |
| VichUploaderBundle | ^2.9 |
| LiipImagineBundle | ^2.16 |

Le même package couvre Symfony 7 et Symfony 8. Composer résout automatiquement
la branche Symfony compatible avec la version de PHP du projet.

Pour tester Symfony 8 :

```bash
composer require symfony/framework-bundle:^8.0
```

Symfony 8 nécessite PHP 8.4 ou supérieur.


Base réutilisable pour Symfony 7 et Symfony 8.

## Objectif

L'application métier ne stocke ni chemin, ni filename, ni URL technique.
Elle référence uniquement `Neox\MediaBundle\Entity\Media`.

Sources supportées par le modèle :
- FILE
- URL
- EMBED
- REMOTE

Le bundle centralise :
- VichUploader pour les fichiers uploadés
- LiipImagine pour les variantes d'images
- MediaPickerType pour sélectionner un média
- attribut `#[MediaField]` pour décrire l'usage métier
- Twig `neox_media_url()` / `neox_media_render()`
- Messenger pour le post-traitement

## Exemple dans l'application

```php
#[ORM\ManyToOne(targetEntity: Media::class)]
#[ORM\JoinColumn(onDelete: 'SET NULL')]
#[MediaField(role: 'cover', accept: ['image/*'])]
private ?Media $cover = null;
```

Le bundle ne connaît pas Product, Article, User, etc.

## Configuration

Importer les routes :

```yaml
neox_media:
    resource: '@NeoxMediaBundle/config/routes.yaml'
```

Configurer Vich et LiipImagine à partir des exemples dans `config/packages/`.

## Notes

Le squelette est volontairement extensible. Le stockage FILE est prêt pour Vich.
REMOTE est prévu pour un futur adapter S3/R2/MinIO.
EMBED/URL sont modélisés sans imposer de provider.


## Media Manager visuel

Le bundle fournit maintenant une couche de médiathèque réutilisable :

- grille responsive ;
- recherche ;
- filtres par type ;
- upload multiple ;
- drag & drop ;
- sélection simple/multiple en modale ;
- inspecteur de média ;
- édition titre / alt / description ;
- suppression ;
- collections ;
- API JSON ;
- bridge optionnel vers les systèmes CRUD.

Route principale :

```text
/admin/media
```

Dans une entité métier :

```php
#[ORM\ManyToOne(targetEntity: Media::class)]
#[MediaField(role: 'cover', accept: ['image/*'])]
private ?Media $cover = null;
```

Dans un FormType :

```php
$builder->add('cover', MediaPickerType::class, [
    'accept' => ['image/*'],
]);
```

### Intégration NeoxCrudBundle

`NeoxMediaBundle` ne dépend pas directement de `NeoxCrudBundle`.

Le service :

```php
Neox\MediaBundle\Bridge\Crud\MediaCrudFieldGuesser
```

lit automatiquement les propriétés marquées `#[MediaField]` et retourne le `MediaPickerType`
et ses options. `NeoxCrudBundle` peut donc consommer ce bridge lorsqu'il est installé.

### Assets Stimulus

Le bundle fournit :

```text
neox_media_library_controller.js
neox_media_upload_controller.js
neox_media_picker_controller.js
```

et une feuille de style :

```text
public/media-manager.css
```

Dans une vraie publication Composer, exposer/copier ces assets via AssetMapper,
Importmap ou `assets:install` selon la stratégie du projet hôte.
