# 27. Ownership, Visibility and Shares

Multi-user media management: who owns a media, who can see it, who can edit
it, and how to share it.

> **Opt-in.** Everything on this page is disabled by default. With
> `neox_media.ownership.enabled: false` (the default), the bundle behaves
> exactly like previous versions: every media is global.

## Overview

The bundle never references your `User` entity (Rule 2.3). Instead, all
identity is expressed through **opaque subject keys** produced by a provider:

```text
user:42               the owner key of a user
role:ROLE_MARKETING   derived from Symfony roles
team:marketing        any app-level key your provider adds
company:acme          ...
```

`Media.owner` stores a single owner key (`user:42`). `null` means a
**global** media — visible and editable by any authenticated user.

`Media.visibility` is one of `private`, `shared`, `public`
(`Neox\MediaBundle\Enum\MediaVisibility`):

| Visibility | Who can VIEW |
|---|---|
| `private` | owner, `MediaShare` subjects, admin bypass role |
| `shared` | any authenticated user |
| `public` | anyone (also usable on public pages) |

Edit/delete/share always requires ownership, an `MediaShare` entry with
`can_edit`/`can_reshare`, or the admin bypass role.

## Configuration

```yaml
# config/packages/neox_media.yaml
neox_media:
    ownership:
        enabled: true
        default_visibility: private     # private|shared|public
        admin_bypass_role: 'ROLE_ADMIN' # '' disables the bypass
        provider: ~                     # your MediaOwnerProviderInterface service id
```

`folders.enabled` is independent and documented in
[28. Folders](28-folders.md). `storage.directory` in
[29. Physical directories](29-storage-dir.md).

## Who provides the keys? — MediaOwnerProviderInterface

```php
interface MediaOwnerProviderInterface
{
    /** Stored on Media::owner at creation. null = global media. */
    public function getCurrentOwnerKey(): ?string;

    /** All keys the current user acts as (owner + roles + app keys). */
    public function getCurrentSubjectKeys(): array;
}
```

**Default**: `SecurityMediaOwnerProvider` builds keys from Symfony Security
without knowing your user class:

```php
getCurrentOwnerKey()     // 'user:'.$user->getUserIdentifier()
getCurrentSubjectKeys()  // ['user:john@example.com', 'role:ROLE_USER', 'role:ROLE_EDITOR']
```

**Custom provider** — add app-level keys (team, tenant, company):

```php
// src/Media/AppOwnerProvider.php
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AppOwnerProvider implements MediaOwnerProviderInterface
{
    public function __construct(private TokenStorageInterface $tokenStorage) {}

    public function getCurrentOwnerKey(): ?string
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        return $user instanceof UserInterface ? 'user:'.$user->getUserIdentifier() : null;
    }

    public function getCurrentSubjectKeys(): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (!$user instanceof UserInterface) return [];

        $keys = ['user:'.$user->getUserIdentifier()];
        foreach ($token->getRoleNames() as $role) { // SF7+SF8-safe (getRoles() removed in SF8)
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

When `enabled: false`, the bundle wires `NullMediaOwnerProvider` — everything
stays global, no column is populated.

## Sharing — MediaShare ACL

`MediaShare` grants access to a media **or a collection** for a subject:

| Field | Values |
|---|---|
| `targetType` | `media`, `collection` |
| `targetId` | id of the media/collection |
| `subjectType` | `user`, `role`, `team`, `everyone` |
| `subjectKey` | `42`, `ROLE_MARKETING`, `marketing`, `*` |
| `canEdit` | grants `MEDIA_EDIT`/`MEDIA_DELETE` |
| `canReshare` | grants `MEDIA_SHARE` |

### API

```http
POST /admin/media/shares
{"target_type":"media","target_id":12,"subject_type":"role",
 "subject_key":"ROLE_MARKETING","can_edit":false,"can_reshare":false}

GET    /admin/media/shares?target_type=media&target_id=12
DELETE /admin/media/shares/{id}
```

`everyone` uses `subject_key: '*'`. Creating the same share twice updates
the flags (unique constraint on target+subject).

## Access control — MediaVoter

`Neox\MediaBundle\Security\MediaVoter` decides, in order:

1. `ownership.enabled` false → grant (legacy)
2. `admin_bypass_role` granted → grant
3. `owner == current owner key` → grant
4. `owner IS NULL` + logged in → grant (global media)
5. `VIEW` + visibility != `private` → grant (`public` also anonymous)
6. matching `MediaShare` → `VIEW`, or `EDIT`/`DELETE` with `can_edit`,
   `SHARE` with `can_edit`/`can_reshare`

Attributes: `MEDIA_VIEW`, `MEDIA_EDIT`, `MEDIA_DELETE`, `MEDIA_SHARE` on
`Media` and `MediaCollection`.

Replace the voter with your own to implement custom business rules — the
bundle's voter only relies on opaque keys.

## API scoping

`GET /admin/media/api` accepts `scope=all|mine|shared`:

- `all` (default): global + non-private + owned + shared-with-me
- `mine`: only `owner` matching my keys
- `shared`: shared with me via ACL or visibility, excluding mine

Scoping is applied **in SQL** (`MediaRepository::applyScope`), so pagination
stays correct. Responses include `owner` and `visibility` fields.

## Media Manager access role

The `#[IsGranted('ROLE_ADMIN')]` gate is now configurable:

```yaml
neox_media:
    security:
        access_role: 'ROLE_EDITOR'   # or 'ROLE_USER', 'PUBLIC_ACCESS'
```

Combined with `ownership.enabled`, this lets non-admin users manage their
own media through the same UI.

## Migration

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Adds `owner`, `visibility`, `storage_dir` on `neox_media`, `parent`,
`owner`, `visibility`, `dynamic`, `rules` on `neox_media_collection`, and
the `neox_media_share` table. Existing rows keep `owner = NULL` → they stay
global, matching the legacy behaviour.
