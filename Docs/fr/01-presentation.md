# 1. Présentation

NeoxMediaBundle fournit une couche média générique pour une application Symfony.

Le principe fondamental est que les entités métier ne connaissent pas le stockage physique du média.

```text
Product ─┐
Article ─┼──> Media ──> FILE / URL / EMBED / REMOTE
User ────┤
Event ───┘
```

Une entité `Product` référence donc un objet `Media`, et non un chemin tel que `/uploads/image.jpg`.

Le bundle fournit notamment :

- gestion centralisée des médias ;
- VichUploader pour les fichiers ;
- LiipImagine pour les images ;
- Media Manager visuel ;
- upload multiple et drag & drop ;
- sélection modale ;
- collections ;
- helpers Twig ;
- Stimulus ;
- Messenger ;
- intégration optionnelle RabbitMQ ;
- bridge optionnel NeoxCrudBundle.

[Suivant : Installation →](02-installation.md)
