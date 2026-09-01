# 21. Bundle Extension

The bundle is designed to receive new layers without modifying business entities.

Possible extensions:

## Storage

Create an abstraction for:

```text
Local
S3
R2
MinIO
NAS
```

## Processors

Add specialized processors:

```text
ImageProcessor
VideoProcessor
AudioProcessor
PdfProcessor
MetadataProcessor
```

## Events

Plan for:

```text
MediaUploadedEvent
MediaProcessingStartedEvent
MediaProcessedEvent
MediaFailedEvent
MediaDeletedEvent
```

## Mercure

Mercure can listen to processing events and refresh the UI in real time.

The core must remain usable without Mercure.

[← Security](20-security.md) · [Examples →](22-examples.md)
