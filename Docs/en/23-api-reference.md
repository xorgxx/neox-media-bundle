# 23. API Reference

All routes are protected by `#[IsGranted('ROLE_ADMIN')]` and prefixed by `neox_media.routes.prefix` (default: `/admin/media`).

The prefix is configurable:

```yaml
neox_media:
    routes:
        enabled: true          # false = disable all bundle routes
        prefix: '/admin/media' # customize the prefix
```

All mutations require an `X-CSRF-Token` header with a valid CSRF token (scope `neox_media`).

---

## User Interface

### GET `{prefix}`

Displays the Media Manager (HTML, standalone).

### GET `{prefix}/picker`

Returns the picker in modal mode (HTML fragment, no layout).

---

## Media

### GET `{prefix}/api`

Paginated list of media.

**Query parameters:**

| Param | Type | Default | Description |
|---|---|---|---|
| `q` | string | — | Search on title, originalName, filename |
| `type` | string | — | Filter by type: `image`, `video`, `audio`, `document`, `archive`, `other` |
| `limit` | int | 60 | Items per page (max 200) |
| `offset` | int | 0 | Offset for pagination |
| `sort` | string | `updatedAt` | Sort: `updatedAt`, `createdAt`, `title`, `size` |

**Response 200:**

```json
{
    "items": [
        {
            "id": 1,
            "uuid": "...",
            "title": "Product photo",
            "alt": "Cocktail platter",
            "name": "plateau.jpg",
            "type": "image",
            "source": "file",
            "status": "ready",
            "mimeType": "image/jpeg",
            "size": 245678,
            "url": "/uploads/media/plateau.jpg",
            "width": 1920,
            "height": 1080
        }
    ],
    "total": 142,
    "limit": 60,
    "offset": 0,
    "hasMore": true
}
```

### GET `{prefix}/{id}`

Full detail of a media.

**Response 200:**

```json
{
    "id": 1,
    "uuid": "...",
    "title": "Product photo",
    "alt": "Cocktail platter",
    "description": "Platter presentation",
    "name": "plateau.jpg",
    "type": "image",
    "source": "file",
    "status": "ready",
    "mimeType": "image/jpeg",
    "size": 245678,
    "url": "/uploads/media/plateau.jpg",
    "embedUrl": null,
    "provider": null,
    "width": 1920,
    "height": 1080,
    "duration": null,
    "metadata": {
        "camera_make": "Canon",
        "orientation": 1
    },
    "createdAt": "2025-08-25T14:00:00+00:00",
    "updatedAt": "2025-08-25T14:05:00+00:00"
}
```

### PATCH `{prefix}/{id}`

Updates editable fields of a media.

**Body:**

```json
{
    "title": "New title",
    "alt": "New alternative text",
    "description": "New description"
}
```

All fields are optional (only present fields are updated).

**Response 200:** `{ "ok": true }`

### DELETE `{prefix}/{id}`

Permanently deletes a media (and its file via VichUploader).

**Response 200:** `{ "ok": true }`

---

## Upload

### POST `{prefix}/upload`

File upload (multipart/form-data).

**Body:** `files[]` — one or more files.

**Headers:** `X-CSRF-Token: <token>`

**Response 201:**

```json
{
    "items": [
        { "id": 10, "uuid": "..." }
    ],
    "warnings": []
}
```

**Response 422 (all files rejected):**

```json
{
    "error": "Upload rejected",
    "details": ["File \"hack.php\" exceeds maximum size of ..."]
}
```

---

## URL Media

### POST `{prefix}/url`

Creates a `URL` source media (external link).

**Body:**

```json
{
    "url": "https://example.com/image.jpg",
    "type": "image",
    "title": "External image",
    "alt": "Alternative description",
    "description": "Long description"
}
```

The URL is validated via `UrlValidator` (configurable allowed schemes).

**Response 201:**

```json
{
    "id": 15,
    "uuid": "...",
    "url": "https://example.com/image.jpg"
}
```

---

## EMBED Media

### POST `{prefix}/embed`

Creates an `EMBED` source media from a provider URL (YouTube, Vimeo, Dailymotion).

**Body:**

```json
{
    "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
}
```

The URL is resolved via `EmbedProviderRegistry` which automatically detects the provider.

**Response 201:**

```json
{
    "id": 20,
    "uuid": "...",
    "provider": "youtube",
    "embedUrl": "https://www.youtube.com/embed/dQw4w9WgXcQ"
}
```

**Response 422:** unsupported provider or invalid URL.

See [Embed Providers](26-embed-providers.md) for the list of providers and how to add them.

---

## Collections

### POST `{prefix}/collections`

Creates a new collection.

**Body:** `{ "name": "My collection" }`

**Response 201:** `{ "id": 5, "name": "My collection" }`

### GET `{prefix}/collections`

List of collections with search.

**Query parameters:**

| Param | Type | Default | Description |
|---|---|---|---|
| `q` | string | — | Search on name |

**Response 200:**

```json
{
    "items": [
        { "id": 1, "name": "Products", "itemCount": 12 },
        { "id": 2, "name": "Banners", "itemCount": 5 }
    ]
}
```

### GET `{prefix}/collections/{id}`

Collection detail with ordered items.

**Response 200:**

```json
{
    "id": 1,
    "name": "Products",
    "items": [
        {
            "itemId": 42,
            "position": 0,
            "id": 10,
            "uuid": "...",
            "title": "Photo 1",
            "type": "image",
            "source": "file",
            "status": "ready",
            "url": "/uploads/media/photo1.jpg"
        }
    ]
}
```

### POST `{prefix}/collections/{id}/reorder`

Reorders items in a collection.

**Body:**

```json
{
    "ids": [42, 43, 44, 45]
}
```

The IDs are the `itemId` (MediaCollectionItem), in the desired new order.

**Response 200:** `{ "ok": true }`

### POST `{prefix}/collections/{collection}/items/{media}`

Adds a media to a collection.

**Query parameters:** `position` (int, optional — default: end of collection)

**Response 201:** `{ "ok": true, "itemId": 50 }`

### DELETE `{prefix}/collection-items/{id}`

Removes an item from a collection.

**Response 200:** `{ "ok": true }`

---

## Twig

| Function | Description |
|---|---|
| `neox_media_url(media)` | Raw media URL (via MediaUrlGenerator) |
| `neox_media_filter(media, 'filter_name')` | Filtered URL via LiipImagine (FILE source only) |
| `neox_media_render(media)` | Automatic HTML rendering based on type |

See [Twig and rendering](15-twig.md).

---

## Security

- All routes: `#[IsGranted('ROLE_ADMIN')]`
- All mutations: `X-CSRF-Token` header required (token scope `neox_media`)
- Upload: real MIME validation, per-group size, extension whitelist
- URL: scheme validation (http/https by default)
- Embed: configurable provider whitelist

See [Security](20-security.md).

[← Examples](22-examples.md) · [Storage adapters →](25-storage.md)
