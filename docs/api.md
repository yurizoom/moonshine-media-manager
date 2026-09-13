[← MediaManagerPicker field](picker-field.md) · **English** | [Русский](api.ru.md) · [Back to README](../README.md) · [Development →](development.md)

# API & events

## Routes

All manager operations are AJAX endpoints under the `media` prefix:

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `media/list` | List files and folders (+ navigation, URLs) |
| GET | `media/download` | Download a file (StreamedResponse) |
| POST | `media/upload` | Upload files (multiple) |
| POST | `media/delete` | Delete (bulk supported, `files[]`) |
| POST | `media/move` | Move a file/folder |
| POST | `media/new-folder` | Create a folder |
| POST | `media/replace` | Replace a file at the same path |

Every endpoint checks the Gate ability from the config (when set) and returns JSON. An authorization failure is an HTTP 403 with the same contract (`status: false`) — not Laravel's default page.

## JSON contract

All endpoints — including failing requests (blocked path, missing file, garbage input) — answer with JSON. An HTML error page (HTTP 500) is impossible: manager construction and domain exceptions are caught by a unified boundary in the controller.

Success (`media/list`):

```json
{
    "status": true,
    "files": [
        {"path": "uploads/photo.jpg", "isDir": false, "type": "image", "size": 12345}
    ],
    "navigation": {"breadcrumbs": []},
    "urls": {},
    "path": "/",
    "view": "table"
}
```

Success (mutations):

```json
{"status": true, "message": "moonshine-media-manager::media-manager.uploaded_successfully"}
```

Error (HTTP 400):

```json
{"status": false, "message": "Localized error message"}
```

Authorization failure (HTTP 403):

```json
{"status": false, "message": "Access denied"}
```

In production, unexpected errors are returned as a localized generic message; raw exception messages only in the local environment. Unexpected exceptions are additionally passed to `report()`.

## `media/list` parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `path` | string | Current folder, defaults to `/` |
| `view` | `table`\|`grid` | Display view |
| `types` | array | Type filter (image, video, audio, pdf, ...) — used by the picker |
| `extensions` | array | Extension filter — used by the picker |

## `media/upload` parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `dir` | string | Target folder, defaults to `/` |
| `files` | array | Files to upload (`files[]`) |
| `types` | array | Optional picker type filter — the server rejects out-of-list files (400) |
| `extensions` | array | Optional picker extension filter — the server rejects out-of-list files (400) |

## Events

The package dispatches events for integration with external code:

| Event | When | Parameters |
|-------|------|------------|
| `MediaManagerFileUploaded` | A file was uploaded | `$path, $disk` |
| `MediaManagerFileReplaced` | A file was replaced (Replace) | `$path, $disk` |
| `MediaManagerFileDeleted` | A file was deleted. When a folder is deleted — one event per file inside it (paths with a leading `/`) | `$path, $disk` |

```php
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileUploaded;

protected $listen = [
    MediaManagerFileUploaded::class => [
        GenerateThumbnailListener::class,
    ],
];
```

Or via `Event::listen()` in the extension package's ServiceProvider.

## Extension registry

Third-party packages can add their own actions to the manager UI via `MediaManagerRegistryInterface` (toolbar buttons and per-file actions) — see the integration example in the [development section](development.md).

## Editor.js integration

When `sckatik/moonshine-editorjs` is installed, the Media Manager automatically registers an `mediaImage` block tool for the Editor.js field ("Image from Media Manager"):

- the toolbox gets a new block that opens the media manager offcanvas (images only) via `Alpine.store('mm').open()`;
- the selected image renders in the block and is saved as `{file: {url, path}, caption}`;
- frontend rendering goes through the `moonshine-media-manager::blocks.editorjs-media-image` view, registered in the Editor.js tool registry (`Sckatik\MoonshineEditorJs\Support\EditorJsToolRegistry`);
- the integration is soft — gated by `class_exists()`, no composer dependency.

## See Also

- [Development](development.md) — integration via the registry and asset builds
- [MediaManagerPicker field](picker-field.md) — the field using these endpoints
- [Configuration](configuration.md) — endpoint authorization via Gate
