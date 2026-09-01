# 1. Presentation

NeoxMediaBundle provides a generic media layer for a Symfony application.

The fundamental principle is that business entities do not know the physical storage of the media.

```text
Product ─┐
Article ─┼──> Media ──> FILE / URL / EMBED / REMOTE
User ────┤
Event ───┘
```

A `Product` entity therefore references a `Media` object, not a path such as `/uploads/image.jpg`.

The bundle notably provides:

- centralized media management;
- VichUploader for files;
- LiipImagine for images;
- visual Media Manager;
- multiple upload and drag & drop;
- modal selection;
- collections;
- Twig helpers;
- Stimulus;
- Messenger;
- optional RabbitMQ integration;
- optional NeoxCrudBundle bridge.

[Next: Installation →](02-installation.md)
