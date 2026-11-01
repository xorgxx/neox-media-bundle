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

### Prévisualisation HLS

Dans l'inspecteur standalone, une playlist `.m3u8` est rendue dans un lecteur
vidéo. Les navigateurs qui savent lire HLS nativement l'utilisent directement.
Pour les autres navigateurs, le bundle prend en charge `hls.js` lorsqu'il est
fourni par l'application hôte ; cette dépendance reste optionnelle. Avec
Symfony AssetMapper, l'application peut l'ajouter à son import map :

```bash
php bin/console importmap:require hls.js
```

Sans `hls.js`, les navigateurs sans prise en charge HLS native affichent un
message explicatif. La lecture des playlists et segments reste soumise à leurs
URLs accessibles et aux règles CORS du serveur qui les héberge.

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
