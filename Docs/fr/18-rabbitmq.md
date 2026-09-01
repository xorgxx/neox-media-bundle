# 18. RabbitMQ

RabbitMQ est optionnel.

NeoxMediaBundle dépend uniquement de Symfony Messenger.

Dans l'application :

```yaml
framework:
    messenger:
        transports:
            media_async: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            'Neox\MediaBundle\Message\ProcessMediaMessage': media_async
```

Exemple d'environnement :

```dotenv
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/media
```

Puis lancer le worker :

```bash
php bin/console messenger:consume media_async -vv
```

Une application qui ne souhaite pas RabbitMQ peut utiliser un autre transport Messenger.

[← Messenger](17-messenger.md) · [NeoxCrud →](19-neox-crud.md)
