# 28. Dossiers — arborescence et dossiers dynamiques

`MediaCollection` est l'abstraction « dossier » du bundle. Avec
`neox_media.folders.enabled`, les collections deviennent un **arbre** de
dossiers virtuels pouvant contenir des éléments choisis manuellement
(statique) ou calculés (dynamique — « smart folder »).

> **Opt-in.** Désactivé par défaut — les collections se comportent comme
> avant (liste à plat de listes nommées).

## Deux modes de collection

| Mode | `dynamic` | Contenu |
|---|---|---|
| statique | `false` (défaut) | lignes `MediaCollectionItem`, ajout/retrait/réordonnancement manuel |
| dynamique (smart) | `true` | calculé à la requête depuis `rules` — rien n'est stocké |

```text
Marketing/                    ← dossier statique (parent)
├── Campagne 2026/            ← sous-dossier statique
└── Logos/                    ← dynamique : {"title_contains": "logo"}
Images produits/              ← dynamique : {"type": "image", "storage_dir": "acme-corp"}
```

## Arbre (`parent`)

Chaque collection a un `parent` nullable → chemins virtuels en slash.
`MediaCollection::getPath()` retourne `"marketing/campagne-2026/logos"`.

- `GET /admin/media/folders/tree` retourne l'arbre imbriqué (filtré par le
  voter `MEDIA_VIEW` si l'ownership est activée)
- `folders.max_depth` (défaut `5`) borne la récursion
- L'API empêche l'auto-parentage et les cycles à la création/mise à jour

## Règles dynamiques

`rules` est un objet JSON traduit en DQL par `FolderRuleEvaluator`. Seuls
les champs de la whitelist sont évalués — les clés arbitraires sont ignorées :

```yaml
neox_media:
    folders:
        enabled: true
        max_depth: 5
        rules:
            allowed_fields:   # whitelist par défaut
                - type              # image|video|audio|document|archive
                - source            # file|url|embed|remote
                - mime_prefix       # 'image/', 'video/'
                - owner             # 'user:42', 'team:marketing'
                - storage_dir       # 'acme-corp'
                - filename_prefix   # 'repertoire/produits/'
                - title_contains    # sous-chaîne insensible à la casse
                - created_after     # 'Y-m-d'
                - created_before    # 'Y-m-d'
                - size_min          # octets
                - size_max          # octets
                - metadata          # {"role": "cover"} — LIKE portable sur la colonne JSON
```

Exemple — « toutes les images du répertoire Acme » :

```json
{"type": "image", "storage_dir": "acme-corp"}
```

> **Aucune élévation d'accès.** Un dossier dynamique ne fait que *filtrer*
> des médias — chaque résultat passe toujours par le scoping de visibilité
> normal. Un utilisateur ne peut pas voir un média privé juste parce que les
> règles d'un dossier partagé le matchent. Pour donner accès, utilisez un
> `MediaShare` sur une collection **statique**.

## API

```http
POST /admin/media/collections
{"name":"Logos","parent":3,"dynamic":true,
 "rules":{"title_contains":"logo"},"visibility":"shared"}

PATCH /admin/media/collections/{id}
{"rules":{"type":"image"},"parent":null}

GET /admin/media/api?collection={id}     ← résout les règles automatiquement pour les dossiers dynamiques
GET /admin/media/folders/tree
```

`GET /collections/{id}` et `GET /collections` exposent `path`, `dynamic`,
`rules`, `visibility`.

## Combinaison avec l'ownership et storage_dir

| Flag | Effet |
|---|---|
| `folders.enabled` | arbre + règles dynamiques |
| `ownership.enabled` | les dossiers ont `owner`/`visibility`, partageables via `MediaShare` (sujet `user:`/`role:`/`team:`/`everyone:`) |
| `storage.directory.enabled` | `storage_dir` devient un champ de règle fiable miroir du disque |

Les trois sont indépendants et activables séparément.
