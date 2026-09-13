# Примеры: пакеты для MoonShine 4

Рабочие паттерны из production-кода и официальной документации.

## Пример 1: ServiceProvider медиа-менеджера (файловый пакет без БД)

Источник: `src/MediaManagerServiceProvider.php` (сокращённо, типовая структура).

```php
<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager;

use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;

class MediaManagerServiceProvider extends ServiceProvider
{
    public function boot(CoreContract $core): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'moonshine-media-manager');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'moonshine-media-manager');
        $this->loadRoutesFrom(__DIR__.'/../routes/moonshine.php');

        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/moonshine-media-manager'),
        ], 'moonshine-media-manager-assets');

        $this->publishes([
            __DIR__.'/../config/media-manager.php' => config_path('media-manager.php'),
        ], 'moonshine-media-manager-config');

        $core->pages([MediaManagerPage::class]);
        // auto_menu: пункт добавляется только если включён в конфиге
    }
}
```

## Пример 2: конфиг с fallback-цепочкой

Приоритет: standalone `config/media-manager.php` → `config/moonshine.php['media_manager']` → дефолты пакета.

```php
// config/media-manager.php
return [
    'disk' => config('filesystems.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,pdf,doc,docx,zip',
    'max_file_size' => env('MOONSHINE_MEDIA_MANAGER_MAX_FILE_SIZE', 10 * 1024 * 1024),
    'rename_duplicates' => env('MOONSHINE_MEDIA_MANAGER_RENAME_DUPLICATES', true),
    'auto_menu' => env('MOONSHINE_MEDIA_MANAGER_AUTO_MENU', true),
    'ability' => env('MOONSHINE_MEDIA_MANAGER_ABILITY'),
    'default_view' => 'table',
];
```

Чтение: `config('moonshine.media_manager.disk', 'public')` — mergeConfigFrom мержит standalone-файл в `moonshine.*` при отсутствии.

## Пример 3: маршруты пакета

```php
// routes/moonshine.php
use Illuminate\Support\Facades\Route;
use YuriZoom\MoonShineMediaManager\Controllers\MediaManagerController;

Route::prefix('media')->group(function (): void {
    Route::get('list', [MediaManagerController::class, 'index']);
    Route::get('download', [MediaManagerController::class, 'download']);
    Route::post('upload', [MediaManagerController::class, 'upload']);
    Route::post('delete', [MediaManagerController::class, 'delete']);
    Route::post('move', [MediaManagerController::class, 'move']);
    Route::post('new-folder', [MediaManagerController::class, 'newFolder']);
    Route::post('replace', [MediaManagerController::class, 'replace']);
});
```

## Пример 4: JSON-контракт AJAX-эндпоинта

Успех:

```json
{"status": true, "message": "moonshine-media-manager::media-manager.uploaded_successfully"}
```

Список файлов:

```json
{
    "status": true,
    "files": [{"path": "...", "isDir": false, "type": "image", "size": 12345}],
    "navigation": {...},
    "urls": {...},
    "path": "/",
    "view": "table"
}
```

Ошибка (HTTP 400):

```json
{"status": false, "message": "Локализованное сообщение или generic в production"}
```

## Пример 5: доменное событие

```php
<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaManagerFileUploaded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $path,
        public readonly string $disk,
    ) {}
}
```

## Пример 6: интеграция пакета-потребителя через реестр

```php
// В ServiceProvider пакета-расширения (moonshine-image-editor)
private function registerMediaManagerIntegration(): void
{
    $this->app->resolving(
        MediaManagerRegistryInterface::class,
        function (MediaManagerRegistryInterface $registry): void {
            $registry->addFileAction('image-editor', [
                'icon' => 'sparkles',
                'class' => 'btn-sm btn-accent',
                'label' => __('image-editor::image-editor.edit_image'),
                'x-show' => '!file.isDir && file.type === "image"',
                'click' => '$store.ic.open(file)',
            ]);
        }
    );
}

// События хоста → слушатели расширения
Event::listen(MediaManagerFileUploaded::class, OptimizeUploadedImage::class);
Event::listen(MediaManagerFileDeleted::class, DeleteImageConversions::class);
```

## Пример 7: offcanvas-компонент в layout хоста

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

Ассеты загружаются автоматически через компонент — layout хоста не трогает пути пакета.

## Пример 8: сборка ассетов пакета (Vite + IIFE-обёртка)

```js
// vite.config.js — плагин оборачивает итоговый бандл в IIFE,
// чтобы пакетный JS не конфликтовал с глобальной областью хоста
const packageBuildPlugin = () => ({
    name: 'media-manager-build-plugin',
    async closeBundle() {
        const filePath = 'dist/media-manager.js';
        const data = readFileSync(filePath);
        writeFileSync(filePath, Buffer.from('(()=>{'));
        writeFileSync(filePath, data, { flag: 'a' });
        await fsPromises.appendFile(filePath, '})()');
    },
});
```

Публикация в хост: `php artisan vendor:publish --tag=moonshine-media-manager-assets --force`
