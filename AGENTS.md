# AGENTS.md

> Структурная карта проекта для AI-агентов. Обновляйте при существенных изменениях структуры проекта.

## Обзор проекта

MoonShine Media Manager — файловый менеджер для админ-панели MoonShine 4 (Laravel): AJAX-навигация по папкам, загрузка/замена/перемещение/удаление файлов, picker-поле для форм, события и реестр расширений для сторонних пакетов. Composer-пакет без собственной БД — работает поверх локального диска Laravel Storage.

## Технологический стек

- **Язык:** PHP 8.2+ (strict_types)
- **Фреймворк:** пакет для Laravel / MoonShine 4.0+
- **БД:** нет — файловое хранилище через Laravel Storage (локальный диск)
- **Фронтенд:** vanilla JS + CSS, Blade-шаблоны; сборка Vite 6 + lightningcss

## Структура проекта

```
moonshine-media-manager/
├── composer.json               # PSR-4 YuriZoom\MoonShineMediaManager\ → src/, автообнаружение провайдера
├── package.json                # npm-скрипты сборки (build / dev --watch)
├── vite.config.js              # Сборка JS+CSS в dist/, IIFE-обёртка бандла, lightningcss+autoprefixer
├── config/
│   └── media-manager.php       # Публикуемый конфиг (disk, allowed_ext, max_file_size, ability...)
├── routes/
│   └── moonshine.php           # AJAX-маршруты: media/list|upload|download|delete|move|new-folder|replace
├── src/
│   ├── Controllers/            # MediaManagerController — HTTP-слой, JSON-ответы
│   ├── MediaManager.php        # Доменная логика файловых операций (ls, upload, delete, move, replace)
│   ├── Support/                # MediaValidator, MediaSecurity, MediaFormatter, MediaNavigator,
│   │                           # MediaAssets, MediaManagerRegistry
│   ├── Fields/                 # MediaManagerPicker — поле выбора файлов в формах
│   ├── Components/             # MediaManagerOffCanvas — глобальная offcanvas-панель
│   ├── Pages/                  # MediaManagerPage — страница менеджера
│   ├── Events/                 # MediaManagerFileUploaded / Replaced / Deleted
│   ├── Enums/                  # MediaManagerView (table / grid)
│   ├── Contracts/              # MediaManagerRegistryInterface — реестр расширений
│   ├── Helpers/                # URLGenerator
│   ├── Exceptions/             # MediaManagerException
│   └── MediaManagerServiceProvider.php
├── resources/
│   ├── js/media-manager.js     # Вся логика фронтенда (vanilla JS, ~без зависимостей)
│   ├── css/media-manager.css   # Стили, z-index-токены --mm-z-browser / --mm-z-dialog
│   ├── views/                  # Blade: manager.blade.php, fields/, components/, partials/
│   └── lang/{en,ru}/           # Файлы локализации
├── dist/                       # Собранные ассеты (публикуются тегом moonshine-media-manager-assets)
├── docs/                       # Доп. документация
├── blob/                       # Скриншоты для README
├── .ai-factory/                # Контекст AI Factory (DESCRIPTION, rules, config.yaml)
└── .opencode/skills/           # Скиллы проекта (aif-*, moonshine-package)
```

## Ключевые точки входа

| Файл | Назначение |
|------|-----------|
| `src/MediaManagerServiceProvider.php` | Регистрация пакета: виды, переводы, маршруты, publishes, страницы |
| `src/MediaManager.php` | Ядро домена — все файловые операции |
| `src/Controllers/MediaManagerController.php` | AJAX-эндпоинты, JSON-контракт `{status, message/files}` |
| `routes/moonshine.php` | Маршруты `media/*` |
| `config/media-manager.php` | Конфиг с fallback: standalone > `moonshine.php → media_manager` > дефолты |
| `resources/js/media-manager.js` | Фронтенд менеджера (навигация, загрузка, модалки, picker) |
| `vite.config.js` | Сборка ассетов в `dist/` |

## Документация

| Документ | Путь | Описание |
|----------|------|----------|
| README | README.md | Лендинг проекта: версии, фичи, quick start, таблица ссылок |
| Установка | docs/getting-started.md | Публикация, OffCanvas, меню, проверка установки |
| Конфигурация | docs/configuration.md | Параметры, ENV, авторизация, z-index слои |
| Поле MediaManagerPicker | docs/picker-field.md | Фильтрация, Json, Layouts, поведение |
| API и события | docs/api.md | Маршруты, JSON-контракт, события, реестр расширений |
| Разработка | docs/development.md | Сборка ассетов, интеграция сторонних пакетов |
| Старые версии | docs/legacy-versions.md | Настройка v3 (MoonShine 4) и v2 (MoonShine 3) |
| Лицензия | LICENSE | MIT |

## AI-контекст файлы

| Файл | Назначение |
|------|-----------|
| AGENTS.md | Эта структурная карта |
| .ai-factory/DESCRIPTION.md | Спецификация проекта: стек, возможности, архитектурные заметки |
| .ai-factory/ARCHITECTURE.md | Архитектурные правила и паттерны |
| .ai-factory/rules/base.md | Детектированные конвенции кодовой базы |
| .ai-factory/config.yaml | Настройки AI Factory (языки: ru; git: base=main, без веток для планов) |

## Правила для агентов

- Разделяйте составные shell-команды на отдельные шаги:
  - Неправильно: `git checkout main && git pull`
  - Правильно: сначала `git checkout main`, затем `git pull origin main`
- Изменения фронтенда требуют пересборки: `npm run build`, затем публикация `php artisan vendor:publish --tag=moonshine-media-manager-assets --force`
- Все пользовательские строки — через `__('moonshine-media-manager::...')`; новые ключи добавлять в `resources/lang/{en,ru}/media-manager.php` одновременно
