# 10. Media Manager

Main route:

```text
/admin/media
```

The standalone Media Manager accepts an optional local  `return_url` query parameter, for example /admin/media?return_url=/admin/products. When provided, the top-left Back button uses that URL; otherwise it falls back to browser history.

The Media Manager provides:

- responsive grid;
- search;
- filters;
- preview;
- inspector;
- metadata editing;
- deletion;
- multiple upload;
- drag & drop;
- collections;
- modal picker.

The sidebar displays physical directories as a tree: each folder appears under
its parent, only its name is shown, and opening a branch closes its siblings.
The selected folder stays visible.

Video media can provide a poster URL through `metadata.thumbnail_url`. The
Media Manager and picker use it as the preview, including for HLS playlists.
The bundle does not generate an image from a playlist itself; the host
application can set this metadata when it creates the HLS output.
During synchronization, local HLS playlists are also inspected for variant
qualities/resolutions, codecs, container, and duration when the playlists
provide that information. These values populate `width`, `height`, `duration`,
and `metadata.hls`; they are not guessed from the media segments.

### HLS preview

In the standalone inspector, a `.m3u8` playlist is rendered in a video player.
Browsers with native HLS playback use it directly. For other browsers, the
bundle supports `hls.js` when provided by the host application; this dependency
remains optional. With Symfony AssetMapper, the host can add it to its import
map:

```bash
php bin/console importmap:require hls.js
```

Without `hls.js`, browsers without native HLS support display an explanatory
message. Playback still depends on accessible playlist/segment URLs and the
hosting server's CORS policy.

## Filters

Main categories:

- all;
- images;
- videos;
- audio;
- documents.

## Inspector

When a media is selected, the inspector allows editing:

- title;
- alternative text;
- description.

It also displays available technical information.

[← MediaPicker](09-media-picker.md) · [Upload →](11-upload.md)
