# 13. LiipImagine

LiipImagine prend en charge les transformations d'images.

Exemples de profils :

```text
thumb   200 × 200
small   max 480
medium  max 1024
large   max 1920
```

Le bundle ne nécessite pas une entité `MediaVariant` pour chaque thumbnail.

Les variantes standards peuvent rester dans le cache LiipImagine.

Une entité de variante persistante n'est utile que pour des fichiers réellement matérialisés et suivis, par exemple :

- transcodage vidéo ;
- preview PDF ;
- waveform audio ;
- master optimisé permanent.

[← Vich](12-vich-uploader.md) · [Collections →](14-collections.md)
