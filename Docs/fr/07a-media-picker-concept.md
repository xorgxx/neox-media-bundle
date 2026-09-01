# 7A. Concept fondamental : l'entité métier ne gère pas l'upload

C'est un point central de NeoxMediaBundle.

Une entité métier comme `Product`, `Article`, `Category` ou `User` ne doit **jamais gérer directement l'upload d'un fichier**.

Elle ne reçoit pas :

```text
UploadedFile
filename
path
url technique
```

Elle reçoit uniquement un objet :

```php
Neox\MediaBundle\Entity\Media
```

---

# Exemple simple : image principale d'un produit

Dans `Product` :

```php
use Neox\MediaBundle\Attribute\MediaField;
use Neox\MediaBundle\Entity\Media;

#[ORM\ManyToOne(targetEntity: Media::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
#[MediaField(
    role: 'cover',
    accept: ['image/*']
)]
private ?Media $cover = null;
```

L'interface du formulaire affiche alors un bouton du type :

```text
Image principale

[ Choisir un média ]
```

Le bouton n'ouvre pas un simple `<input type="file">`.

Il ouvre le **MediaPicker**.

---

# Le MediaPicker

Le MediaPicker affiche les médias déjà connus par l'application.

Exemple :

```text
┌─────────────────────────────────────────────┐
│ Choisir un média                     [ X ]  │
├─────────────────────────────────────────────┤
│ Rechercher...       Images ▼   [ Importer ] │
├─────────────────────────────────────────────┤
│                                             │
│  🖼️    🖼️    🖼️    🖼️    🖼️           │
│                                             │
│  🖼️    🖼️    🖼️    🖼️    🖼️           │
│                                             │
├─────────────────────────────────────────────┤
│                    [ Sélectionner ]         │
└─────────────────────────────────────────────┘
```

L'utilisateur peut alors :

1. choisir un média déjà présent ;
2. importer un nouveau fichier ;
3. attendre que le nouveau média apparaisse dans la grille ;
4. le sélectionner ;
5. valider.

À la fin, le formulaire ne reçoit pas le fichier.

Il reçoit l'objet `Media` sélectionné.

Conceptuellement :

```php
$product->setCover($media);
```

---

# Flux complet

```text
Edition Product
      │
      ▼
[ Choisir un média ]
      │
      ▼
MediaPicker
      │
      ├── sélectionner un média existant
      │
      └── [ Importer ]
              │
              ▼
        MediaUploadController
              │
              ▼
          création Media
              │
              ▼
           VichUploader
              │
              ▼
      nouveau média dans la grille
              │
              ▼
         sélection utilisateur
              │
              ▼
      Product.cover = Media
```

---

# Pourquoi ce modèle est important

Il permet de réutiliser un même média.

Exemple :

```text
Media #42 = logo-neox.webp

Product A ─────┐
Product B ─────┼──> Media #42
Article X ─────┘
```

Le fichier physique n'est importé qu'une fois.

Les entités métier ne font que référencer le même objet `Media`.

---

# Cas d'une galerie

Pour plusieurs médias :

```php
#[ORM\ManyToMany(targetEntity: Media::class)]
#[MediaField(
    role: 'gallery',
    accept: ['image/*', 'video/*'],
    multiple: true,
    sortable: true
)]
private Collection $gallery;
```

L'interface peut afficher :

```text
Galerie

[ + Ajouter des médias ]

┌────────┐ ┌────────┐ ┌────────┐
│ image1 │ │ image2 │ │ image3 │
└────────┘ └────────┘ └────────┘
```

Le bouton :

```text
[ + Ajouter des médias ]
```

ouvre encore le même MediaPicker.

L'utilisateur peut sélectionner plusieurs médias, puis l'application fait conceptuellement :

```php
foreach ($selectedMedia as $media) {
    $product->addMedia($media);
}
```

---

# Le bouton Importer appartient à la médiathèque

C'est une règle importante.

Le bouton `Importer` appartient au **Media Manager / MediaPicker**.

Il n'appartient pas à `Product`.

C'est la médiathèque qui sait :

- recevoir le fichier ;
- créer `Media` ;
- appeler VichUploader ;
- lancer Messenger ;
- afficher le nouveau média ;
- permettre sa sélection.

L'entité métier ne fait qu'attacher le média sélectionné.

---

# FILE, URL, EMBED et REMOTE

Le même principe reste valable pour toutes les sources.

Le MediaPicker pourra à terme proposer :

```text
[ Importer un fichier ]
[ Ajouter une URL ]
[ Ajouter un embed ]
```

Dans tous les cas, l'entité métier reçoit toujours :

```php
Media
```

et jamais le détail technique de la source.

---

# Résumé

Le bon modèle mental est :

```text
Product
   │
   │ référence
   ▼
 Media
   │
   ├── FILE
   ├── URL
   ├── EMBED
   └── REMOTE
```

et non :

```text
Product
   │
   └── upload / path / filename / url
```

[← Intégration dans une entité métier](07-entity-integration.md) · [MediaField →](08-media-field.md)
