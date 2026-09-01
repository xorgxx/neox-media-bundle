# 14. Collections

Les collections servent à organiser la médiathèque indépendamment des entités métier.

Entités :

```text
MediaCollection
      │
      └── MediaCollectionItem
                 │
                 └── Media
```

`MediaCollectionItem` porte la position du média dans la collection.

Une collection peut représenter par exemple :

- Mariages ;
- Produits ;
- Logos ;
- Documents commerciaux ;
- Photos 2026.

Une collection de médiathèque ne remplace pas nécessairement une galerie de `Product`.
La première organise la bibliothèque ; la seconde constitue une relation métier.

[← LiipImagine](13-liip-imagine.md) · [Twig →](15-twig.md)
