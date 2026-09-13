[← Поле MediaManagerPicker](picker-field.md) · [Back to README](../README.md) · [Разработка →](development.md)

# API и события

## Маршруты

Все операции менеджера — AJAX-эндпоинты с префиксом `media`:

| Метод | Маршрут | Назначение |
|-------|---------|-----------|
| GET | `media/list` | Список файлов и папок (+ навигация, URL) |
| GET | `media/download` | Скачивание файла (StreamedResponse) |
| POST | `media/upload` | Загрузка файлов (множественная) |
| POST | `media/delete` | Удаление (в т.ч. массовое, `files[]`) |
| POST | `media/move` | Перемещение файла/папки |
| POST | `media/new-folder` | Создание папки |
| POST | `media/replace` | Замена файла по тому же пути |

Каждый эндпоинт проверяет Gate ability из конфига (если задан) и возвращает JSON.

## JSON-контракт

Успех (`media/list`):

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

Успех (мутации):

```json
{"status": true, "message": "moonshine-media-manager::media-manager.uploaded_successfully"}
```

Ошибка (HTTP 400):

```json
{"status": false, "message": "Локализованное сообщение об ошибке"}
```

В production неожидаемые ошибки отдаются как локализованный generic-текст; сырые сообщения исключений — только в local-окружении. Неожидаемые исключения дополнительно уходят в `report()`.

## Параметры `media/list`

| Параметр | Тип | Описание |
|----------|-----|----------|
| `path` | string | Текущая папка, по умолчанию `/` |
| `view` | `table`\|`grid` | Вид отображения |
| `types` | array | Фильтр по типам (image, video, audio, pdf, ...) — использует picker |
| `extensions` | array | Фильтр по расширениям — использует picker |

## События

Пакет диспатчит события для интеграции с внешним кодом:

| Событие | Когда | Параметры |
|---------|-------|-----------|
| `MediaManagerFileUploaded` | Файл загружен | `$path, $disk` |
| `MediaManagerFileReplaced` | Файл заменён (Replace) | `$path, $disk` |
| `MediaManagerFileDeleted` | Файл удалён | `$path, $disk` |

```php
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileUploaded;

protected $listen = [
    MediaManagerFileUploaded::class => [
        GenerateThumbnailListener::class,
    ],
];
```

Или через `Event::listen()` в ServiceProvider пакета-расширения.

## Реестр расширений

Сторонние пакеты могут добавлять свои действия в UI менеджера через `MediaManagerRegistryInterface` (кнопки в тулбаре и per-file действия) — см. пример интеграции в [разделе разработки](development.md).

## See Also

- [Разработка](development.md) — интеграция через реестр и сборка ассетов
- [Поле MediaManagerPicker](picker-field.md) — поле, использующее эти эндпоинты
- [Конфигурация](configuration.md) — авторизация эндпоинтов через Gate
