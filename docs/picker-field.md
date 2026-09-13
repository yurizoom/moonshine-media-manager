[← Configuration](configuration.md) · **English** | [Русский](picker-field.ru.md) · [Back to README](../README.md) · [API & events →](api.md)

# MediaManagerPicker field

A field for picking files from the manager right inside a form. Works with regular fields, Json and Layouts.

## Basic usage

```php
use YuriZoom\MoonShineMediaManager\Fields\MediaManagerPicker;

// Single image
MediaManagerPicker::make('Image', 'image')
    ->allowedTypes(['image']),

// Multiple selection with drag-and-drop reordering
MediaManagerPicker::make('Gallery', 'images')
    ->multiple()
    ->allowedTypes(['image']),
```

The field requires `MediaManagerOffCanvas` to be wired into the layout (see [Installation](getting-started.md)).

## File filtering

By type or by extension, combinable:

```php
// By type (from the manager): image, video, audio, pdf, word, code, zip, txt, ppt
->allowedTypes(['image'])
->allowedTypes(['image', 'pdf'])

// By extension (precise control):
->allowedExtensions(['jpg', 'png', 'webp'])
->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx'])
```

## With Json

```php
use MoonShine\UI\Fields\Json;

Json::make('Meta', 'meta')
    ->fields([
        Text::make('Title', 'title'),
        MediaManagerPicker::make('Image', 'image')
            ->allowedTypes(['image']),
        MediaManagerPicker::make('Document', 'document')
            ->allowedExtensions(['pdf', 'doc', 'docx']),
        MediaManagerPicker::make('Files', 'files')
            ->multiple()
            ->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx']),
    ]),
```

## With Layouts

```php
use MoonShine\Layouts\Fields\Layouts;

Layouts::make('Content', 'content')
    ->addLayout('Image block', 'image_block', [
        Text::make('Title', 'title'),
        MediaManagerPicker::make('Image', 'image')
            ->allowedTypes(['image']),
    ])
    ->addLayout('Files block', 'files_block', [
        Text::make('Title', 'title'),
        MediaManagerPicker::make('Documents', 'documents')
            ->multiple()
            ->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx']),
    ]),
```

## Behavior

- **Filters enforced on upload** — files uploaded from the picker-opened manager are checked against `allowedTypes`/`allowedExtensions` on the server: a non-matching file is rejected with a 400
- **The picker remembers the folder** — reopening returns to the last visited folder
- **Drag-and-drop reorder** — drag to change the order of selected files
- **WebP/AVIF filter** — for picker fields whose `allowedExtensions` include webp/avif, the "Hide WebP/AVIF" toggle disables itself automatically so those files stay pickable
- **Preview** — in previews (resource tables) the field renders MoonShine's core `Thumbnails`

## See Also

- [API & events](api.md) — endpoints the picker uses under the hood
- [Configuration](configuration.md) — disk, allowed_ext and limits
- [Installation](getting-started.md) — wiring up MediaManagerOffCanvas
