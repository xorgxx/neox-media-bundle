# 15. Twig and Rendering

The bundle exposes:

```twig
{{ neox_media_url(media) }}
```

and:

```twig
{{ neox_media_render(media) }}
```

## URL

Always prefer:

```twig
{{ neox_media_url(product.cover) }}
```

to:

```twig
/uploads/media/{{ product.cover.filename }}
```

The former remains compatible with a storage change.

## Automatic Rendering

`neox_media_render()` chooses a rendering based on `MediaType`.

- IMAGE → `<img>`
- VIDEO → `<video>`
- AUDIO → `<audio>`
- EMBED → `<iframe>`
- other → link

If the URL or embed URL is null/empty, a broken link message is displayed instead of the media tag:

```html
<div class="neox-media-broken" ...>
    <svg>...</svg>
    <strong>Media title</strong>
    <small>Link inaccessible or deleted</small>
</div>
```

This prevents generating empty `<img src="">` or `<iframe src="">` tags and informs the user that the media is no longer accessible.

The API can be enriched with specialized helpers using LiipImagine.

## LiipImagine Filter

```twig
{{ neox_media_filter(media, 'neox_media_thumb') }}
```

Generates a filtered URL via LiipImagine for `FILE` source media.
For other sources (URL, EMBED, REMOTE), returns the raw URL without filtering.

Default available filters:

```twig
{{ neox_media_filter(media, 'neox_media_thumb') }}   {# 200×200 #}
{{ neox_media_filter(media, 'neox_media_small') }}   {# max 480 #}
{{ neox_media_filter(media, 'neox_media_medium') }}  {# max 1024 #}
{{ neox_media_filter(media, 'neox_media_large') }}   {# max 1920 #}
```

[← Collections](14-collections.md) · [Stimulus →](16-stimulus.md)
