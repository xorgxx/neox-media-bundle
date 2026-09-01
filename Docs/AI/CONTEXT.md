# Functional Context for AI Assistants

## Why this bundle exists

The same media need recurs across multiple modules:

```text
Product
Article
User
Event
Category
Recipe
...
```

Without a generic bundle, each entity ends up with its own logic:

```text
upload
filename
path
thumbnail
delete
validation
picker
```

NeoxMediaBundle centralizes this logic.

---

# Core Concept

A media is not necessarily a local file.

It can be:

```text
FILE
URL
EMBED
REMOTE
```

Therefore the business entity must never assume its location.

---

# Conceptual Example

```php
$product->getCover()
```

returns:

```php
?Media
```

The calling code must not know whether `Media` corresponds to:

```text
/uploads/a.jpg
https://cdn.example.com/a.jpg
YouTube
S3
R2
```

---

# Media Manager

The Media Manager is the application's shared library.

It must be usable:

```text
standalone
from a FormType
from a CRUD
from a modal
```

---

# Collections

A `MediaCollection` organizes the media library.

It must not be confused with a business relation.

Example:

```text
Collection "Product Photos"
```

is a library organization.

Whereas:

```text
Product.gallery
```

is a business relation.

---

# Asynchronous Processing

The HTTP download must remain fast.

Expensive operations must be able to go through Messenger:

```text
EXIF
heavy thumbnail
FFmpeg
PDF preview
indexing
```

---

# Philosophy

The bundle must prioritize:

```text
abstraction
loose coupling
extensibility
compatibility
reusability
```

over shortcuts specific to a single application.
