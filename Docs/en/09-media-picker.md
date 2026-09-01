# 9. Forms and MediaPicker

The bundle provides `MediaPickerType`, a custom form type that extends `EntityType` and offers a modal interface to select a media from the library.

## Principle

`MediaPickerType` extends `EntityType` (with `class = Media::class`).

- Rendering uses a `<input type="hidden">` that stores **the ID** of the selected `Media`.
- On hover in the modal, the user chooses a media → JS updates the hidden input with `media.id`.
- On submission, Symfony receives the ID and `EntityType` transforms it into a `Media` entity via `EntityManager::find(Media::class, $id)`.
- The entity is assigned to the business entity property (e.g. `$product->setCover($media)`).

The form never knows the file path, technical URL or embed URL. It works only with IDs and `Media` objects.

## Single Media

```php
use Neox\MediaBundle\Form\MediaPickerType;

$builder->add('cover', MediaPickerType::class, [
    'required' => false,
    'label' => 'Cover image',
    'accept' => ['image/*'],
]);
```

In the business entity:

```php
use Neox\MediaBundle\Attribute\MediaField;
use Neox\MediaBundle\Entity\Media;

#[ORM\ManyToOne(targetEntity: Media::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
#[MediaField(role: 'cover', accept: ['image/*'])]
private ?Media $cover = null;
```

## Multiple Selection

```php
$builder->add('gallery', MediaPickerType::class, [
    'multiple' => true,
    'accept' => ['image/*', 'video/*'],
    'sortable' => true,
]);
```

With `multiple: true`, the hidden input stores a comma-separated list of IDs (e.g. `12,15,22`).

## Available Options

| Option | Type | Default | Description |
|---|---|---|---|
| `accept` | `array` | `[]` | UX filter of accepted media types (e.g. `['image/*']`, `['video/*']`). Does not replace server-side validation. |
| `multiple` | `bool` | `false` | Allows multiple selection. |
| `sortable` | `bool` | `false` | Enables drag & drop to reorder media (in multiple mode). |
| `mode` | `string` | `'widget'` | Display mode: `widget` (full) or `compact` (reduced). |
| `class` | `string` | `Media::class` | Inherited from `EntityType`. Do not modify. |
| `choice_label` | `callable` | auto | Inherited from `EntityType`. Displays the media title or name. |
| `placeholder` | `string` | `'Choose a media'` | Text displayed when no media is selected. |

## Stimulus Targets

| Target | Element | Role |
|---|---|---|
| `value` | `<input type="hidden">` | Stores the ID or CSV IDs of selected media. |
| `label` | `<span>` | Text displayed in the empty state. |
| `zone` | `<div>` | Drag-and-drop zone. |
| `empty` | `<div>` | Empty state (displayed when no media). |
| `preview` | `<div>` | Media card grid. |
| `loader` | `<div>` | Loader overlay. |
| `loaderText` | `<small>` | Dynamic loader text. |
| `fileInput` | `<input type="file">` | Hidden OS file selector. |
| `count` | `<span>` | Counter container. |
| `countText` | `<span>` | Counter text (e.g. "3 files selected"). |
| `removeAllBtn` | `<button>` | Global remove button (hidden if 0 media). |

## Stimulus Methods

| Method | Trigger | Description |
|---|---|---|
| `connect()` | auto | Initializes the widget, loads previews if initial value. |
| `dragOver(event)` | `dragover` | Adds the `dragover` class to the zone. |
| `dragLeave(event)` | `dragleave` | Removes the `dragover` class. |
| `drop(event)` | `drop` | Uploads dropped files, refreshes preview. |
| `open(event)` | "Browse" button click | Opens the Media Manager modal (SweetAlert2). |
| `triggerUpload(event)` | "Upload" button click | Opens the OS file selector. |
| `onFileInputChange(event)` | `change` on file input | Uploads selected files, refreshes preview. |
| `removeAll(event)` | "Remove" button click | Clears all selection. |
| `_loadPreview(idsStr)` | internal | Fetches media via API, renders cards. |
| `_removeFromPreview(id)` | internal | Removes a media from selection and preview. |
| `_updateCount(count)` | internal | Updates counter and remove button visibility. |
| `_showLoader(text)` | internal | Shows loader with dynamic text. |
| `_hideLoader()` | internal | Hides loader. |
| `_uploadFiles(files, ...)` | internal | Uploads files to `MediaUploadController`. |

## Twig Rendering

