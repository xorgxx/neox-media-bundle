# NeoxMediaBundle Priority Checklist

This checklist tracks the next work items for the bundle after the initial project analysis.

## Documentation

- [x] Fix the AI entry-point documentation path for the architecture guide.
- [ ] Keep `Docs/README.md` updated whenever a public architectural behavior changes.
- [ ] Document how host applications should expose the bundled JavaScript and CSS assets with AssetMapper, Importmap or `assets:install`.
- [ ] Add examples for disabling the bundled routes once the implementation is confirmed.

## Routing and Configuration

- [x] Make `neox_media.routes.enabled` effective, or remove the option if route loading must stay controlled only by host imports.
- [x] Add a test or minimal kernel fixture that proves the bundle routes can be enabled and disabled as documented.
- [x] Clamp API pagination options such as `limit` and `offset` to protect the media library endpoint from very large queries.
- [x] Restrict API sorting to known fields before passing the value to repository queries.

## Security

- [x] Harden external URL validation against SSRF risks: localhost, private IP ranges and link-local ranges. DNS rebinding and redirects still require fetch-time protection.
- [x] Decide whether SVG uploads are acceptable by default. SVG stays allowed but is sandboxed by default together with PDF and ZIP.
- [ ] Add controller tests for invalid CSRF tokens on upload, update, delete, collection and embed endpoints.
- [ ] Validate JSON payload types before assigning fields such as `title`, `alt` and `description`.

## Media Lifecycle

- [ ] Confirm that deleting a `Media` removes its physical FILE asset through VichUploader.
- [ ] Confirm how host entities should handle deleted shared media, especially `ManyToOne` relations.
- [ ] Prevent duplicate media entries inside the same collection, or document that duplicates are intentionally allowed.
- [ ] Add tests for collection add, bulk add, remove, bulk remove and reorder behavior.

## Processing

- [ ] Decide whether `ProcessMediaMessageHandler` should swallow processor exceptions or rethrow them to use Messenger retries.
- [ ] Persist a processing error message or metadata entry when media processing fails.
- [ ] Add tests for processor ordering and partial processor failures.
- [ ] Document the recommended Messenger transport setup, including optional RabbitMQ usage.

## Compatibility

- [ ] Run the test suite against Symfony 7 on PHP 8.2 or 8.3.
- [ ] Run the test suite against Symfony 8 on PHP 8.4 or newer.
- [ ] Verify that NeoxCrudBundle, Mercure, RabbitMQ, FFmpeg and remote storage remain optional.
- [ ] Add CI matrix coverage for supported PHP and Symfony combinations.

## Test Coverage

- [x] Unit tests pass locally with PHPUnit.
- [ ] Add functional tests for upload, media listing, URL creation, embed creation and deletion.
- [ ] Add tests for `MediaUrlGenerator` with FILE, URL, EMBED and REMOTE sources.
- [ ] Add tests for configuration defaults and service wiring.
