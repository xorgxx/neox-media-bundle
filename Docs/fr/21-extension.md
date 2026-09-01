# 21. Extension du bundle

Le bundle est conçu pour recevoir de nouvelles couches sans modifier les entités métier.

Extensions possibles :

## Stockage

Créer une abstraction pour :

```text
Local
S3
R2
MinIO
NAS
```

## Processors

Ajouter des processors spécialisés :

```text
ImageProcessor
VideoProcessor
AudioProcessor
PdfProcessor
MetadataProcessor
```

## Événements

Prévoir :

```text
MediaUploadedEvent
MediaProcessingStartedEvent
MediaProcessedEvent
MediaFailedEvent
MediaDeletedEvent
```

## Mercure

Mercure peut écouter les événements de traitement et actualiser l'interface en direct.

Le cœur doit rester utilisable sans Mercure.

[← Sécurité](20-security.md) · [Exemples →](22-examples.md)
