# 16. Stimulus

Provided controllers:

```text
neox_media_library_controller.js
neox_media_upload_controller.js
neox_media_picker_controller.js
```

## Library

Responsible for:

- grid loading;
- search;
- filters;
- selection;
- editing;
- deletion.

## Upload

Responsible for:

- file selection;
- drag & drop;
- multiple upload;
- `started`, `uploaded`, `failed` events.

## Picker

Responsible for:

- opening the Media Manager modal (SweetAlert2);
- retrieving selection from the modal;
- updating the form field (hidden input);
- **drag-and-drop** of files directly into the widget zone;
- **direct upload** via OS file selector (Upload button);
- rendering selected media as a **card grid** (thumbnail, name, size, actions);
- per-card actions: download, preview, remove;
- **toolbar** with file counter, Remove all button, Upload button, Browse button;
- **loader overlay** with spinner and dynamic text during uploads and loading;
- adding embed links (YouTube/Vimeo) via the `/embed` API with **confirmation warning** about external link risks (broken link, deletion, restriction, third-party dependency);
- individual or global removal from the selection.

### Values

```text
url        String   — Modal picker URL
uploadUrl  String   — Upload URL
embedUrl   String   — Embed addition URL
multiple   Boolean  — Multiple selection (default: false)
mode       String   — Display mode: 'widget' | 'compact' (default: 'widget')
```

### Targets

```text
value, label, zone, empty, preview, loader, loaderText,
fileInput, count, countText, removeAllBtn
```

### Handled DOM Events

```text
dragover  → neox-media-picker#dragOver
dragleave → neox-media-picker#dragLeave
drop      → neox-media-picker#drop
change    → neox-media-picker#onFileInputChange (on fileInput)
```

### Dispatched Events

```text
neox-media-picker:changed  { detail: { ids: number[] } }
```

### Compact Mode

The `compact` mode is a reduced widget display mode. It uses the **same HTML structure**, the **same Stimulus controller** and the **same targets**. The only difference is the addition of the CSS class `.neox-media-form-picker-compact` which reduces dimensions:

- drag-and-drop zone: 8px padding, 24px icon;
- card grid: 80px instead of 140px;
- toolbar: smaller buttons and text;
- fully supports multiple, drag-and-drop, upload, library, removal.

No different JS logic — only CSS overrides.

Controllers communicate via Stimulus/DOM events to limit their coupling.

[← Twig](15-twig.md) · [Messenger →](17-messenger.md)
