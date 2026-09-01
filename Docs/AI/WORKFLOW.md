# Workflow for an AI

This workflow must be followed before any major modification.

---

# Step 1 — Identify the type of change

Classify the request:

```text
A. Core Media
B. UI Media Manager
C. Storage
D. Processing
E. CRUD integration
F. Documentation
G. Security
H. Compatibility
```

Do not start coding until the category is identified.

---

# Step 2 — Read the relevant files

Always start with:

```text
AGENTS.md
Docs/en/AI/RULES.md (or Docs/fr/AI/RULES.md)
Docs/en/AI/ARCHITECTURE.md (or Docs/fr/AI/ARCHITECTURE.md)
```

Then read the concerned classes.

Example for modifying the picker:

```text
src/Form/MediaPickerType.php
assets/controllers/neox_media_picker_controller.js
templates/manager/_picker.html.twig
Docs/en/09-media-picker.md
```

---

# Step 3 — Verify invariants

Before the modification, mentally answer:

```text
Does the bundle know a business entity?
Does the modification impose NeoxCrudBundle?
Does the modification impose RabbitMQ?
Does the modification impose Mercure?
Does the modification bypass MediaUrlGenerator?
Does the modification add path/file/url in Product?
Does the modification break Symfony 7?
```

If yes to a forbidden question, revise the approach.

---

# Step 4 — Choose the right extension point

## New storage

Create:

```text
StorageInterface
StorageAdapter
```

Do not directly modify all classes.

## New processing

Create:

```text
ProcessorInterface
XxxProcessor
```

## New embed provider

Create:

```text
EmbedProviderInterface
YoutubeProvider
VimeoProvider
```

## New CRUD integration

Create or extend:

```text
Bridge/
```

---

# Step 5 — Implement in small units

Recommended order:

1. contract/interface;
2. implementation;
3. service;
4. controller if necessary;
5. UI;
6. tests;
7. documentation.

Avoid massive unrelated changes.

---

# Step 6 — Verify Doctrine

If an entity changes:

```bash
php bin/console doctrine:schema:validate
```

Then:

```bash
php bin/console doctrine:migrations:diff
```

Do not manually modify a published old migration.

---

# Step 7 — Verify Symfony

At minimum:

```bash
php bin/console lint:container
php bin/console lint:twig templates/
php bin/console debug:router
php bin/console debug:messenger
```

If available:

```bash
composer validate --strict
```

---

# Step 8 — Verify PHP

```bash
find src -name "*.php" -print0 | xargs -0 -n1 php -l
```

No syntax errors must remain.

---

# Step 9 — Verify the frontend

For a Stimulus modification:

- check the controller name;
- verify targets;
- verify actions;
- verify dispatched events;
- verify asset loading;
- verify console errors.

---

# Step 10 — Verify compatibility

Target matrix:

```text
PHP 8.2 + Symfony 7
PHP 8.3 + Symfony 7
PHP 8.4 + Symfony 7
PHP 8.4 + Symfony 8
PHP 8.5 + Symfony 8
```

Do not add a dependency that makes this matrix inconsistent without an explicit decision.

---

# Step 11 — Documentation

Any public evolution must update at least one page in `Docs/en/` (or `Docs/fr/`).

If the architecture changes:

```text
Docs/en/AI/ARCHITECTURE.md (or Docs/fr/AI/ARCHITECTURE.md)
```

If a rule changes:

```text
Docs/en/AI/RULES.md (or Docs/fr/AI/RULES.md)
```

---

# Step 12 — Expected Final Summary from an AI

When an AI completes a modification, it must provide:

```text
1. what changed
2. main files modified
3. compatibility
4. possible migration
5. possible configuration
6. tests performed
7. remaining optional points
```

Do not just say "it's done".
