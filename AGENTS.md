# AGENTS.md

Ce fichier est le point d'entrée destiné aux assistants IA travaillant sur **NeoxMediaBundle**.

## Objectif du projet

NeoxMediaBundle est un bundle Symfony réutilisable pour gérer des médias de manière indépendante des entités métier.

Compatibilité cible :

- Symfony 7
- Symfony 8
- PHP >= 8.2
- PHP >= 8.4 lorsque Symfony 8 est utilisé
- Doctrine ORM 3
- VichUploaderBundle
- LiipImagineBundle
- Stimulus
- Symfony Messenger

Le bundle doit rester réutilisable et ne jamais dépendre directement d'une entité métier de l'application hôte.

## À lire avant toute modification

Lire dans cet ordre :

1. `Docs/en/AI/RULES.md`
2. `Docs/AI/ARCHITECTURE.md`
3. `Docs/en/AI/WORKFLOW.md`
4. `Docs/README.md`
5. les fichiers spécifiques au domaine modifié

## Invariant principal

Une entité métier ne doit pas stocker directement :

```text
file
filename
path
url technique
embed technique
storage key
```

Elle référence un objet :

```php
Neox\MediaBundle\Entity\Media
```

Exemple :

```php
#[ORM\ManyToOne(targetEntity: Media::class)]
#[MediaField(role: 'cover', accept: ['image/*'])]
private ?Media $cover = null;
```

## Principe de dépendance

Autorisé :

```text
Application -> NeoxMediaBundle
NeoxCrudBundle -> bridge NeoxMediaBundle
NeoxMediaBundle -> Symfony / Doctrine / Vich / LiipImagine
```

Interdit :

```text
NeoxMediaBundle -> Product
NeoxMediaBundle -> Article
NeoxMediaBundle -> User métier
NeoxMediaBundle -> NeoxCrudBundle obligatoire
NeoxMediaBundle -> RabbitMQ obligatoire
NeoxMediaBundle -> Mercure obligatoire
```

## Sources de médias

Le modèle universel utilise :

```text
FILE
URL
EMBED
REMOTE
```

Ne pas introduire une entité différente pour chaque source.

## Avant de coder

Toujours vérifier :

- si la fonctionnalité appartient au cœur ou à une intégration optionnelle ;
- si elle peut être réalisée sans connaître les entités métier ;
- si elle fonctionne sans NeoxCrudBundle ;
- si elle fonctionne sans RabbitMQ ;
- si elle fonctionne sans Mercure ;
- si elle reste compatible Symfony 7 et Symfony 8.

## Documentation

Toute fonctionnalité publique ajoutée doit être documentée dans `Docs/`.

Toute modification architecturale doit mettre à jour :

```text
Docs/README.md
```
