# 15. Twig et rendu

Le bundle expose :

```twig
{{ neox_media_url(media) }}
```

et :

```twig
{{ neox_media_render(media) }}
```

## URL

Toujours préférer :

```twig
{{ neox_media_url(product.cover) }}
```

à :

```twig
/uploads/media/{{ product.cover.filename }}
```

Le premier reste compatible avec un changement de stockage.

## Rendu automatique

`neox_media_render()` choisit un rendu selon `MediaType`.

- IMAGE → `<img>`
- VIDEO → `<video>`
- AUDIO → `<audio>`
- EMBED → `<iframe>`
- autre → lien

Si l'URL ou l'embed URL est null/vide, un message de rupture de lien est affiché à la place du tag média :

```html
<div class="neox-media-broken" ...>
    <svg>...</svg>
    <strong>Titre du média</strong>
    <small>Lien inaccessible ou supprimé</small>
</div>
```

Cela évite de générer des tags `<img src="">` ou `<iframe src="">` vides et informe l'utilisateur que le média n'est plus accessible.

L'API pourra être enrichie avec des helpers spécialisés utilisant LiipImagine.

## Filtre LiipImagine

```twig
{{ neox_media_filter(media, 'neox_media_thumb') }}
```

Génère une URL filtrée via LiipImagine pour les médias source `FILE`.
Pour les autres sources (URL, EMBED, REMOTE), retourne l'URL brute sans filtrage.

Filtres disponibles par défaut :

```twig
{{ neox_media_filter(media, 'neox_media_thumb') }}   {# 200×200 #}
{{ neox_media_filter(media, 'neox_media_small') }}   {# max 480 #}
{{ neox_media_filter(media, 'neox_media_medium') }}  {# max 1024 #}
{{ neox_media_filter(media, 'neox_media_large') }}   {# max 1920 #}
```

[← Collections](14-collections.md) · [Stimulus →](16-stimulus.md)
