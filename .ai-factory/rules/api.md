# API Rules

> Area-specific conventions for api. Loaded after rules/base.md.

## Rules

- Все эндпоинты возвращают единый JSON-контракт `{status: bool, message|files, ...}` — никаких HTML-ответов и других форм
- Первый оператор каждого экшена — guard `authorizeAction()` (Gate ability из конфига)
- Ошибки — HTTP 400 + `{status: false, message}`; доменные ошибки только через `MediaManagerException` с локализованным сообщением
- Неожидаемые `Throwable` — `report()` + локализованный generic-текст наружу; сырые тексты исключений только при `app()->isLocal()`
- Входные параметры request приводятся (`(string)`, `(array)`) до передачи в `MediaManager`
- Работа с файлами — только через `MediaManager` (домен); контроллеры не трогают Storage напрямую
- Загрузки валидируются whitelist'ом расширений + MIME + размером (`MediaValidator`); пути — только через `MediaSecurity`, никаких сырых путей от пользователя в Storage
- Фильтры `types`/`extensions` эндпоинта `media/list` применяются к файлам, но не к директориям (папки видны всегда)
- Новые пользовательские сообщения — ключи в `resources/lang/{en,ru}/media-manager.php` одновременно в оба языка
- Мутации файлов диспатчат события `MediaManagerFileUploaded/Replaced/Deleted` — интеграции строятся на них, а не на хуках контроллеров
- Пагинация/размер ответа: список файлов отдаётся целиком только для папок; для больших директорий учитывать фильтрацию на стороне `media/list`
