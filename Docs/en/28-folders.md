# 28. Folders — Tree and Dynamic Smart Folders

`MediaCollection` is the folder abstraction of the bundle. With
`neox_media.folders.enabled`, collections become a **tree** of virtual
folders that can hold either hand-picked items (static) or computed content
(dynamic "smart folders").

> **Opt-in.** Disabled by default — collections behave exactly as before
> (flat list of named item lists).

## Two collection modes

| Mode | `dynamic` | Membership |
|---|---|---|
| static | `false` (default) | `MediaCollectionItem` rows, manual add/remove/reorder |
| dynamic (smart) | `true` | computed at query time from `rules` — nothing stored |

```text
Marketing/                    ← static folder (parent)
├── Campaign 2026/            ← static subfolder
└── Logos/                    ← dynamic: {"title_contains": "logo"}
Product images/               ← dynamic: {"type": "image", "storage_dir": "acme-corp"}
```

## Tree (`parent`)

Each collection has a nullable `parent` → slash-separated virtual paths.
`MediaCollection::getPath()` returns `"marketing/campaign-2026/logos"`.

- `GET /admin/media/folders/tree` returns the nested tree (filtered by the
  `MEDIA_VIEW` voter when ownership is enabled)
- `folders.max_depth` (default `5`) caps recursion
- The API prevents self-parenting and cycles on create/update

## Dynamic rules

`rules` is a JSON object translated to DQL by `FolderRuleEvaluator`.
Only whitelisted fields are evaluated — arbitrary keys are ignored:

```yaml
neox_media:
    folders:
        enabled: true
        max_depth: 5
        rules:
            allowed_fields:   # default whitelist
                - type              # image|video|audio|document|archive
                - source            # file|url|embed|remote
                - mime_prefix       # 'image/', 'video/'
                - owner             # 'user:42', 'team:marketing'
                - storage_dir       # 'acme-corp'
                - filename_prefix   # 'repertoire/produits/'
                - title_contains    # case-insensitive substring
                - created_after     # 'Y-m-d'
                - created_before    # 'Y-m-d'
                - size_min          # bytes
                - size_max          # bytes
                - metadata          # {"role": "cover"} — portable LIKE on the JSON column
```

Example smart folder — "all images uploaded under the Acme directory":

```json
{"type": "image", "storage_dir": "acme-corp"}
```

> **No access elevation.** A dynamic folder only *filters* media — each
> result still passes through the normal visibility scoping. A user cannot
> see a private media just because a shared smart folder's rules match it.
> To grant access, use `MediaShare` on a **static** collection.

## API

```http
POST /admin/media/collections
{"name":"Logos","parent":3,"dynamic":true,
 "rules":{"title_contains":"logo"},"visibility":"shared"}

PATCH /admin/media/collections/{id}
{"rules":{"type":"image"},"parent":null}

GET /admin/media/api?collection={id}     ← resolves rules automatically for dynamic folders
GET /admin/media/folders/tree
```

`GET /collections/{id}` and `GET /collections` expose `path`, `dynamic`,
`rules`, `visibility`.

## Combining with ownership and storage_dir

| Flag | Effect |
|---|---|
| `folders.enabled` | tree + dynamic rules |
| `ownership.enabled` | folders have `owner`/`visibility`, shareable via `MediaShare` (subject `user:`/`role:`/`team:`/`everyone:`) |
| `storage.directory.enabled` | `storage_dir` becomes a reliable rule field mirroring the disk layout |

All three are independent and can be enabled separately.
