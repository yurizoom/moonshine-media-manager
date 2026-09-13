[← API и события](api.ru.md) · [English](development.md) | **Русский** · [Back to README](../README.ru.md) · [Старые версии →](legacy-versions.ru.md)

# Разработка

## Тесты

Пакет покрыт PHPUnit + Orchestra Testbench (42 теста: Unit по санитайзерам/валидатору/форматтерам, Feature по JSON-контракту всех эндпоинтов):

```bash
composer install            # один раз (dev-зависимости)
vendor/bin/phpunit          # все тесты
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Feature
```

CI (GitHub Actions) гоняет матрицу PHP 8.2/8.3 автоматически.

## Сборка ассетов

Фронтенд менеджера — vanilla JS + CSS без фреймворков, собирается Vite 6 + lightningcss:

```bash
npm install
npm run build        # разовая сборка в dist/
npm run dev          # watch-режим
```

Готовые файлы появятся в `dist/`. Кастомный Vite-плагин оборачивает итоговый `media-manager.js` в IIFE — бандл изолирован от глобальной области хоста; сохраняйте это поведение при правке `vite.config.js`.

## Публикация в хост-проекте

```bash
php artisan vendor:publish --tag=moonshine-media-manager-assets --force
```

Флаг `--force` перезаписывает опубликованные ассеты свежесобранными.

## Интеграция сторонних пакетов

### Через события

```php
// В ServiceProvider пакета-расширения
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileUploaded;

Event::listen(MediaManagerFileUploaded::class, OptimizeUploadedImage::class);
```

### Через реестр (кнопки в UI менеджера)

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

Доступные методы реестра: `addFileAction` / `addToolbarAction` / `removeFileAction` / `removeToolbarAction` (+ getters). File-действия появляются в таблице (inline) и в сетке (dropdown); toolbar-действия — рядом с Refresh / Upload / New Folder.

## Архитектура пакета

Слои: `routes` → `Controllers` (HTTP/JSON) → `MediaManager` (домен файловых операций) → `Support` (валидация, безопасность, ассеты) + `Fields`/`Pages`/`Components` (UI-адаптеры MoonShine).

Полезный скилл для разработки MoonShine-пакетов — `moonshine-package` (`.opencode/skills/moonshine-package/`).

## See Also

- [API и события](api.ru.md) — полный список эндпоинтов и событий
- [Конфигурация](configuration.ru.md) — z-index токены для CSS хоста
- [Установка](getting-started.ru.md) — публикация ассетов и конфига