The `neox_media_picker_widget` block generates a complete "file manager" interface:

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
                <span data-neox-media-picker-target="label">Drop files here</span>
                <small>or use the buttons below</small>
            </div>
        </div>
        <div class="neox-media-form-picker-grid" data-neox-media-picker-target="preview" hidden></div>
        <div class="neox-media-form-picker-loader" data-neox-media-picker-target="loader" hidden>
            <div class="neox-media-form-picker-spinner"></div>
            <small data-neox-media-picker-target="loaderText">Loading...</small>
        </div>
    </div>
    <div class="neox-media-form-picker-toolbar">
        <span class="neox-media-form-picker-count">
            <svg>...</svg>
            <span data-neox-media-picker-target="countText">No file selected</span>
        </span>
        <div class="neox-media-form-picker-toolbar-actions">
            <button data-neox-media-picker-target="removeAllBtn"
                    data-action="neox-media-picker#removeAll" hidden>Remove</button>
            <button data-action="neox-media-picker#triggerUpload">Upload</button>
            <button data-action="neox-media-picker#open">Browse...</button>
        </div>
    </div>
</div>
```

### Drag-and-drop zone

The `.neox-media-form-picker-zone` area accepts file drag-and-drop directly from the OS. The `dragover`, `dragleave` and `drop` events are handled by the Stimulus controller.

- `dragover`: adds the `dragover` class (accented border, soft background).
- `dragleave`: removes the `dragover` class.
- `drop`: uploads files via `_uploadFiles()`, then refreshes the preview.

### Card grid (preview)

Selected media are displayed in `.neox-media-form-picker-grid` as cards:

- thumbnail (image, embed icon, file icon);
- drag handle (reordering in multiple mode);
- hover actions: download, preview, remove;
- filename and size.

### Toolbar

The `.neox-media-form-picker-toolbar` contains:

- a selected file counter;
- **Remove** button (clears all selection);
- **Upload** button (opens OS file selector);
- **Browse...** button (opens Media Manager modal).

### Loader

A `.neox-media-form-picker-loader` overlay with spinner is displayed during:

- file uploads (drag-and-drop or OS selector);
- media preview loading.

### CSS and host theme protection

The widget CSS (`public/media-manager.css`) uses `!important` rules on structural properties (display, width, height, background, border, visibility) to resist global CSS resets from the host application. SVGs are particularly protected (`display: block`, `visibility: visible`, explicit dimensions).

If the host theme continues to override styles, consider isolation via Shadow DOM or iframe.

### Asset installation

Bundle assets must be installed as **hard copies** (no symlink) to avoid 403 Forbidden errors:

```bash
php bin/console assets:install --env=dev
```

Re-run this command after each modification of the bundle's CSS or templates.

The Stimulus controller `neox-media-picker` handles:
- Opening the modal (SweetAlert2)
- Selecting media in the grid
- Uploading files without closing the modal
- Direct upload via drag-and-drop or OS selector
- Adding YouTube/Vimeo links via the `/embed` API (with warning about external link risks)
- Updating the hidden input and label
- Rendering media cards with thumbnails and actions
- File counter and global remove button
- Showing/hiding the loader during asynchronous operations
- Individual removal of a media from the selection
- Global removal of all media (`removeAll`)

## Retrieval in the controller

```php
if ($form->isSubmitted() && $form->isValid()) {
    $data = $form->getData();
    $media = $data['cover']; // Media instance or null
    
    $product->setCover($media);
    $em->persist($product);
    $em->flush();
}
```

## Display in Twig

See [Twig functions](15-twig.md) to display a media:

```twig
{# Raw URL #}
{{ neox_media_url(product.cover) }}

{# Filtered thumbnail (LiipImagine for FILE, metadata for EMBED) #}
{{ neox_media_filter(product.cover, 'neox_media_thumb') }}

{# Complete HTML (img, video, audio, iframe for embed) #}
{{ neox_media_render(product.cover) }}
```

## Compact mode

The `compact` mode offers a reduced widget display, ideal for dense forms or secondary fields. It keeps **all features** of the `widget` mode (drag-and-drop, upload, library, multiple, removal) with reduced dimensions:

- Drag-and-drop zone: reduced padding, 24px icon
- Card grid: 80px instead of 140px
- Toolbar: more compact buttons and text

```php
$builder->add('cover', MediaPickerType::class, [
    'accept' => ['image/*'],
    'mode' => 'compact',
]);
```

The `compact` mode uses the same HTML structure, the same Stimulus controller and the same targets. The only difference is the addition of the CSS class `.neox-media-form-picker-compact` which overrides dimensions via dedicated CSS rules.

## External link warning

When a user adds a link-type media (URL or embed), a confirmation dialog appears before submission, warning about the risks:

- The link may become **broken** if the resource is moved
- The content may be **deleted** by the origin platform
- Access may be **restricted** (geoblocking, private, removed)
- Dependency on a **third-party** service not controlled

The user must confirm to proceed with the addition.

[← MediaField](08-media-field.md) · [Media Manager →](10-media-manager.md)
