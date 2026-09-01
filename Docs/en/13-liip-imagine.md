# 13. LiipImagine

LiipImagine handles image transformations.

Example filter sets:

```text
thumb   200 × 200
small   max 480
medium  max 1024
large   max 1920
```

The bundle does not require a `MediaVariant` entity for each thumbnail.

Standard variants can remain in LiipImagine cache.

A persistent variant entity is only useful for actually materialized and tracked files, for example:

- video transcoding;
- PDF preview;
- audio waveform;
- permanent optimized master.

[← Vich](12-vich-uploader.md) · [Collections →](14-collections.md)
