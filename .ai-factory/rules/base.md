# Базовые правила проекта

> Автоопределено из анализа кодовой базы. Редактируйте по мере необходимости.

## Именование

- PHP-файлы: PascalCase, имя файла совпадает с именем класса (`MediaManagerPicker.php`)
- Blade-шаблоны и партиалы: kebab-case (`media-manager-offcanvas.blade.php`, `browser-toolbar.blade.php`)
- JS/CSS-файлы: kebab-case (`media-manager.js`, `media-manager.css`)
- Классы: PascalCase; большинство помечено `final` (контроллер) или расширяет базовые классы MoonShine
- Методы и свойства: camelCase
- Fluent-сеттеры полей возвращают `static` (`multiple()`, `allowedTypes()`)
- Пространства имён повторяют структуру каталогов `src/`

## Структура модулей

- `src/Controllers/` — HTTP-слой, JSON-ответы
- `src/MediaManager.php` — доменная логика файловых операций (ls, upload, delete, move, replace, newFolder, download)
- `src/Support/` — вспомогательные сервисы (валидация, безопасность, форматирование, навигация, ассеты, реестр)
- `src/Fields/`, `src/Components/`, `src/Pages/` — точки интеграции с UI MoonShine
- `src/Events/`, `src/Contracts/`, `src/Enums/`, `src/Helpers/`, `src/Exceptions/`
- `resources/views/` (+ `partials/`), `resources/js/`, `resources/css/`, `resources/lang/{en,ru}/`
- `routes/moonshine.php`, `config/media-manager.php`, сборка в `dist/` через `vite.config.js`

## Обработка ошибок

- `declare(strict_types=1)` в каждом PHP-файле
- Доменные ошибки — `MediaManagerException` с локализованным сообщением
- Контроллер: `try/catch (Throwable)` → `errorResponse()`: JSON `{status: false, message}` с HTTP 400
- Неожиданные исключения (не `MediaManagerException`) — `report()`; сырой текст ошибки отдаётся только в `app()->isLocal()`, в production — локализованный generic
- Все пользовательские сообщения — через `__('moonshine-media-manager::media-manager.*')`

## Поток управления

- Предпочитать плоский, читаемый поток управления глубокой вложенности. Guard-клаузы, ранние `return`/`continue`, небольшие именованные хелперы (`authorizeAction()`, `errorResponse()`, `getStorageUrl()`).
- Ранние проверки в начале экшена: авторизация → валидация входа → основная логика.

## Логирование

- Явных вызовов логгера в модуле нет; неожидаемые исключения отправляются в стандартный канал через `report($e)`
- Не логировать содержимое файлов, пути пользователей или параметры запроса сверх необходимого

## Сборка ассетов

- Изменения фронтенда требуют `npm run build` (Vite → `dist/`), затем `php artisan vendor:publish --tag=moonshine-media-manager-assets --force` для публикации в хост-проекте
- Кастомный Vite-плагин оборачивает итоговый `dist/media-manager.js` в IIFE — сохранять это поведение
