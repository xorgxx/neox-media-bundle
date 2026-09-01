# 5. Entité Media

`Media` constitue la représentation universelle d'un média.

Principaux champs :

| Champ | Rôle |
|---|---|
| `id` | identifiant Doctrine |
| `uuid` | identifiant public stable |
| `type` | image, vidéo, audio, document… |
| `source` | FILE, URL, EMBED, REMOTE |
| `status` | état de traitement |
| `filename` | nom du fichier géré par Vich |
| `originalName` | nom original |
| `mimeType` | type MIME |
| `size` | taille |
| `url` | URL externe éventuelle |
| `embedUrl` | URL d'intégration |
| `storageKey` | clé d'un stockage distant |
| `width`, `height` | dimensions |
| `duration` | durée audio/vidéo |
| `title` | titre |
| `alt` | texte alternatif |
| `description` | description |
| `metadata` | métadonnées extensibles |

L'application métier ne doit normalement pas utiliser directement `filename`.

Elle manipule l'objet `Media`.

[← Architecture](04-architecture.md) · [Sources →](06-media-sources.md)
