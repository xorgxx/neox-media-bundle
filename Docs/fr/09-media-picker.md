# 9. Formulaires et MediaPicker

Le bundle fournit `MediaPickerType`, un type de formulaire personnalisé qui étend `EntityType` et propose une interface modale pour sélectionner un média dans la médiathèque.

## Principe

`MediaPickerType` étend `EntityType` (avec `class = Media::class`).

- Le rendu utilise un `<input type="hidden">` qui stocke **l'ID** du `Media` sélectionné.
- Au survol de la modale, l'utilisateur choisit un média → le JS met à jour le hidden input avec `media.id`.
- À la soumission, Symfony reçoit l'ID et `EntityType` le transforme en entité `Media` via `EntityManager::find(Media::class, $id)`.
- L'entité est assignée à la propriété de l'entité métier (ex: `$product->setCover($media)`).

Le formulaire ne connaît jamais le chemin du fichier, l'URL technique ou l'embed URL. Il travaille uniquement avec des identifiants et des objets `Media`.

## Média unique

```php
use Neox\MediaBundle\Form\MediaPickerType;

$builder->add('cover', MediaPickerType::class, [
    'required' => false,
    'label' => 'Image de couverture',
    'accept' => ['image/*'],
]);
```

Dans l'entité métier :

```php
use Neox\MediaBundle\Attribute\MediaField;
use Neox\MediaBundle\Entity\Media;

#[ORM\ManyToOne(targetEntity: Media::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
#[MediaField(role: 'cover', accept: ['image/*'])]
private ?Media $cover = null;
```

## Sélection multiple

```php
$builder->add('gallery', MediaPickerType::class, [
    'multiple' => true,
    'accept' => ['image/*', 'video/*'],
    'sortable' => true,
]);
```

Avec `multiple: true`, le hidden input stocke une liste d'IDs séparés par des virgules (ex: `12,15,22`).

## Options disponibles

| Option | Type | Défaut | Description |
|---|---|---|---|
| `accept` | `array` | `[]` | Filtre UX des types de média acceptés (ex: `['image/*']`, `['video/*']`). Ne remplace pas la validation serveur. |
| `multiple` | `bool` | `false` | Autorise la sélection multiple. |
| `sortable` | `bool` | `false` | Active le drag & drop pour réordonner les médias (en mode multiple). |
| `mode` | `string` | `'widget'` | Mode d'affichage : `widget` (complet) ou `compact` (réduit). |
| `class` | `string` | `Media::class` | Hérité d'`EntityType`. Ne pas modifier. |
| `choice_label` | `callable` | auto | Hérité d'`EntityType`. Affiche le titre ou le nom du média. |
| `placeholder` | `string` | `'Choisir un média'` | Texte affiché quand aucun média n'est sélectionné. |

## Targets Stimulus

| Target | Élément | Rôle |
|---|---|---|
| `value` | `<input type="hidden">` | Stocke l'ID ou les IDs CSV des médias sélectionnés. |
| `label` | `<span>` | Texte affiché dans l'empty state. |
| `zone` | `<div>` | Zone drag-and-drop. |
| `empty` | `<div>` | Empty state (affiché quand aucun média). |
| `preview` | `<div>` | Grille de cartes média. |
| `loader` | `<div>` | Overlay loader. |
| `loaderText` | `<small>` | Texte dynamique du loader. |
| `fileInput` | `<input type="file">` | Sélecteur de fichiers OS caché. |
| `count` | `<span>` | Conteneur du compteur. |
| `countText` | `<span>` | Texte du compteur (ex: "3 fichiers sélectionnés"). |
| `removeAllBtn` | `<button>` | Bouton de retrait global (hidden si 0 média). |

## Méthodes Stimulus

