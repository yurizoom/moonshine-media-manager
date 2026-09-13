[← API & events](api.md) · **English** | [Русский](development.ru.md) · [Back to README](../README.md) · [Legacy versions →](legacy-versions.md)

# Development

## Tests

The package is covered by PHPUnit + Orchestra Testbench (42 tests: Unit for sanitizers/validator/formatters, Feature for the JSON contract of every endpoint):

```bash
composer install            # once (dev dependencies)
vendor/bin/phpunit          # all tests
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Feature
```

CI (GitHub Actions) runs a PHP 8.2/8.3 matrix automatically.

## Building assets

The manager frontend is framework-free vanilla JS + CSS, built with Vite 6 + lightningcss:

```bash
npm install
npm run build        # one-off build into dist/
npm run dev          # watch mode
```

The built files land in `dist/`. A custom Vite plugin wraps the final `media-manager.js` into an IIFE — the bundle stays isolated from the host's global scope; preserve this behavior when editing `vite.config.js`.

## Publishing to the host project

```bash
php artisan vendor:publish --tag=moonshine-media-manager-assets --force
```

The `--force` flag overwrites the published assets with the freshly built ones.

## Third-party package integration

### Via events

```php
// In the extension package's ServiceProvider
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileUploaded;

Event::listen(MediaManagerFileUploaded::class, OptimizeUploadedImage::class);
```

### Via the registry (buttons in the manager UI)

```php
use YuriZoom\MoonShineMediaManager\Contracts\MediaManagerRegistryInterface;

$this->app->resolving(
    MediaManagerRegistryInterface::class,
    function (MediaManagerRegistryInterface $registry): void {
        $registry->addFileAction('my-package', [
            'icon' => 'sparkles',
            'class' => 'btn-sm btn-accent',
            'label' => __('my-package::ui.edit_file'),
            'x-show' => '!file.isDir && file.type === "image"',
            'click' => '$store.myPackage.open(file)',
        ]);
    }
);
```

Available registry methods: `addFileAction` / `addToolbarAction` / `removeFileAction` / `removeToolbarAction` (+ getters). File actions appear in the table (inline) and in the grid (dropdown); toolbar actions sit next to Refresh / Upload / New Folder.

## Package architecture

Layers: `routes` → `Controllers` (HTTP/JSON) → `MediaManager` (file operations domain) → `Support` (validation, security, assets) + `Fields`/`Pages`/`Components` (MoonShine UI adapters).

A useful skill for developing MoonShine packages is `moonshine-package` (`.opencode/skills/moonshine-package/`).

## See Also

- [API & events](api.md) — the full list of endpoints and events
- [Configuration](configuration.md) — z-index tokens for the host's CSS
- [Installation](getting-started.md) — publishing assets and the config
