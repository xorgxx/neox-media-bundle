# 17. Messenger et traitements asynchrones

Après création du média :

```php
$bus->dispatch(new ProcessMediaMessage($media->getId()));
```

Le handler passe le média par différents états :

```text
PENDING
   ↓
PROCESSING
   ↓
READY

ou

FAILED
```

Le handler actuel constitue un point d'extension.

Les traitements futurs peuvent inclure :

- extraction EXIF ;
- dimensions ;
- analyse MIME ;
- preview PDF ;
- FFmpeg ;
- génération de poster vidéo ;
- métadonnées audio ;
- indexation.

[← Stimulus](16-stimulus.md) · [RabbitMQ →](18-rabbitmq.md)