| Méthode | Trigger | Description |
|---|---|---|
| `connect()` | auto | Initialise le widget, charge les previews si valeur initiale. |
| `dragOver(event)` | `dragover` | Ajoute la classe `dragover` sur la zone. |
| `dragLeave(event)` | `dragleave` | Retire la classe `dragover`. |
| `drop(event)` | `drop` | Upload les fichiers déposés, rafraîchit la preview. |
| `open(event)` | clic bouton "Parcourir" | Ouvre la modale Media Manager (SweetAlert2). |
| `triggerUpload(event)` | clic bouton "Upload" | Ouvre le sélecteur de fichiers OS. |
| `onFileInputChange(event)` | `change` sur file input | Upload les fichiers choisis, rafraîchit la preview. |
| `removeAll(event)` | clic bouton "Retirer" | Efface toute la sélection. |
| `_loadPreview(idsStr)` | interne | Fetch les médias via l'API, rend les cartes. |
| `_removeFromPreview(id)` | interne | Retire un média de la sélection et de la preview. |
| `_updateCount(count)` | interne | Met à jour le compteur et la visibilité du bouton Retirer. |
| `_showLoader(text)` | interne | Affiche le loader avec un texte dynamique. |
| `_hideLoader()` | interne | Masque le loader. |
| `_uploadFiles(files, ...)` | interne | Upload les fichiers vers `MediaUploadController`. |

## Rendu Twig

Le block `neox_media_picker_widget` génère une interface complète de type "file manager" :

```html
<div class="neox-media-form-picker" data-controller="neox-media-picker" ...>
    <input type="hidden" name="product[cover]" value="12" data-neox-media-picker-target="value">
    <input type="file" hidden data-neox-media-picker-target="fileInput"
           data-action="change->neox-media-picker#onFileInputChange">
    <div class="neox-media-form-picker-zone" data-neox-media-picker-target="zone">
        <div class="neox-media-form-picker-empty" data-neox-media-picker-target="empty">
            <div class="neox-media-form-picker-icon-big">
                <svg>...</svg>
            </div>
            <div class="neox-media-form-picker-empty-text">
                <span data-neox-media-picker-target="label">Glissez des fichiers ici</span>
                <small>ou utilisez les boutons ci-dessous</small>
            </div>
        </div>
        <div class="neox-media-form-picker-grid" data-neox-media-picker-target="preview" hidden></div>
        <div class="neox-media-form-picker-loader" data-neox-media-picker-target="loader" hidden>
            <div class="neox-media-form-picker-spinner"></div>
            <small data-neox-media-picker-target="loaderText">Chargement...</small>
        </div>
    </div>
    <div class="neox-media-form-picker-toolbar">
        <span class="neox-media-form-picker-count">
            <svg>...</svg>
            <span data-neox-media-picker-target="countText">Aucun fichier sélectionné</span>
        </span>
        <div class="neox-media-form-picker-toolbar-actions">
            <button data-neox-media-picker-target="removeAllBtn"
                    data-action="neox-media-picker#removeAll" hidden>Retirer</button>
            <button data-action="neox-media-picker#triggerUpload">Upload</button>
            <button data-action="neox-media-picker#open">Parcourir...</button>
        </div>
    </div>
</div>
```

### Zone drag-and-drop

La zone `.neox-media-form-picker-zone` accepte le drag-and-drop de fichiers directement depuis l'OS. Les événements `dragover`, `dragleave` et `drop` sont gérés par le contrôleur Stimulus.

- `dragover` : ajoute la classe `dragover` (bordure accentuée, fond soft).
- `dragleave` : retire la classe `dragover`.
- `drop` : upload les fichiers via `_uploadFiles()`, puis rafraîchit la prévisualisation.

### Grille de cartes (preview)

Les médias sélectionnés sont affichés dans `.neox-media-form-picker-grid` sous forme de cartes :

- thumbnail (image, icône embed, icône fichier) ;
- drag handle (réordonnancement en mode multiple) ;
- actions sur hover : télécharger, aperçu, retirer ;
- nom du fichier et taille.

### Toolbar

La toolbar `.neox-media-form-picker-toolbar` contient :

