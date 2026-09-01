# 14. Collections

Collections organize the media library independently of business entities.

Entities:

```text
MediaCollection
      │
      └── MediaCollectionItem
                 │
                 └── Media
```

`MediaCollectionItem` carries the position of the media in the collection.

A collection can represent for example:

- Weddings;
- Products;
- Logos;
- Commercial documents;
- Photos 2026.

A library collection does not necessarily replace a `Product` gallery.
The former organizes the library; the latter constitutes a business relation.

[← LiipImagine](13-liip-imagine.md) · [Twig →](15-twig.md)
