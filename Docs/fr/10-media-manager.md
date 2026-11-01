# 10. Media Manager

Route principale :

```text
/admin/media
```

Le Media Manager standalone accepte un paramètre local optionnel  `return_url`, par exemple /admin/media?return_url=/admin/products. Quand il est présent, le bouton Retour en haut à gauche utilise cette URL ; sinon il revient via l'historique du navigateur.

Le Media Manager fournit :

- grille responsive ;
- recherche ;
- filtres ;
- prévisualisation ;
- inspecteur ;
- édition des métadonnées ;
- suppression ;
- upload multiple ;
- drag & drop ;
- collections ;
- picker modal.

La barre latérale affiche les répertoires physiques en arbre : chaque dossier
est placé sous son parent, seul son nom est affiché, et une branche sœur se
replie lorsqu'une autre est ouverte. Le dossier sélectionné reste visible.

Les médias vidéo peuvent fournir une URL de vignette dans `metadata.thumbnail_url`.
Le Media Manager et le picker l'utilisent comme poster, y compris pour une
playlist HLS. Le bundle ne génère pas lui-même d'image depuis une playlist :
l'application hôte peut définir cette métadonnée lors de la génération HLS.

## Filtres

Les catégories principales sont :

- tous ;
- images ;
- vidéos ;
- audio ;
- documents.

## Inspecteur

Lorsqu'un média est sélectionné, l'inspecteur permet d'éditer :

- titre ;
- texte alternatif ;
- description.

Il affiche également les informations techniques disponibles.

[← MediaPicker](09-media-picker.md) · [Upload →](11-upload.md)
