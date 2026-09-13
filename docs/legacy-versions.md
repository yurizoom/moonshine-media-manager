[← Development](development.md) · **English** | [Русский](legacy-versions.ru.md) · [Back to README](../README.md)

# Legacy versions (v3 / v2)

## v3 setup (package 3.x, MoonShine 4)

Add to `config/moonshine.php`:

```php
'media_manager' => [
    'auto_menu' => true,
    'disk' => config('filesystem.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,pdf,doc,docx,zip',
    'default_view' => 'table',
],
```

To add a menu item:

```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
{
    return [
        MenuItem::make(new MediaManagerPage()),
    ];
}
```

## v2 setup (package 2.x, MoonShine 3)

To change the settings, add to `config/moonshine.php`:

```php
[
    'media_manager' => [
        // Automatic menu item
        'auto_menu' => true,
        // Root directory
        'disk' => config('filesystem.default', 'public'),
        // File extensions allowed for upload
        'allowed_ext' => 'jpg,jpeg,png,pdf,doc,docx,zip',
        // Default manager view
        'default_view' => 'table',
    ]
]
```

To add a menu item in `app/MoonShine/Layouts/MoonShineLayout.php`:

```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
{
    return [
        MenuItem::make(new MediaManagerPage()),
    ];
}
```

## See Also

- [Installation](getting-started.md) — current v4 setup
- [Configuration](configuration.md) — the full list of v4 options
