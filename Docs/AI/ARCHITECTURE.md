# Architecture for AI Assistants

## Overview

```text
┌─────────────────────────────────────────────────────┐
│                 HOST APPLICATION                    │
│                                                     │
│ Product   Article   User   Event   Category   ...   │
└───────────────┬─────────────────────────────────────┘
                │ Doctrine relation
                ▼
┌─────────────────────────────────────────────────────┐
│                  NEOX MEDIA BUNDLE                  │
│                                                     │
│                    Media                            │
│        ┌─────────────┼───────────────┐              │
│        │             │               │              │
│       FILE          URL            EMBED          REMOTE
│        │                                             │
│        ▼                                             │
│  VichUploader                                       │
│        │                                             │
│        ▼                                             │
│  LiipImagine                                        │
│                                                     │
│ Media Manager                                       │
│  ├─ Grid                                            │
│  ├─ Upload                                          │
│  ├─ Picker                                          │
│  ├─ Collections                                     │
│  └─ API                                             │
│                                                     │
│ Messenger                                           │
│  └─ ProcessMediaMessage                             │
└───────────────┬─────────────────────────────────────┘
                │
        Optional integrations
                │
       ┌────────┼─────────┐
       ▼        ▼         ▼
    RabbitMQ  Mercure   FFmpeg
```

---

# Modules

## Entity

Contains the generic persistent objects:

```text
Media
MediaCollection
MediaCollectionItem
```

No business entities.

---

## Enum

```text
MediaType
MediaSource
MediaStatus
```

---

## Attribute

```text
MediaField
```

Describes how a property of a host entity uses media.

---

## Metadata

```text
MediaMetadataReader
MediaFieldDefinition
```

Transforms PHP attributes into exploitable metadata.

---

## Form

```text
MediaPickerType
```

Allows selecting a media from a Symfony Form.

The Twig rendering generates a "file manager" widget with:
- drag-and-drop zone (direct OS file upload);
- media card grid (thumbnail, name, size, actions: download, preview, remove);
- toolbar (counter, Remove all button, Upload button, Browse button);
- loader overlay with spinner;
- Media Manager modal (SweetAlert2) for library selection.

The Stimulus controller `neox-media-picker` handles all interactions (drag-and-drop, upload, preview, modal selection, removal).

### Display Modes

The widget supports two modes via the `mode` option:

- **`widget`** (default): full display with large drag-and-drop zone, 140px card grid, full toolbar.
- **`compact`**: reduced display — smaller drag-and-drop zone, 80px card grid, compact toolbar and buttons. Fully supports multiple, drag-and-drop, upload, library, removal. No different JS logic, only CSS overrides via the `.neox-media-form-picker-compact` class.

---

## Controller

```text
MediaUploadController
MediaManagerController
```

Responsibilities:

```text
upload
list
edit
delete
collections
picker
```

---

## Service

```text
MediaUrlGenerator
```

Single access point to obtain a media URL.

Never reconstruct a media URL elsewhere if the generator can do it.

---

## Message

```text
ProcessMediaMessage
```

---

## MessageHandler

```text
ProcessMediaMessageHandler
```

Entry point for asynchronous processing.

---

## Bridge

```text
Bridge/Crud/MediaCrudFieldGuesser
```

Optional connection to CRUD systems.

---

# Flow — File Upload

```text
Browser
   │
   │ multipart upload
   ▼
MediaUploadController
   │
   ├─ create Media
   ├─ setFile()
   ├─ detect MediaType
   └─ persist
        │
        ▼
    VichUploader
        │
        ▼
     file
        │
        ▼
ProcessMediaMessage
        │
        ▼
     Messenger
        │
        ▼
ProcessMediaMessageHandler
        │
        ├─ metadata
        ├─ EXIF
        ├─ preview
        ├─ FFmpeg if available
        └─ READY
```

---

# Flow — Product Cover

```text
Product.cover
      │
      │ ManyToOne
      ▼
    Media
      │
      ▼
MediaUrlGenerator
      │
      ├── FILE
      ├── URL
      ├── EMBED
      └── REMOTE
```

---

# Flow — NeoxCrud

```text
Product
 │
 │ #[MediaField]
 ▼
MediaMetadataReader
 │
 ▼
MediaCrudFieldGuesser
 │
 ▼
MediaPickerType
 │
 ▼
Media Manager modal
```

---

# Architectural Boundaries

## Core

```text
Entity
Enum
Attribute
Metadata
Form
Service
Controller
Messenger abstraction
Stimulus
Twig
```

## Optional

```text
RabbitMQ
Mercure
FFmpeg
S3
R2
MinIO
NeoxCrudBundle
```

If a new feature belongs to the optional part, it must be placed behind an abstraction.
