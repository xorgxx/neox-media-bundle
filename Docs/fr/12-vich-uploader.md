# 12. VichUploader

VichUploader est responsable du cycle de vie des fichiers `FILE`.

Il gère notamment :

- réception du fichier ;
- déplacement ;
- nom du fichier ;
- remplacement ;
- suppression.

Dans `Media` :

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

Le champ `file` n'est pas persisté par Doctrine.

`updatedAt` est modifié lors de l'affectation d'un nouveau fichier afin que Doctrine détecte le changement.

[← Upload](11-upload.md) · [LiipImagine →](13-liip-imagine.md)
