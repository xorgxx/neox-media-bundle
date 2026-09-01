# 5. Media Entity

`Media` is the universal representation of a media item.

Main fields:

| Field | Role |
|---|---|
| `id` | Doctrine identifier |
| `uuid` | stable public identifier |
| `type` | image, video, audio, document… |
| `source` | FILE, URL, EMBED, REMOTE |
| `status` | processing state |
| `filename` | Vich-managed filename |
| `originalName` | original name |
| `mimeType` | MIME type |
| `size` | size |
| `url` | external URL if applicable |
| `embedUrl` | embed URL |
| `storageKey` | remote storage key |
| `width`, `height` | dimensions |
| `duration` | audio/video duration |
| `title` | title |
| `alt` | alternative text |
| `description` | description |
| `metadata` | extensible metadata |

The business application should normally not use `filename` directly.

It manipulates the `Media` object.

[← Architecture](04-architecture.md) · [Sources →](06-media-sources.md)
