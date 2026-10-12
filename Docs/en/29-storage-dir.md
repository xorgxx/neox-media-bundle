# 29. Physical Directories per Context (storage_dir)

Organize uploaded files into subdirectories of the upload destination — e.g.
**one directory per company** — without any access-control system.

> **Opt-in.** Disabled by default: files are stored flat in
> `uploads/media/` exactly as before.

```text
public/uploads/media/
├── acme-corp/
│   └── a3f9e2b1.jpg
├── globex-inc/
│   └── b7d2f8a1.png
└── c4e1f0ab.pdf            ← media without directory key stays at root
```

> **Organization ≠ protection.** `storage_dir` only decides *where* a file
> lands on disk. It grants no isolation — every media remains globally
> visible unless `neox_media.ownership` is enabled.

## How it works

1. `neox_media.storage.directory.provider` points to your implementation of
   `MediaDirectoryProviderInterface::getDirectoryKey()`.
2. At upload, `MediaUploadController` stores the key in `Media::storageDir`
   — **persisted**, so the directory never depends on *who* generates the
   URL later.
3. `SubjectDirectoryNamer` (bundled Vich `DirectoryNamerInterface`) reads
   `Media::storageDir` and returns the subdirectory — after sanitizing it
   (no `..`, `/`, `\`, only `[a-zA-Z0-9-_]`).
4. `null` → file stored at the upload root (flat layout, BC).

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

## Example providers

Two ready-to-adapt implementations ship in `examples/`:

**`CompanyDirectoryProvider`** — `uploads/media/<company-slug>/`:

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

**`UserDirectoryProvider`** — `uploads/media/user-42/`:

```php
public function getDirectoryKey(): ?string
{
    return $this->security->getUser() !== null
        ? 'user-'.$this->security->getUser()->getUserIdentifier()
        : null;
}
```

The key is whatever **your app** decides — company slug, team id, tenant
code, `"shared"`, a constant. The bundle never guesses your domain model.

## Security note

`SubjectDirectoryNamer::sanitize()` is applied systematically: traversal
(`..`), path separators, NUL bytes and exotic characters are stripped.
You may return raw slugs — they are still normalized before touching the
filesystem.

## Combining with smart folders

`storage_dir` is a whitelisted rule field: a dynamic folder with
`{"storage_dir": "acme-corp"}` mirrors the physical directory as a virtual
folder. See [28. Folders](28-folders.md).

## Remote storage

For `REMOTE` source media, prefix the key the same way in
`Media::storageKey` (e.g. `acme-corp/abc.jpg`) inside your own
`StorageInterface` adapter — the filesystem adapter keeps working since the
key is opaque.
