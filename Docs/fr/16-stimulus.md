# 16. Stimulus

Contrôleurs fournis :

```text
neox_media_library_controller.js
neox_media_upload_controller.js
neox_media_picker_controller.js
```

## Library

Responsable de :

- chargement de la grille ;
- recherche ;
- filtres ;
- sélection ;
- édition ;
- suppression.

## Upload

Responsable de :

- sélection de fichiers ;
- drag & drop ;
- upload multiple ;
- événements `started`, `uploaded`, `failed`.

## Picker

Responsable de :

- ouverture de la modale Media Manager (SweetAlert2) ;
- récupération de la sélection depuis la modale ;
- mise à jour du champ de formulaire (hidden input) ;
- **drag-and-drop** de fichiers directement dans la zone du widget ;
- **upload direct** via sélecteur de fichiers OS (bouton Upload) ;
- rendu des médias sélectionnés en **grille de cartes** (thumbnail, nom, taille, actions) ;
- actions par carte : télécharger, aperçu, retirer ;
- **toolbar** avec compteur de fichiers, bouton Retirer tout, bouton Upload, bouton Parcourir ;
- **loader overlay** avec spinner et texte dynamique pendant les uploads et chargements ;
- ajout de liens embed (YouTube/Vimeo) via l'API `/embed` avec **avertissement de confirmation** sur les risques de liens externes (lien cassé, suppression, restriction, dépendance tiers) ;
- retrait individuel ou global de la sélection.

### Values

```text
url        String   — URL du picker modal
uploadUrl  String   — URL d'upload
embedUrl   String   — URL d'ajout embed
multiple   Boolean  — Sélection multiple (défaut: false)
mode       String   — Mode d'affichage: 'widget' | 'compact' (défaut: 'widget')
```

### Targets

```text
value, label, zone, empty, preview, loader, loaderText,
fileInput, count, countText, removeAllBtn
```

### Événements DOM gérés

```text
dragover  → neox-media-picker#dragOver
dragleave → neox-media-picker#dragLeave
drop      → neox-media-picker#drop
change    → neox-media-picker#onFileInputChange (sur fileInput)
```

### Événements dispatchés

```text
neox-media-picker:changed  { detail: { ids: number[] } }
```

### Mode compact

Le mode `compact` est un mode d'affichage réduit du widget. Il utilise la **même structure HTML**, le **même contrôleur Stimulus** et les **mêmes targets**. La seule différence est l'ajout de la classe CSS `.neox-media-form-picker-compact` qui réduit les dimensions :

- zone drag-and-drop : padding 8px, icône 24px ;
- grille de cartes : 80px au lieu de 140px ;
- toolbar : boutons et texte plus petits ;
- supporte identiquement multiple, drag-and-drop, upload, médiathèque, retrait.

Aucune logique JS différente — uniquement des overrides CSS.

Les contrôleurs communiquent via des événements Stimulus/DOM afin de limiter leur couplage.

[← Twig](15-twig.md) · [Messenger →](17-messenger.md)
