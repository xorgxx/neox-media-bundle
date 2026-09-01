# 18. RabbitMQ

RabbitMQ is optional.

NeoxMediaBundle depends only on Symfony Messenger.

In the application:

```yaml
framework:
    messenger:
        transports:
            media_async: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            'Neox\MediaBundle\Message\ProcessMediaMessage': media_async
```

Example environment:

```dotenv
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/media
```

Then start the worker:

```bash
php bin/console messenger:consume media_async -vv
```

An application that does not want RabbitMQ can use another Messenger transport.

[← Messenger](17-messenger.md) · [NeoxCrud →](19-neox-crud.md)
