# Архитектура: Layered (адаптированная под Laravel-пакет)

## Обзор

Пакет использует слоистую архитектуру, адаптированную под специфику Composer-пакета для MoonShine 4: вместо классических `Services/Repositories` над БД здесь HTTP-слой над доменным ядром файловых операций (`MediaManager`), которое работает поверх Laravel Storage (локальный диск, без собственной БД). Слои — HTTP, Домен, Support-инфраструктура и UI-адаптеры MoonShine — зависят строго в одном направлении, что сохраняет пакет тестируемым и позволяет сторонним пакетам интегрироваться через события и реестр, не трогая внутренности.

Документ адаптирован к реальной структуре кодовой базы (решение пользователя при генерации): переименование каталогов под канонический шаблон не требуется.

## Обоснование выбора

- **Тип проекта:** отдельный Composer-пакет (библиотека), один функциональный модуль
- **Стек:** PHP 8.2+ / Laravel-пакет / MoonShine 4, vanilla JS + Vite
- **Ключевой фактор:** малый размер (~20 PHP-классов), простая доменная область (файловые операции), отсутствие БД — decision-matrix указывает на Layered (низкая операционная сложность, быстрая скорость разработки)

## Структура слоёв

```
routes/moonshine.php
        │
        ▼
src/Controllers/                      СЛОЙ 1: HTTP / Transport
  MediaManagerController              — JSON-контракт {status, message|files}
        │                             — авторизация (Gate ability)
        ▼
src/MediaManager.php                  СЛОЙ 2: Домен (ядро)
  + Events/                           — ls, upload, delete, move,
  + Enums/                              replace, newFolder, download
  + Exceptions/                       — доменные события и исключения
  + Contracts/
        │
        ▼
src/Support/                          СЛОЙ 3: Support / Инфраструктура
  MediaValidator, MediaSecurity       — валидация, безопасность путей,
  MediaFormatter, MediaNavigator        форматирование, навигация,
  MediaAssets, MediaManagerRegistry     пути ассетов, реестр расширений
  + Helpers/URLGenerator
        │
        ▼
Laravel Storage (локальный диск)      Внешняя зависимость

src/Fields/  src/Pages/  src/Components/    СЛОЙ 4: UI-адаптеры MoonShine
resources/views|js|css                      — picker-поле, страница,
                                             offcanvas; blade + vanilla JS
```

`MediaManagerServiceProvider` — composition root: связывает все слои (маршруты, виды, переводы, publishes, страницы).

## Правила зависимостей

- ✅ `routes → Controllers → MediaManager → Support → Storage`
- ✅ UI-адаптеры (`Fields`/`Pages`/`Components`) → `MediaManager`, `Support`, `MediaAssets`
- ✅ Сторонние пакеты → `Events/*` и `Contracts/MediaManagerRegistryInterface` (единственная публичная интеграция)
- ❌ `MediaManager` (домен) не знает о `Controllers`, blade, запросах — только `MoonShineRequest`-независимые сигнатуры
- ❌ `Support` не импортирует `Controllers`/`Fields`/`Pages`
- ❌ Фронтенд (`resources/js`) не обращается к PHP иначе как через JSON-эндпоинты `media/*`
- ❌ Никаких прямых вызовов Storage из контроллеров — только через `MediaManager`

## Коммуникация между слоями

- **HTTP ↔ Домен:** контроллер создаёт `new MediaManager($path)`, вызывает метод, заворачивает результат в JSON; ошибки — через `MediaManagerException` → `errorResponse()` (HTTP 400, локализованное сообщение)
- **Домен → внешние слушатели:** события `MediaManagerFileUploaded/Replaced/Deleted` с `$path, $disk` — интеграция без жёстких зависимостей
- **UI-адаптеры → фронтенд:** blade рендерит `pickerConfig` (JSON) + ассеты через `MediaAssets::get()`; JS общается только с эндпоинтами
- **Расширения:** `$app->resolving(MediaManagerRegistryInterface::class, ...)` в ServiceProvider пакета-потребителя

## Ключевые принципы

1. **Тонкие контроллеры:** парсинг запроса → один вызов домена → JSON-ответ; бизнес-логика только в `MediaManager`
2. **Домен без framework-утечек:** `MediaManager` работает с путями и Storage, не с HTTP-запросами
3. **Единый JSON-контракт:** `{status: bool, message|files, ...}` — фронтенд не парсит ничего иного
4. **Публичная поверхность = ServiceProvider + Events + Contracts:** всё остальное — внутренности, менять можно свободно
5. **Локализация на границах:** пользовательские строки — только через `__('moonshine-media-manager::...')` в контроллерах и blade; в домене — локализованные сообщения исключений

## Политика организации кода

- **Новые фичи:** следуют этой архитектуре — новый эндпоинт = маршрут + метод контроллера + метод домена (при необходимости — Support-хелпер)
- **Существующий код:** документирован как есть; при правках предпочтительно следовать конвенциям документа, но не переписывать работающий код ради структуры
- **Взаимодействие:** новый код, вызывающий существующий, — через чистые интерфейсы (Contracts), не через внутренности

## Примеры кода

### Тонкий контроллер (слой 1 → 2)

```php
public function move(MoonShineRequest $request): JsonResponse
{
    $this->authorizeAction(); // guard: Gate ability из конфига
    try {
        (new MediaManager((string) $request->get('path', '/')))
            ->move((string) $request->get('new', ''));
    } catch (Throwable $e) {
        return $this->errorResponse($e);
    }

    return response()->json([
        'status' => true,
        'message' => __('moonshine-media-manager::media-manager.moved_successfully'),
    ]);
}
```

### Домен + Support (слой 2 → 3)

```php
// MediaManager::upload() делегирует валидацию Support-слою
// MediaValidator проверяет расширение + MIME + размер,
// MediaSecurity защищает пути (запрет ../),
// результат — доменное событие MediaManagerFileUploaded
```

### UI-адаптер MoonShine (слой 4 → 3)

```php
class MediaManagerPicker extends Field
{
    protected string $view = 'moonshine-media-manager::fields.media-manager-picker';

    protected function assets(): array
    {
        return MediaAssets::get(); // Support-слой отдаёт пути published-ассетов
    }
}
```

## Антипаттерны

- ❌ **God Controller:** бизнес-логика (правила переименования дубликатов, конфликты путей) в контроллере вместо `MediaManager`
- ❌ **Пропуск слоя:** контроллер вызывает Storage/файловую систему напрямую, минуя домен
- ❌ **Обратные зависимости:** `Support` импортирует `Controllers`; `MediaManager` знает о blade/Alpine
- ❌ **Жёсткая связка пакетов:** moonshine-image-editor импортирует внутренности менеджера вместо Events/Registry
- ❌ **Сырые строки в UI:** тексты без `__()`; ключи только в ru без en (и наоборот)
- ❌ **z-index магией:** модальные слои пакета заданы числами вместо токенов `--mm-z-browser` / `--mm-z-dialog` поверх шкалы ядра
