# 27. Propriété, visibilité et partages

Gestion multi-utilisateur : qui possède un média, qui peut le voir, qui peut
le modifier, comment le partager.

> **Opt-in.** Tout est désactivé par défaut. Avec
> `neox_media.ownership.enabled: false` (défaut), le bundle se comporte
> exactement comme avant : tous les médias sont globaux.

## Vue d'ensemble

Le bundle ne référence jamais votre entité `User` (règle 2.3). L'identité
passe par des **clés de sujet opaques** produites par un provider :

```text
user:42               clé propriétaire d'un utilisateur
role:ROLE_MARKETING   dérivée des rôles Symfony
team:marketing        toute clé métier ajoutée par votre provider
company:acme          ...
```

`Media.owner` stocke une clé propriétaire (`user:42`). `null` = média
**global** — visible et modifiable par tout utilisateur connecté.

`Media.visibility` vaut `private`, `shared` ou `public`
(`Neox\MediaBundle\Enum\MediaVisibility`) :

| Visibilité | Qui peut VOIR |
|---|---|
| `private` | propriétaire, sujets `MediaShare`, rôle bypass admin |
| `shared` | tout utilisateur authentifié |
| `public` | tout le monde (utilisable sur pages publiques) |

Édition/suppression/partage exigent toujours la propriété, un `MediaShare`
avec `can_edit`/`can_reshare`, ou le rôle bypass admin.

## Configuration

```yaml
# config/packages/neox_media.yaml
neox_media:
    ownership:
        enabled: true
        default_visibility: private     # private|shared|public
        admin_bypass_role: 'ROLE_ADMIN' # '' désactive le bypass
        provider: ~                     # id de votre service MediaOwnerProviderInterface
```

`folders.enabled` est indépendant, voir [28. Dossiers](28-folders.md).
`storage.directory` : [29. Répertoires physiques](29-storage-dir.md).

## Qui fournit les clés ? — MediaOwnerProviderInterface

```php
interface MediaOwnerProviderInterface
{
    /** Stocké dans Media::owner à la création. null = média global. */
    public function getCurrentOwnerKey(): ?string;

    /** Toutes les clés sous lesquelles l'utilisateur agit. */
    public function getCurrentSubjectKeys(): array;
}
```

**Défaut** : `SecurityMediaOwnerProvider` construit les clés depuis Symfony
Security sans connaître votre classe User :

```php
getCurrentOwnerKey()     // 'user:'.$user->getUserIdentifier()
getCurrentSubjectKeys()  // ['user:john@example.com', 'role:ROLE_USER', 'role:ROLE_EDITOR']
```

**Provider custom** — ajoutez des clés métier (team, tenant, company) :

```php
// src/Media/AppOwnerProvider.php
final class AppOwnerProvider implements MediaOwnerProviderInterface
{
    public function __construct(private Security $security) {}

    public function getCurrentOwnerKey(): ?string
    {
        $user = $this->security->getUser();
        return $user ? 'user:'.$user->getUserIdentifier() : null;
    }

    public function getCurrentSubjectKeys(): array
    {
        $user = $this->security->getUser();
        if (!$user) return [];

        $keys = ['user:'.$user->getUserIdentifier()];
        foreach ($user->getRoles() as $role) {
            $keys[] = 'role:'.$role;
        }
        if (method_exists($user, 'getTeam')) {
            $keys[] = 'team:'.$user->getTeam()?->getSlug();
        }
        return array_filter($keys);
    }
}
```

```yaml
neox_media:
    ownership:
        enabled: true
        provider: App\Media\AppOwnerProvider
```

Quand `enabled: false`, le bundle branche `NullMediaOwnerProvider` — tout
reste global, aucune colonne n'est remplie.

## Partage — ACL MediaShare

`MediaShare` accorde un accès à un média **ou une collection** pour un sujet :

| Champ | Valeurs |
|---|---|
| `targetType` | `media`, `collection` |
| `targetId` | id du média/collection |
| `subjectType` | `user`, `role`, `team`, `everyone` |
| `subjectKey` | `42`, `ROLE_MARKETING`, `marketing`, `*` |
| `canEdit` | accorde `MEDIA_EDIT`/`MEDIA_DELETE` |
| `canReshare` | accorde `MEDIA_SHARE` |

### API

```http
POST /admin/media/shares
{"target_type":"media","target_id":12,"subject_type":"role",
 "subject_key":"ROLE_MARKETING","can_edit":false,"can_reshare":false}

GET    /admin/media/shares?target_type=media&target_id=12
DELETE /admin/media/shares/{id}
```

`everyone` utilise `subject_key: '*'`. Créer deux fois le même partage met à
jour les flags (contrainte unique sur target+subject).

## Contrôle d'accès — MediaVoter

`Neox\MediaBundle\Security\MediaVoter` décide dans l'ordre :

1. `ownership.enabled` faux → accordé (legacy)
2. `admin_bypass_role` accordé → accordé
3. `owner == clé propriétaire courante` → accordé
4. `owner IS NULL` + connecté → accordé (média global)
5. `VIEW` + visibilité != `private` → accordé (`public` aussi anonyme)
6. `MediaShare` correspondant → `VIEW`, ou `EDIT`/`DELETE` avec `can_edit`,
   `SHARE` avec `can_edit`/`can_reshare`

Attributs : `MEDIA_VIEW`, `MEDIA_EDIT`, `MEDIA_DELETE`, `MEDIA_SHARE` sur
`Media` et `MediaCollection`.

Remplacez le voter par le vôtre pour des règles métier custom — le voter du
bundle ne repose que sur des clés opaques.

## Scoping API

`GET /admin/media/api` accepte `scope=all|mine|shared` :

- `all` (défaut) : global + non-privé + possédé + partagé-avec-moi
- `mine` : seulement `owner` correspondant à mes clés
- `shared` : partagé avec moi via ACL ou visibilité, hors les miens

Le scoping est appliqué **en SQL** (`MediaRepository::applyScope`), la
pagination reste correcte. Les réponses exposent `owner` et `visibility`.

## Rôle d'accès au Media Manager

La barrière `#[IsGranted('ROLE_ADMIN')]` est maintenant configurable :

```yaml
neox_media:
    security:
        access_role: 'ROLE_EDITOR'   # ou 'ROLE_USER', 'PUBLIC_ACCESS'
```

Combiné à `ownership.enabled`, cela permet à des non-admins de gérer leurs
propres médias via la même UI.

## Migration

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Ajoute `owner`, `visibility`, `storage_dir` sur `neox_media`, `parent`,
`owner`, `visibility`, `dynamic`, `rules` sur `neox_media_collection`, et la
table `neox_media_share`. Les lignes existantes gardent `owner = NULL` →
elles restent globales, conforme au comportement legacy.
