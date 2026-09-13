# Hardening Round 2 — аудит недоработок и полировка

**Ветка:** 4.0 (без отдельной ветки, `git.create_branches=false`)
**Дата:** 2026-09-13
**Режим:** Full · Testing: yes · Logging: verbose · Docs: yes

## Original Request

поизучай все еще раз, посмотри недоработки, баги, мелочи, что можно улучшить, доработать, валидации. Кароче думай как инженер и сеньор программист

## Settings

- Testing: yes — регрессионные тесты на каждый баг + закрытие дыр покрытия
- Logging: verbose — DEBUG-детали в домене/контроллере, `mmDebugLog` в JS
- Docs: yes — обязательный чекпоинт синхронизации docs/ в конце

## Аудит — сводка находок

Полный обход: JS (1447 строк целиком), PHP (codegraph + прямые чтения), routes, config, tests. Агенты аудита не запускались повторно (цепочка моделей `opencode-go/*` недоступна — та же ошибка, что в раунде 1; аудит выполнен вручную).

| ID | Sev | Находка | Файл:строка |
|----|-----|---------|-------------|
| B1 | major | `MediaManagerFileDeleted` **не диспатчится при удалении папок** — слушатели (`DeleteImageConversions`, `DeleteConvertedImageVersions`) не чистят конвертации → orphan-файлы | `src/MediaManager.php:145-147` |
| B2 | major | Offcanvas `mmBrowser.init()` зовёт `loadFiles('/')` **безусловно** → AJAX `media/list` на каждом pageview админки (offcanvas в layout), гонка с `$watch(isOpen)`, игнор `lastPath` | `resources/js/media-manager.js:306` |
| B3 | major | Picker-фильтры (`allowedTypes`/`allowedExtensions`) фильтруют только listing; **upload из пикерного контекста их не проверяет** | `js:_doUpload`, `Controllers/MediaManagerController.php:upload` |
| B4 | minor | Ошибка авторизации отдаёт **400 вместо 403** (AuthorizationException ловится в общий catch) | `Controllers/MediaManagerController.php:errorResponse` |
| B5 | minor | Мёртвая ветка `submitUpload(fileList)` / `isDirectDrop` — после дропзон-ревампа вызовов с аргументом нет | `js:990-1033` |
| B6 | minor | `MediaManagerOffCanvas::viewData()` → `new MediaManager('/')` + генерация urls на каждом рендере каждой страницы | `src/Components/MediaManagerOffCanvas.php:17` |
| B7 | minor | Церемония `class_exists(...::class)` вокруг dispatch — классы всегда существуют (тот же пакет) | `src/MediaManager.php:142,246,386` |
| B8 | minor | Тестовые дыры: download endpoint, события (Event::fake), rename/move **папок**, ability denied | `tests/` |
| B9 | minor | JS fallback-сообщения захардкожены на английском (сервер локализует, fallback — нет) — принять как есть, отметить | js (разбросано) |
| OK | — | Проверено и в порядке: cache-busting `?v=` после replace, `syncInput` + broken-remap при drag-reorder, AbortController-защита, `revokeObjectURL`-парность, `$cleanup` слушателей, LRU exists-кэша, x-cloak, конфиг-ключи vs `config()`-чтения (все 9 ключей читаются), routes `withAuthenticate: true` | — |

## Tasks

### Фаза 1 — Поведенческие баги

- [x] Task 1: События при удалении папок (B1). В `MediaManager::delete()` в ветке `directoryExists`: ДО `deleteDirectory` собрать `$this->storage->allFiles($safePath)` и диспатчить `MediaManagerFileDeleted::dispatch($file, $this->getDisk())` для каждого файла; сами события перенести ПОСЛЕ успешного удаления (не в lock до). Лог: `Log::debug('[MediaManager] folder delete dispatched events', ['count' => N])`. Файл: `src/MediaManager.php`. (закрывает B1)
- [x] Task 2: Ленивая инициализация offcanvas (B2). `mmBrowser(urls, modalPrefix, deferLoad = false)`: при `deferLoad === true` НЕ звать `loadFiles('/')` в init — загрузку делает `$watch('$store.mm.isOpen')` (уже есть) с `lastPath \|\| '/'`. Offcanvas blade передаёт `true`, manager page — `false`. Лог: `mmDebugLog('deferred initial load (offcanvas)')`. Файлы: `resources/js/media-manager.js`, `resources/views/components/media-manager-offcanvas.blade.php`, `resources/views/manager.blade.php`. Пересборка `npm run build` + публикация. (закрывает B2)
- [x] Task 3: Picker-фильтры на upload (B3). JS `_doUpload`: при `Alpine.store('mm').allowedTypes/allowedExtensions` непустых — аппендить `types[]`/`extensions[]` в FormData. Сервер `upload()`: принимать списки (`stringList`), при непустых `extensions[]` — финальное расширение каждого файла обязано входить в список (расширение проверки MediaValidator: `validateUploadedFile($file, $ext, $extraAllowedExtensions)` или отдельный guard); при непустых `types[]` — MIME-группа файла (из его финального расширения) обязана входить в список. Лог: `Log::debug('upload picker filter applied', ['ext' => ..., 'types' => ...])`. Файлы: `resources/js/media-manager.js`, `src/Controllers/MediaManagerController.php`, возможно `src/Support/MediaValidator.php`. Локализация ошибки — переиспользовать существующий ключ `file_extension_not_allowed` / добавить `type_not_allowed` (en+ru). (закрывает B3)
- [x] Task 4: 403-контракт авторизации (B4). В `errorResponse()` (или в `handle()` catch): `AuthorizationException`/`HttpException` со статусом 403 → JSON c HTTP 403. Лог: `Log::warning('action denied')`. Файл: `src/Controllers/MediaManagerController.php`. (закрывает B4)

