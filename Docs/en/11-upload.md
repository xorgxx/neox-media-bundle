# 11. Upload and drag & drop

Endpoint:

```text
POST /_neox/media/upload
```

The controller accepts a single file or multiple files.

Multipart example:

```text
files[] = image1.jpg
files[] = image2.png
files[] = brochure.pdf
```

The media type is determined from the main MIME:

```text
image/*  -> IMAGE
video/*  -> VIDEO
audio/*  -> AUDIO
other    -> DOCUMENT
```

After persistence, a `ProcessMediaMessage` is sent to Messenger.

Heavy processing can therefore be executed outside the HTTP request.

[← Media Manager](10-media-manager.md) · [Vich →](12-vich-uploader.md)
