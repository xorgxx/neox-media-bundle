# 12. VichUploader

VichUploader is responsible for the lifecycle of `FILE` files.

It notably handles:

- file reception;
- moving;
- file naming;
- replacement;
- deletion.

In `Media`:

```php
#[Vich\Uploadable]
class Media
{
    #[Vich\UploadableField(
        mapping: 'neox_media',
        fileNameProperty: 'filename',
        size: 'size',
        mimeType: 'mimeType',
        originalName: 'originalName'
    )]
    private ?File $file = null;
}
```

The `file` field is not persisted by Doctrine.

`updatedAt` is modified when a new file is assigned so that Doctrine detects the change.

[← Upload](11-upload.md) · [LiipImagine →](13-liip-imagine.md)
