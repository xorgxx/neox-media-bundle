# 7A. Core Concept: the business entity does not handle upload

This is a central point of NeoxMediaBundle.

A business entity like `Product`, `Article`, `Category` or `User` must **never directly handle file upload**.

It does not receive:

```text
UploadedFile
filename
path
technical url
```

It only receives an object:

```php
Neox\MediaBundle\Entity\Media
```

---

# Simple example: product cover image

In `Product`:

```php
use Neox\MediaBundle\Attribute\MediaField;
use Neox\MediaBundle\Entity\Media;

#[ORM\ManyToOne(targetEntity: Media::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
#[MediaField(
    role: 'cover',
    accept: ['image/*']
)]
private ?Media $cover = null;
```

The form interface then displays a button like:

```text
Cover image

[ Choose a media ]
```

The button does not open a simple `<input type="file">`.

It opens the **MediaPicker**.

---

# The MediaPicker

The MediaPicker displays media already known to the application.

Example:

```text
┌─────────────────────────────────────────────┐
│ Choose a media                       [ X ]  │
├─────────────────────────────────────────────┤
│ Search...            Images ▼   [ Import ]  │
├─────────────────────────────────────────────┤
│                                             │
│  🖼️    🖼️    🖼️    🖼️    🖼️           │
│                                             │
│  🖼️    🖼️    🖼️    🖼️    🖼️           │
│                                             │
├─────────────────────────────────────────────┤
│                    [ Select ]               │
└─────────────────────────────────────────────┘
```

The user can then:

1. choose an existing media;
2. import a new file;
3. wait for the new media to appear in the grid;
4. select it;
5. confirm.

At the end, the form does not receive the file.

It receives the selected `Media` object.

Conceptually:

```php
$product->setCover($media);
```

---

# Complete Flow

```text
Product Edit
      │
      ▼
[ Choose a media ]
      │
      ▼
MediaPicker
      │
      ├── select an existing media
      │
      └── [ Import ]
              │
              ▼
        MediaUploadController
              │
              ▼
          create Media
              │
              ▼
           VichUploader
              │
              ▼
      new media in the grid
              │
              ▼
         user selection
              │
              ▼
      Product.cover = Media
```

---

# Why this model matters

It allows reusing the same media.

Example:

```text
Media #42 = logo-neox.webp

Product A ─────┐
Product B ─────┼──> Media #42
Article X ─────┘
```

The physical file is imported only once.

Business entities only reference the same `Media` object.

---

# Gallery case

For multiple media:

```php
#[ORM\ManyToMany(targetEntity: Media::class)]
#[MediaField(
    role: 'gallery',
    accept: ['image/*', 'video/*'],
    multiple: true,
    sortable: true
)]
private Collection $gallery;
```

The interface can display:

```text
Gallery

[ + Add media ]

┌────────┐ ┌────────┐ ┌────────┐
│ image1 │ │ image2 │ │ image3 │
└────────┘ └────────┘ └────────┘
```

The button:

```text
[ + Add media ]
```

opens the same MediaPicker.

The user can select multiple media, then the application conceptually does:

```php
foreach ($selectedMedia as $media) {
    $product->addMedia($media);
}
```

---

# The Import button belongs to the media library

This is an important rule.

The `Import` button belongs to the **Media Manager / MediaPicker**.

It does not belong to `Product`.

The media library knows how to:

- receive the file;
- create `Media`;
- call VichUploader;
- launch Messenger;
- display the new media;
- allow its selection.

The business entity only attaches the selected media.

---

# FILE, URL, EMBED and REMOTE

The same principle applies to all sources.

The MediaPicker can eventually offer:

```text
[ Import a file ]
[ Add a URL ]
[ Add an embed ]
```

In all cases, the business entity always receives:

```php
Media
```

and never the technical details of the source.

---

# Summary

The correct mental model is:

```text
Product
   │
   │ references
   ▼
 Media
   │
   ├── FILE
   ├── URL
   ├── EMBED
   └── REMOTE
```

and not:

```text
Product
   │
   └── upload / path / filename / url
```

[← Integration in a business entity](07-entity-integration.md) · [MediaField →](08-media-field.md)