### Фаза 2 — Чистота кода

- [x] Task 5: Убрать мёртвую direct-drop ветку upload (B5): `submitUpload()` без параметра, `_doUpload` без `isDirectDrop`-веток (ошибки — только `formError`, тост больше не нужен — модалка открыта). Файл: `resources/js/media-manager.js`. Пересборка.
- [x] Task 6: Кэш URLs offcanvas (B6) + убрать `class_exists`-церемонию (B7): `MediaManagerOffCanvas::viewData()` — статическое memoization массива urls (ключ — диск из конфига); в `src/MediaManager.php` убрать три `if (class_exists(...))` — диспатчи безусловно. Лог: без изменений (dispatch сам шумит достаточно). Файлы: `src/Components/MediaManagerOffCanvas.php`, `src/MediaManager.php`.

### Фаза 3 — Тестовое покрытие

- [x] Task 7: Download endpoint: happy (200, content-disposition), `?file=../` traversal → 400 JSON, blocked path → 400. Файл: `tests/Feature/MediaManagerEndpointsTest.php` (или отдельный `DownloadEndpointTest`).
- [x] Task 8: События (зависит от 1): `Event::fake` — upload → `MediaManagerFileUploaded` с финальным путём (вкл. rename-duplicates случай); replace → `MediaManagerFileReplaced`; delete файла → `MediaManagerFileDeleted`; **delete папки с файлами → по событию на каждый файл**. Файл: `tests/Feature/MediaManagerEventsTest.php`.
- [x] Task 9: Контракт авторизации (зависит от 4): ability в конфиге + юзер без прав → 403 JSON `{status:false}`; rename/move папки happy-path. Файл: `tests/Feature/MediaManagerEndpointsTest.php`.
- [x] Task 10: Upload с пикер-фильтрами (зависит от 3): `extensions[]=png` + загрузка `.jpg` → 400; без фильтров — как раньше. Файл: `tests/Feature/MediaManagerEndpointsTest.php`.

### Фаза 4 — Доки и финал

- [x] Task 11: Синхронизация документации: `docs/api.md` — события при удалении папок, 403-контракт, picker-фильтры на upload; `docs/picker-field.md` — enforcement фильтров при загрузке; `docs/configuration.md` — если добавлен ключ/поведение; README — формулировка drag-drop («в модалке загрузки») сверить. Финал: `php -l` все изменённые, `vendor/bin/phpunit` полный прогон, `npm run build` + `php artisan vendor:publish --tag=moonshine-media-manager-assets --force` (из корня хоста).

## Commit Plan

> Коммиты — только по явной команде пользователя (действовало правило «без коммита»).

- Чекпоинт 1 (после Фазы 1): `fix: dispatch delete events for folders, defer offcanvas load, enforce picker filters on upload, 403 auth contract`
- Чекпоинт 2 (после Фазы 2): `refactor: drop dead upload branch, cache offcanvas urls`
- Чекпоинт 3 (после Фаз 3-4): `test: cover download/events/auth/picker-upload, sync docs`

## Примечания

- Предыдущий план `production-ready-hardening.md` завершён на 16/16 — после старта этого раунда заархивировать через `/aif-archive` (уберёт неоднозначность выбора плана в `/aif-implement`).
- B9 (английские JS-fallback) осознанно выведен из скоупа: серверные сообщения локализованы и покрывают 95% случаев.
- Проверено и принято без изменений: `switchView` re-fetch (сервер — источник истины по view), a11y кнопок (title даёт accessible name).
