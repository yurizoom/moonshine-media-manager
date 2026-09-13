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

Каждый эндпоинт проверяет Gate ability из конфига (если задан) и возвращает JSON. Отказ авторизации — HTTP 403 с тем же контрактом (`status: false`), а не стандартная страница Laravel.

## JSON-контракт

Все эндпоинты — включая ошибочные запросы (заблокированный путь, несуществующий файл, мусорный input) — отвечают JSON. HTML-страница ошибки (HTTP 500) невозможна: конструирование менеджера и доменные исключения перехватываются единым контуром в контроллере.

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

Отказ авторизации (HTTP 403):

```json
{"status": false, "message": "Доступ запрещён"}
```

В production неожидаемые ошибки отдаются как локализованный generic-текст; сырые сообщения исключений — только в local-окружении. Неожидаемые исключения дополнительно уходят в `report()`.

## Параметры `media/list`

| Параметр | Тип | Описание |
|----------|-----|----------|
| `path` | string | Текущая папка, по умолчанию `/` |
| `view` | `table`\|`grid` | Вид отображения |
| `types` | array | Фильтр по типам (image, video, audio, pdf, ...) — использует picker |
| `extensions` | array | Фильтр по расширениям — использует picker |

## Параметры `media/upload`

| Параметр | Тип | Описание |
|----------|-----|----------|
| `dir` | string | Целевая папка, по умолчанию `/` |
| `files` | array | Загружаемые файлы (`files[]`) |
| `types` | array | Необязательный picker-фильтр по типам — сервер отклонит файл вне списка (400) |
| `extensions` | array | Необязательный picker-фильтр по расширениям — сервер отклонит файл вне списка (400) |

## События

Пакет диспатчит события для интеграции с внешним кодом:

| Событие | Когда | Параметры |
|---------|-------|-----------|
| `MediaManagerFileUploaded` | Файл загружен | `$path, $disk` |
| `MediaManagerFileReplaced` | Файл заменён (Replace) | `$path, $disk` |
| `MediaManagerFileDeleted` | Файл удалён. При удалении папки — по событию на каждый файл внутри неё (пути с ведущим `/`) | `$path, $disk` |

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
