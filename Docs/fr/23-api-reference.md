# 23. Référence API

Toutes les routes sont protégées par `#[IsGranted('ROLE_ADMIN')]` et préfixées par `neox_media.routes.prefix` (défaut : `/admin/media`).

Le prefix est configurable :

```yaml
neox_media:
    routes:
        enabled: true          # false = désactiver toutes les routes du bundle
        prefix: '/admin/media' # personnaliser le prefix
```

Toutes les mutations nécessitent un header `X-CSRF-Token` avec un token CSRF valide (scope `neox_media`).

---

## Interface utilisateur

### GET `{prefix}`

Affiche le Media Manager (HTML, standalone).

### GET `{prefix}/picker`

Retourne le picker en mode modal (HTML fragment, sans layout).

---

## Médias

### GET `{prefix}/api`

Liste paginée des médias.

**Paramètres query :**

| Param | Type | Défaut | Description |
|---|---|---|---|
| `q` | string | — | Recherche sur titre, originalName, filename |
| `type` | string | — | Filtrer par type : `image`, `video`, `audio`, `document`, `archive`, `other` |
| `limit` | int | 60 | Nombre d'items par page (max 200) |
| `offset` | int | 0 | Offset pour pagination |
| `sort` | string | `updatedAt` | Tri : `updatedAt`, `createdAt`, `title`, `size` |

**Response 200 :**

```json
{
    "items": [
        {
            "id": 1,
            "uuid": "...",
            "title": "Photo produit",
            "alt": "Plateau cocktail",
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

Détail complet d'un média.

**Response 200 :**

```json
{
    "id": 1,
    "uuid": "...",
    "title": "Photo produit",
    "alt": "Plateau cocktail",
    "description": "Présentation du plateau",
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

Met à jour les champs éditables d'un média.

**Body :**

```json
{
    "title": "Nouveau titre",
    "alt": "Nouveau texte alternatif",
    "description": "Nouvelle description"
}
```

Tous les champs sont optionnels (seuls les champs présents sont mis à jour).

**Response 200 :** `{ "ok": true }`

### DELETE `{prefix}/{id}`

Supprime définitivement un média (et son fichier via VichUploader).

**Response 200 :** `{ "ok": true }`

---

## Upload

### POST `{prefix}/upload`

Upload de fichiers (multipart/form-data).

**Body :** `files[]` — un ou plusieurs fichiers.

**Headers :** `X-CSRF-Token: <token>`

**Response 201 :**

```json
{
    "items": [
        { "id": 10, "uuid": "..." }
    ],
    "warnings": []
}
```

**Response 422 (tous les fichiers rejetés) :**

```json
{
    "error": "Upload rejected",
    "details": ["File \"hack.php\" exceeds maximum size of ..."]
}
```

---

## Médias URL

### POST `{prefix}/url`

Crée un média de source `URL` (lien externe).

**Body :**

```json
{
    "url": "https://example.com/image.jpg",
    "type": "image",
    "title": "Image externe",
    "alt": "Description alternative",
    "description": "Description longue"
}
```

L'URL est validée via `UrlValidator` (schemes autorisés configurables).

**Response 201 :**

```json
{
    "id": 15,
    "uuid": "...",
    "url": "https://example.com/image.jpg"
}
```

---

## Médias EMBED

### POST `{prefix}/embed`

Crée un média de source `EMBED` depuis une URL de provider (YouTube, Vimeo, Dailymotion).

**Body :**

```json
{
    "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
}
```

L'URL est résolue via `EmbedProviderRegistry` qui détecte automatiquement le provider.

**Response 201 :**

```json
{
    "id": 20,
    "uuid": "...",
    "provider": "youtube",
    "embedUrl": "https://www.youtube.com/embed/dQw4w9WgXcQ"
}
```

**Response 422 :** provider non supporté ou URL invalide.

Voir [Embed Providers](26-embed-providers.md) pour la liste des providers et comment en ajouter.

---

## Collections

### POST `{prefix}/collections`

Crée une nouvelle collection.

**Body :** `{ "name": "Ma collection" }`

**Response 201 :** `{ "id": 5, "name": "Ma collection" }`

### GET `{prefix}/collections`

Liste des collections avec recherche.

**Paramètres query :**

| Param | Type | Défaut | Description |
|---|---|---|---|
| `q` | string | — | Recherche sur le nom |

**Response 200 :**

```json
{
    "items": [
        { "id": 1, "name": "Produits", "itemCount": 12 },
        { "id": 2, "name": "Bannières", "itemCount": 5 }
    ]
}
```

### GET `{prefix}/collections/{id}`

Détail d'une collection avec ses items ordonnés.

**Response 200 :**

```json
{
    "id": 1,
    "name": "Produits",
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

Réordonne les items d'une collection.

**Body :**

```json
{
    "ids": [42, 43, 44, 45]
}
```

Les IDs sont les `itemId` (MediaCollectionItem), dans le nouvel ordre souhaité.

**Response 200 :** `{ "ok": true }`

### POST `{prefix}/collections/{collection}/items/{media}`

Ajoute un média à une collection.

**Paramètres query :** `position` (int, optionnel — défaut : fin de la collection)

**Response 201 :** `{ "ok": true, "itemId": 50 }`

### DELETE `{prefix}/collection-items/{id}`

Retire un item d'une collection.

**Response 200 :** `{ "ok": true }`

---

## Twig

| Function | Description |
|---|---|
| `neox_media_url(media)` | URL brute du média (via MediaUrlGenerator) |
| `neox_media_filter(media, 'filter_name')` | URL filtrée via LiipImagine (source FILE uniquement) |
| `neox_media_render(media)` | Rendu HTML automatique selon le type |

Voir [Twig et rendu](15-twig.md).

---

## Sécurité

- Toutes les routes : `#[IsGranted('ROLE_ADMIN')]`
- Toutes les mutations : header `X-CSRF-Token` requis (token scope `neox_media`)
- Upload : validation MIME réel, taille par groupe, extension whitelist
- URL : validation scheme (http/https par défaut)
- Embed : whitelist de providers configurables

Voir [Sécurité](20-security.md).

[← Exemples](22-examples.md) · [Storage adapters →](25-storage.md)
