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
Media                (+ owner, visibility, storageDir — opt-in)
MediaCollection      (+ parent, owner, visibility, dynamic, rules — opt-in)
MediaCollectionItem
MediaShare           (ACL entry: target + subject keys — opt-in)
```

No business entities. `owner` / subject keys are opaque strings, never
references to host entities.

---

## Enum

```text
MediaType
MediaSource
MediaStatus
MediaRiskLevel
MediaVisibility    (private | shared | public)
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

## Ownership (opt-in, neox_media.ownership)

```text
Ownership/MediaOwnerProviderInterface   — opaque owner + subject keys
Ownership/SecurityMediaOwnerProvider    — default: user:<identifier> + role:<ROLE>
Ownership/NullMediaOwnerProvider        — global mode (ownership disabled)
Security/MediaVoter                     — MEDIA_VIEW/EDIT/DELETE/SHARE on Media + MediaCollection
Entity/MediaShare + Repository          — ACL entries (user|role|team|everyone keys)
```

Multi-user isolation and sharing. All identity is expressed as opaque
strings — the bundle never imports a host User entity.

---

## Folder (opt-in, neox_media.folders)

```text
Folder/FolderRuleEvaluator   — MediaCollection::rules JSON → DQL criteria
```

Collection tree (`parent`) and dynamic smart folders (`dynamic` + `rules`).
Rule fields are whitelisted via `folders.rules.allowed_fields`. Dynamic
folders filter only — they never elevate access.

---

## Storage (opt-in, neox_media.storage.directory)

```text
Storage/StorageInterface               — REMOTE url resolution (existing)
Storage/FilesystemStorageAdapter       — local/CDN fallback (existing)
Storage/MediaDirectoryProviderInterface — app-supplied physical dir key
Storage/SubjectDirectoryNamer          — Vich directory namer reading
                                         Media::storageDir (+ sanitize)
```

Physical per-context subdirectories (e.g. one dir per company). The key is
persisted on `Media::storageDir` at upload so URL generation is context-free.

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

## Optional (feature flags — disabled by default)

```text
ownership.*            — MediaVoter, MediaShare, owner keys
folders.*              — collection tree + dynamic rules
storage.directory.*    — physical per-context subdirectories
security.access_role   — configurable Media Manager gate
RabbitMQ
Mercure
FFmpeg
S3
R2
MinIO
NeoxCrudBundle
```

If a new feature belongs to the optional part, it must be placed behind an abstraction.