- un compteur de fichiers sélectionnés ;
- bouton **Retirer** (efface toute la sélection) ;
- bouton **Upload** (ouvre le sélecteur de fichiers OS) ;
- bouton **Parcourir...** (ouvre la modale Media Manager).

### Loader

Un overlay `.neox-media-form-picker-loader` avec spinner s'affiche pendant :

- l'upload de fichiers (drag-and-drop ou sélecteur OS) ;
- le chargement des previews média.

### CSS et protection contre le thème hôte

Le CSS du widget (`public/media-manager.css`) utilise des règles `!important` sur les propriétés structurelles (display, width, height, background, border, visibility) pour résister aux resets CSS globaux de l'application hôte. Les SVGs sont particulièrement protégés (`display: block`, `visibility: visible`, dimensions explicites).

Si le thème hôte continue d'écraser les styles, envisager une isolation via Shadow DOM ou iframe.

### Asset installation

Les assets du bundle doivent être installés en **hard copies** (pas de symlink) pour éviter les erreurs 403 Forbidden :

```bash
php bin/console assets:install --env=dev
```

Relancer cette commande après chaque modification du CSS ou des templates du bundle.

Le contrôleur Stimulus `neox-media-picker` gère :
- L'ouverture de la modale (SweetAlert2)
- La sélection de médias dans la grille
- L'upload de fichiers sans fermeture de la modale
- L'upload direct par drag-and-drop ou sélecteur OS
- L'ajout de liens YouTube/Vimeo via l'API `/embed` (avec avertissement sur les risques de liens externes)
- La mise à jour du hidden input et du label
- Le rendu des cartes média avec thumbnails et actions
- Le compteur de fichiers et le bouton de retrait global
- L'affichage/masquage du loader pendant les opérations asynchrones
- Le retrait individuel d'un média de la sélection
- Le retrait global de tous les médias (`removeAll`)

## Récupération dans le controller

```php
if ($form->isSubmitted() && $form->isValid()) {
    $data = $form->getData();
    $media = $data['cover']; // Instance de Media ou null
    
    $product->setCover($media);
    $em->persist($product);
    $em->flush();
}
```

## Affichage en Twig

Voir [Twig functions](15-twig.md) pour afficher un média :

```twig
{# URL brute #}
{{ neox_media_url(product.cover) }}

{# Thumbnail filtrée (LiipImagine pour FILE, metadata pour EMBED) #}
{{ neox_media_filter(product.cover, 'neox_media_thumb') }}

{# HTML complet (img, video, audio, iframe pour embed) #}
{{ neox_media_render(product.cover) }}
```

## Mode compact

Le mode `compact` offre un affichage réduit du widget, idéal pour les formulaires denses ou les champs secondaires. Il garde **toutes les fonctionnalités** du mode `widget` (drag-and-drop, upload, médiathèque, multiple, retrait) avec des dimensions réduites :

- Zone drag-and-drop : padding réduit, icône 24px
- Grille de cartes : 80px au lieu de 140px
- Toolbar : boutons et texte plus compacts

```php
$builder->add('cover', MediaPickerType::class, [
    'accept' => ['image/*'],
    'mode' => 'compact',
]);
```

Le mode `compact` utilise la même structure HTML, le même contrôleur Stimulus et les mêmes targets. La seule différence est l'ajout de la classe CSS `.neox-media-form-picker-compact` qui override les dimensions via des règles CSS dédiées.

## Avertissement pour les liens externes

Lorsqu'un utilisateur ajoute un média de type lien (URL ou embed), une boîte de dialogue de confirmation s'affiche avant l'envoi, l'avertissant des risques :

- Le lien peut devenir **cassé** si la ressource est déplacée
- Le contenu peut être **supprimé** par la plateforme d'origine
- L'accès peut être **restreint** (géoblocage, privé, supprimé)
- Dépendance à un service **tiers** non contrôlé

L'utilisateur doit confirmer pour poursuivre l'ajout.

[← MediaField](08-media-field.md) · [Media Manager →](10-media-manager.md)
