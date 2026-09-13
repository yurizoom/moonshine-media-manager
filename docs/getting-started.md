[Back to README](../README.md) · **English** | [Русский](getting-started.ru.md) · [Configuration →](configuration.md)

# Installation and quick start

## Requirements

- PHP 8.2+
- Laravel with MoonShine 4.0+ installed
- A local Laravel Storage disk (`public` by default)

## Installation

```bash
composer require yurizoom/moonshine-media-manager
```

After installation, publish the assets and the config:

```bash
php artisan vendor:publish --tag=moonshine-media-manager-assets
php artisan vendor:publish --tag=moonshine-media-manager-config
```

## Wiring up the OffCanvas

The manager works through a global offcanvas panel. Add the component in `app/MoonShine/Layouts/MoonShineLayout.php`:

```php
use YuriZoom\MoonShineMediaManager\Components\MediaManagerOffCanvas;

final class MoonShineLayout extends AppLayout
{
    protected function getContentComponents(): array
    {
        return [
            ...parent::getContentComponents(),
            MediaManagerOffCanvas::make(),
        ];
    }
}
```

`MediaManagerOffCanvas` is a global component that renders the offcanvas panel with the file manager. All picker fields on your pages work through it. Assets are loaded automatically by the component — no manual wiring needed.

## Adding a menu item (optional)

When `auto_menu` is enabled (the default), the item appears automatically. For manual placement:

```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
{
    return [
        MenuItem::make(MediaManagerPage::class),
    ];
}
```

## Verifying the installation

1. Open the admin panel — the manager item is in the sidebar (with `auto_menu` enabled)
2. Upload a file via the "Upload" button — it appears in the list
3. The file physically lives on the disk from `config('filesystems.default')` (or from the `disk` setting)

## What's next

- [Configuration](configuration.md) — disk, extensions, limits, authorization, z-index layers
- [MediaManagerPicker field](picker-field.md) — pick files right inside forms
- [API & events](api.md) — AJAX endpoints and integration events

## See Also

- [Configuration](configuration.md) — every config option and env variable
- [MediaManagerPicker field](picker-field.md) — integrating the manager into resource forms
- [Development](development.md) — building assets from source
