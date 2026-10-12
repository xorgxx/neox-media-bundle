# 29. Répertoires physiques par contexte (storage_dir)

Organiser les fichiers uploadés en sous-répertoires de la destination —
par exemple **un répertoire par compagnie** — sans aucun système de droits.

> **Opt-in.** Désactivé par défaut : les fichiers sont stockés à plat dans
> `uploads/media/` exactement comme avant.

```text
public/uploads/media/
├── acme-corp/
│   └── a3f9e2b1.jpg
├── globex-inc/
│   └── b7d2f8a1.png
└── c4e1f0ab.pdf            ← média sans clé de répertoire reste à la racine
```

> **Organisation ≠ protection.** `storage_dir` décide seulement *où* un
> fichier atterrit sur disque. Il n'accorde aucune isolation — tout média
> reste globalement visible sauf si `neox_media.ownership` est activé.

## Fonctionnement

1. `neox_media.storage.directory.provider` pointe vers votre implémentation
   de `MediaDirectoryProviderInterface::getDirectoryKey()`.
2. À l'upload, `MediaUploadController` stocke la clé dans `Media::storageDir`
   — **persistée**, donc le répertoire ne dépend jamais de *qui* génère
   l'URL ensuite.
3. `SubjectDirectoryNamer` (namer Vich fourni) lit `Media::storageDir` et
   retourne le sous-répertoire — après sanitization (pas de `..`, `/`, `\`,
   uniquement `[a-zA-Z0-9-_]`).
4. `null` → fichier stocké à la racine (à plat, rétrocompatible).

## Configuration

```yaml
# config/packages/neox_media.yaml
neox_media:
    storage:
        directory:
            enabled: true
            provider: App\Media\CompanyDirectoryProvider
```

```yaml
# config/packages/vich_uploader.yaml
vich_uploader:
    mappings:
        neox_media:
            directory_namer:
                service: Neox\MediaBundle\Storage\SubjectDirectoryNamer
```

## Exemples de providers

Deux implémentations prêtes à adapter dans `examples/` :

**`CompanyDirectoryProvider`** — `uploads/media/<company-slug>/` :

```php
final class CompanyDirectoryProvider implements MediaDirectoryProviderInterface
{
    public function getDirectoryKey(): ?string
    {
        $company = $this->security->getUser()?->getCompany();
        return $company?->getSlug() ?? ($company ? 'company-'.$company->getId() : null);
    }
}
```

**`UserDirectoryProvider`** — `uploads/media/user-42/` :

```php
public function getDirectoryKey(): ?string
{
    return $this->security->getUser() !== null
        ? 'user-'.$this->security->getUser()->getUserIdentifier()
        : null;
}
```

La clé est ce que **votre app** décide — slug compagnie, id d'équipe, code
tenant, `"shared"`, une constante. Le bundle ne devine jamais votre modèle
métier.

## Note sécurité

`SubjectDirectoryNamer::sanitize()` est appliqué systématiquement : traversal
(`..`), séparateurs, octets NUL et caractères exotiques sont supprimés. Vous
pouvez retourner des slugs bruts — ils sont normalisés avant de toucher le
filesystem.

## Combinaison avec les smart folders

`storage_dir` est un champ de règle whitelisté : un dossier dynamique avec
`{"storage_dir": "acme-corp"}` reflète le répertoire physique en dossier
virtuel. Voir [28. Dossiers](28-folders.md).

## Stockage distant

Pour les médias `REMOTE`, préfixez la clé de la même façon dans
`Media::storageKey` (ex. `acme-corp/abc.jpg`) dans votre propre adapter
`StorageInterface` — l'adapter filesystem continue de fonctionner puisque
la clé est opaque.
