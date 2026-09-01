# Rules for AI Assistants

## 1. Purpose

These rules exist to prevent an AI from modifying NeoxMediaBundle as if it were a classic business application.

NeoxMediaBundle is a **reusable building block**.

The bundle must be installable in multiple applications without knowing their business domain.

---

# 2. Architecture Rules

## Rule 2.1 — Media is the central abstraction

All media resources go through:

```php
Neox\MediaBundle\Entity\Media
```

Do not create in the bundle:

```text
ProductImage
ArticleImage
UserAvatar
RecipeDocument
```

These concepts belong to the host applications.

---

## Rule 2.2 — Never store the path in the business entity

Forbidden:

```php
class Product
{
    private ?string $imagePath;
    private ?string $imageUrl;
    private ?string $filename;
}
```

Expected:

```php
class Product
{
    #[ORM\ManyToOne(targetEntity: Media::class)]
    private ?Media $cover = null;
}
```

---

## Rule 2.3 — The bundle does not know business entities

The bundle source code must not import:

```php
App\Entity\Product
App\Entity\Article
App\Entity\User
```

Examples may use them in `examples/` or documentation only.

---

## Rule 2.4 — Doctrine describes the relation, MediaField describes the media UX

Example:

```php
#[ORM\ManyToOne(targetEntity: Media::class)]
#[MediaField(
    role: 'cover',
    accept: ['image/*']
)]
private ?Media $cover = null;
```

`ORM\ManyToOne` defines the Doctrine relation.

`MediaField` tells the Media Manager / CRUD how to handle the field.

Do not try to replace Doctrine with `MediaField`.

---

## Rule 2.5 — External integrations remain optional

The core must not impose:

- RabbitMQ;
- Mercure;
- FFmpeg;
- S3;
- R2;
- MinIO;
- NeoxCrudBundle.

Optional dependencies are accessible via bridges, adapters or processors.

---

# 3. Storage Rules

## FILE

Use VichUploader.

## URL

Store the external URL in `Media`.

## EMBED

Use `embedUrl` and optionally `provider`.

## REMOTE

Use a storage identifier (`storageKey`) and an adapter.

Do not assume `REMOTE` necessarily means S3.

---

# 4. VichUploader Rules

VichUploader is responsible for the local file lifecycle.

Do not duplicate its responsibility with a service that manually moves files unless clearly documented necessity.

The field:

```php
private ?File $file = null;
```

is not persisted by Doctrine.

Changing the file must update `updatedAt`.

---

# 5. LiipImagine Rules

LiipImagine handles derived image transformations.

Do not automatically create a Doctrine row for each thumbnail.

Use LiipImagine cache for common variants.

Create a persistent variant only when there is a real business/technical reason:

- transcoding;
- materialized preview;
- permanent optimized file;
- FFmpeg output.

---

# 6. Media Manager Rules

The Media Manager must remain autonomous.

Expected features:

- grid;
- search;
- filters;
- multiple upload;
- drag & drop;
- editing;
- deletion;
- collections;
- modal selection;
- JSON API.

The Media Manager must not depend on the CRUD.

---

# 7. NeoxCrudBundle Rules

Integration goes through:

```text
MediaCrudFieldGuesser
MediaMetadataReader
MediaPickerType
```

NeoxMediaBundle must not declare NeoxCrudBundle as a required dependency.

---

# 8. Messenger Rules

The bundle uses:

```php
MessageBusInterface
```

and not RabbitMQ directly.

The transport is chosen by the host application.

Media processing must be compatible with:

```text
sync
Doctrine
Redis
AMQP/RabbitMQ
SQS
```

---

# 9. Stimulus Rules

Stimulus controllers must remain small and specialized.

Prefer:

```text
neox-media-library
neox-media-upload
neox-media-picker
```

over a single giant controller.

Preferred communication:

```text
DOM / Stimulus events
```

Limit direct dependencies between controllers.

---

# 10. Symfony Compatibility Rules

The code must remain compatible with Symfony 7 and Symfony 8.

Do not use a Symfony 8-only API without a fallback or justification.

Always check Composer constraints when adding a dependency.

---

# 11. Security Rules

An option like:

```text
accept: ['image/*']
```

is a UX rule, not a security validation.

Always provide server-side:

- real MIME;
- size;
- allowed extension;
- permissions;
- CSRF or equivalent protection;
- upload execution prevention;
- remote URL control;
- embed validation.

---

# 12. Documentation Rules

Any new public API must be documented.

Any new major feature must include:

```text
purpose
configuration
example
limitations
integration
```

Keep up to date:

```text
Docs/README.md
Docs/en/AI/ARCHITECTURE.md (or Docs/fr/AI/ARCHITECTURE.md)
Docs/en/AI/WORKFLOW.md (or Docs/fr/AI/WORKFLOW.md)
```

---

# 13. What an AI Must Not Do

Do not:

- rewrite the entire architecture without necessity;
- introduce a business dependency;
- store paths in `Product`;
- make Media depend on the CRUD;
- link the bundle directly to RabbitMQ;
- remove the FILE/URL/EMBED/REMOTE abstractions;
- confuse library collection with business gallery;
- create a table per media type;
- remove the `MediaUrlGenerator` abstraction.
