# MoonShine Media Manager

## Обзор

Файловый менеджер для [MoonShine](https://moonshine-laravel.com/) — админ-панели на Laravel. Composer-пакет (`yurizoom/moonshine-media-manager`), расширяющий MoonShine 4 полнофункциональным AJAX-менеджером медиафайлов: навигация по папкам, загрузка, замена, перемещение, переименование, массовое удаление, скачивание, а также picker-поле для выбора файлов прямо в формах ресурсов.

Работает поверх локального диска Laravel Filesystem (Storage). БД не используется — состояние полностью в файловой системе; конфигурация через `config/media-manager.php` с fallback в `config/moonshine.php → media_manager`.

## Ключевые возможности

- **AJAX-навигация** по папкам без перезагрузки (GET `media/list`)
- **Загрузка файлов** — множественная, с проверкой MIME, расширения и размера; drag-and-drop
- **Replace / Move / Rename / New Folder / Bulk Delete / Download / URL**
- **Поле `MediaManagerPicker`** — выбор файлов из менеджера в форме (multiple, фильтрация по типу/расширению, drag-and-drop reorder, интеграция с Json и Layouts)
- **Компонент `MediaManagerOffCanvas`** — глобальная offcanvas-панель, через которую работают все picker-поля
- **Реестр расширений** (`MediaManagerRegistryInterface`) — сторонние пакеты регистрируют file/toolbar-действия (пример: moonshine-image-editor добавляет кнопку редактирования)
- **События** — `MediaManagerFileUploaded` / `MediaManagerFileReplaced` / `MediaManagerFileDeleted`
- **Авторизация** — опциональный Gate ability (`MOONSHINE_MEDIA_MANAGER_ABILITY`)
- **Два вида** — таблица и сетка; поиск, фильтр по типу, сортировка, lazy-load превью
- **Локализация** — ru / en

## Технологический стек

- **Язык:** PHP 8.2+ (`declare(strict_types=1)` во всех файлах)
- **Фреймворк:** пакет для Laravel / MoonShine (`moonshine/moonshine` ^4.0), ServiceProvider с авто-обнаружением
- **БД:** нет — файловое хранилище через Laravel Storage (только локальный диск)
- **Фронтенд:** vanilla JS + CSS без фреймворков (Alpine-совместимые паттерны), Blade-шаблоны
- **Сборка:** Vite 6 + lightningcss + autoprefixer; кастомный плагин оборачивает бандл в IIFE; выход в `dist/`
- **PSR-4:** `YuriZoom\MoonShineMediaManager\` → `src/`

## Архитектурные заметки

- Слои пакета: `Controllers` (HTTP → JSON), `MediaManager` (доменная логика файловых операций), `Support` (валидация, безопасность, форматирование, навигация, ассеты, реестр), `Fields` / `Components` / `Pages` (интеграция с MoonShine UI), `Events`, `Contracts`, `Enums`, `Exceptions`
- Контроллер возвращает JSON `{status: bool, message|files, ...}`; ошибки — через `errorResponse()`: ожидаемые `MediaManagerException` не репортятся, неожиданные `Throwable` уходят в `report()`, в production сообщение подменяется локализованным
- Маршруты в `routes/moonshine.php` (media/list, media/upload, media/download, media/delete, media/move, media/new-folder, media/replace)
- Виды именуются `moonshine-media-manager::<view>`; партиалы в `resources/views/partials/`
- z-index-слои менеджера вынесены в CSS-токены `--mm-z-browser` / `--mm-z-dialog` поверх шкалы ядра MoonShine
- Ассеты публикуются тегом `moonshine-media-manager-assets`, конфиг — `moonshine-media-manager-config`
- Интеграция с другими модулями монорепозитория — через события и `MediaManagerRegistryInterface` (без жёстких зависимостей)

## Архитектура

Подробные архитектурные правила и паттерны — в [.ai-factory/ARCHITECTURE.md](ARCHITECTURE.md).
**Паттерн:** Layered (адаптированная под Laravel-пакет: HTTP → Домен → Support → UI-адаптеры MoonShine)

## Нефункциональные требования

- **Логирование:** неожиданные исключения через `report()`; в ответах наружу — только локализованные обезличенные сообщения
- **Обработка ошибок:** JSON `{status: false, message}` с HTTP 400; сырые тексты исключений только в local-окружении
- **Безопасность:** whitelist расширений + MIME-проверка (`MediaValidator`), защита путей (`MediaSecurity`), опциональный Gate, лимит размера файла
- **Тесты:** в модуле отсутствуют; смежный монорепозиторий moonshine содержит тесты ядра
