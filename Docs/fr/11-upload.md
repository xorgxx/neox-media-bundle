# 11. Upload et drag & drop

Endpoint :

```text
POST /_neox/media/upload
```

Le contrôleur accepte un fichier unique ou plusieurs fichiers.

Exemple multipart :

```text
files[] = image1.jpg
files[] = image2.png
files[] = brochure.pdf
```

Le type du média est déterminé à partir du MIME principal :

```text
image/*  -> IMAGE
video/*  -> VIDEO
audio/*  -> AUDIO
autre    -> DOCUMENT
```

Après persistance, un `ProcessMediaMessage` est envoyé à Messenger.

Le traitement lourd peut donc être exécuté hors de la requête HTTP.

[← Media Manager](10-media-manager.md) · [Vich →](12-vich-uploader.md)
