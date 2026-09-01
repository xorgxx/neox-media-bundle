# 17. Messenger and Asynchronous Processing

After media creation:

```php
$bus->dispatch(new ProcessMediaMessage($media->getId()));
```

The handler passes the media through different states:

```text
PENDING
   ↓
PROCESSING
   ↓
READY

or

FAILED
```

The current handler is an extension point.

Future processing may include:

- EXIF extraction;
- dimensions;
- MIME analysis;
- PDF preview;
- FFmpeg;
- video poster generation;
- audio metadata;
- indexing.

[← Stimulus](16-stimulus.md) · [RabbitMQ →](18-rabbitmq.md)
