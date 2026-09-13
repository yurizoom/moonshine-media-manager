# MoonShine Media Manager

[English](README.md) | **Русский**

Файловый менеджер для [MoonShine](https://moonshine-laravel.com/).

### Поддержка версий

| MoonShine | Пакет | Документация                        |
|-----------|-------|-------------------------------------|
| 4.0+      | 4.x   | [Ниже ↓](#установка) + [docs/](docs/) |
| 4.0+      | 3.x   | [docs/legacy-versions.md](docs/legacy-versions.md) |
| 3.0+      | 2.x   | [docs/legacy-versions.md](docs/legacy-versions.md) |
| 2.0+      | 1.x   |                                     |

## Скриншоты

<table>
    <tr>
        <td align="center"><b>Менеджер</b></td>
        <td align="center"><b>Пикер</b></td>
    </tr>
    <tr>
        <td><img src="blob/manager.jpg" alt="Media Manager" width="400"/></td>
        <td><img src="blob/picker.jpg" alt="Media Manager Picker" width="400"/></td>
    </tr>
</table>

## Установка

```bash
composer require yurizoom/moonshine-media-manager
php artisan vendor:publish --tag=moonshine-media-manager-assets
php artisan vendor:publish --tag=moonshine-media-manager-config
```

Подключите OffCanvas в layout и — опционально — пункт меню: [Установка и быстрый старт](docs/getting-started.ru.md).

## Пример: picker-поле в форме

```php
use YuriZoom\MoonShineMediaManager\Fields\MediaManagerPicker;

// Одно изображение
MediaManagerPicker::make('Изображение', 'image')
    ->allowedTypes(['image']),

// Множественный выбор
MediaManagerPicker::make('Галерея', 'images')
    ->multiple()
    ->allowedTypes(['image']),
```

Работает с обычными полями, Json и Layouts — [подробнее](docs/picker-field.ru.md).

## Возможности v4

- **AJAX навигация** — переход по папкам без перезагрузки
- **Поиск / фильтр / сортировка** — мгновенный поиск по имени, фильтр по типу, сортировка по имени / дате / размеру
- **Скрытие конвертированных форматов** — кнопка «Скрыть WebP/AVIF», состояние в localStorage
- **Загрузка файлов** — множественная, с проверкой MIME, расширения и размера; drag-and-drop в модалке загрузки
- **Replace file** — перезапись файла по тому же пути (URL не ломается)
- **Move file** — перемещение через folder browser
- **Создание папок / Переименование / Bulk delete / Удаление / Скачивание**
- **URL файла** — просмотр ссылки с копированием
- **Inline-валидация** — ошибки прямо в модалках, всё локализовано (ru/en)
- **Два вида** — таблица и сетка (grid)
- **Ховер строк таблицы** — подсветка с поддержкой тёмной темы
- **Позиция после удаления** — список скроллится к ближайшему оставшемуся файлу и подсвечивает его
- **Пикер запоминает папку** — при повторном открытии возвращается в последнюю
- **Ленивый offcanvas** — страницы админки, где менеджер не открывали, не шлют лишних запросов
- **Lazy-load** — превью загружаются только при скролле
- **Cache-busting** — после Replace браузер автоматически обновляет изображение
- **Picker-поле** — выбор файлов из менеджера прямо в форме (multiple, фильтры действуют и на загрузку, drag-and-drop reorder)
- **Layouts / Json** — полная интеграция с moonshine/layouts-field и Json-полями
- **Расширяемость** — события (Uploaded/Replaced/Deleted — по событию на каждый файл, даже при удалении папки) и реестр действий для сторонних пакетов
- **Гарантированные слои модальных окон** — менеджер всегда выше чужих оверлеев ([z-index](docs/configuration.ru.md#слои-модальных-окон-z-index))
- **Тесты** — 42 теста (PHPUnit + Testbench), CI-матрица PHP 8.2/8.3

## Документация

Гайды доступны на русском и английском (`*.md` — английская версия).

| Guide | Описание |
|-------|----------|
| [Установка и быстрый старт](docs/getting-started.ru.md) · [EN](docs/getting-started.md) | Публикация, OffCanvas, меню, проверка |
| [Конфигурация](docs/configuration.ru.md) · [EN](docs/configuration.md) | Параметры, ENV, авторизация, z-index слои |
| [Поле MediaManagerPicker](docs/picker-field.ru.md) · [EN](docs/picker-field.md) | Фильтрация, Json, Layouts, поведение |
| [API и события](docs/api.ru.md) · [EN](docs/api.md) | Маршруты, JSON-контракт, события, реестр |
| [Разработка](docs/development.ru.md) · [EN](docs/development.md) | Сборка ассетов, интеграция пакетов |
| [Старые версии](docs/legacy-versions.ru.md) · [EN](docs/legacy-versions.md) | Настройка v3 (MoonShine 4) и v2 (MoonShine 3) |

## Лицензия

[The MIT License (MIT)](LICENSE).
