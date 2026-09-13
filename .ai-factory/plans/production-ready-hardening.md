# Implementation Plan: Production-ready hardening (безопасность, edge cases, архитектура, чистота)

Branch: 4.0 (git.create_branches=false — план без отдельной ветки)
Created: 2026-09-13

## Original Request

Ты — ведущий инженер-программист и эксперт по безопасности кода. Моя цель — превратить текущий код в стабильный, надежный и готовый к публикации пакет (production-ready package). 

Внимательно проанализируй предоставленный код и выполни следующие задачи:

1. Поиск уязвимостей и "дыр": Проверь код на проблемы с безопасностью (инъекции, утечки памяти, небезопасная обработка данных, race conditions, некорректная работа с типами).
2. Обработка ошибок (Edge Cases): Найди места, где код может упасть (crash). Добавь перехват исключений (try/catch), валидацию входящих аргументов и адекватные fallback-значения.
3. Архитектура пакета: Убедись, что код изолирован, не зависит от глобального состояния (global scope) и не имеет жестко зашитых зависимостей (hardcoded values). Сделай его модульным и удобным для импорта.
4. Оптимизация и чистота: Удали дублирование кода, оптимизируй тяжелые алгоритмы, улучши читаемость и переименуй переменные/функции по стандартам чистого кода (Clean Code).
5. Документация и типы: Добавь строгую типизацию (если применимо) и JSDoc/Docblocks комментарии к публичным методам, чтобы разработчикам было легко использовать этот пакет.

Выведи результат в следующем формате:
- Список найденных критических проблем и уязвимостей (с объяснением, почему это опасно).
- Список улучшений по архитектуре для пакета.
- Финальный, полностью исправленный и готовый к продакшену код.  и gitignore сделать, чтобы лишнее не грузить

## Settings

- Testing: yes — PHPUnit + Orchestra Testbench, покрытие критичных фиксов (см. Фазу 4)
- Logging: verbose — DEBUG-логи на ключевых границах (upload/delete/move/replace, блокировки, publish); уровнем управляет конфиг; не логировать содержимое файлов
- Docs: yes — обязательный чекпоинт документации в конце, через /aif-docs

## Аудит: найденные проблемы

Источники: полный обход PHP-слоя (MediaManager, Controller, URLGenerator, MediaSecurity, MediaValidator, MediaNavigator, MediaFormatter, MediaAssets, Registry, ServiceProvider, routes), JS (resources/js/media-manager.js, 1419 строк) и Blade-views (grep по `{!!`/`x-html`/`innerHTML` — совпадений нет).

### Критические (CRITICAL/HIGH)

| # | Проблема | Где | Почему опасно |
|---|----------|-----|---------------|
| C1 | `index()` и `upload()` (частично `delete()`) не оборачивают конструирование `MediaManager` в try/catch — конструктор бросает `MediaManagerException` (blocked path, non-local disk) | `src/Controllers/MediaManagerController.php:27-69, 84, 109` | `GET media/list?path=/framework` → необработанное исключение → **HTTP 500 HTML** вместо JSON-контракта `{status:false}`; в production с debug-режимом утекает stack trace |
| C2 | Whitelist-обход расширений: валидация проходит, если ЛИБО `guessExtension` ЛИБО клиентское расширение в allowed; MIME-проверка выполняется только по `realExtension`, а при пустом/неизвестном `realExtension` не выполняется вовсе. При пустом `allowed_ext` в конфиге валидация отключается полностью | `src/Support/MediaValidator.php:69-93`; `src/MediaManager.php:153-155` | Загрузка файла с содержимым X под расширением Y (полиглот); deny-by-default отсутствует |
| C3 | SVG в allowed_ext по умолчанию → stored XSS на домене админки (скрипты внутри SVG исполняются при открытии URL файла) | `src/Support/MediaValidator.php:26`; default config | Пользователь с правом загрузки получает JS-исполнение в сессии администратора |
| C4 | `delete()` принимает `files` без проверки типов: null → TypeError (шумный 400 + лишний `report()`), скаляр → единичный путь, элементы массива не проверяются на string | `src/Controllers/MediaManagerController.php:109`; `src/MediaManager.php:94-116` | Некорректная обработка типов, ложные алерты в логах, непредсказуемое поведение массового удаления |

### Средние (MEDIUM)

| # | Проблема | Где | Почему важно |
|---|----------|-----|--------------|
| M1 | TOCTOU в `move()`/`replace()`/`newFolder()`: exists-проверка и запись разнесены, без блокировки (upload имеет Cache::lock, остальные — нет) | `src/MediaManager.php:118-142, 225-272` | Race condition при конкурентных операциях |
| M2 | `Cache::lock()` падает на драйверах без поддержки блокировок (array/null) — BadMethodCallException; fallback по LockTimeout выполняет запись без блокировки молча | `src/MediaManager.php:175-194` | Крах загрузки на тестовых/минимальных конфигурациях |
| M3 | `ls()` зовёт UI-хелпер `toast()` и возвращает `[]` при несуществующем пути — домен зависит от UI, а фронтенд получает `status:true` с пустым списком (ошибка замаскирована) | `src/MediaManager.php:66-87` | Нарушение слоистости + маскировка ошибок |
| M4 | `initNavigator()` читает `request('view')` внутри конструктора домена — скрытая зависимость от глобального состояния | `src/MediaManager.php:54-64` | Global scope, несовместимость с не-HTTP контекстом (тесты, очереди) |
| M5 | `BLOCKED_PATHS = ['framework','logs']` захардкожены | `src/Support/MediaSecurity.php:11-14` | Hardcoded values, не настраивается, неожиданно для пользователя |
| M6 | `formatBytes` крашится при ≥1 EB (`$units[$i]` за границей); `formatTimestamp` использует `date()` (TZ сервера); JS-двойник `formatBytes` даёт `undefined` при ≥1 PB | `src/Support/MediaFormatter.php:9-23`; `media-manager.js:1043-1048` | Edge-case крэш/мусор в UI |
| M7 | `sanitizeFileName` вырезает все не-ASCII символы: «Отчёт.jpg» → «jpg»/«file» — потеря имён для кириллических пользователей | `src/Helpers/URLGenerator.php:91` | UX-баг потери данных (проект RU-ориентированный) |
| M8 | `.gitignore` игнорирует `package-lock.json`, а CI использует `npm ci` — CI красный | `.gitignore:7`; `.github/workflows/ci.yml` | Сломанная сборка + невоспроизводимые сборки |
| M9 | `autoPublishAssets()` — неявный побочный эффект в `boot()` c `@`-подавлением ошибок и необработанным сбоем копирования | `src/MediaManagerServiceProvider.php:95-112` | Скрытое глобальное поведение, тихие отказы |
| M10 | JS `loadMoveFolders()` — пустой `catch {}`: тишина при ошибке загрузки списка папок | `media-manager.js:845-846` | Пользователь не видит ошибку |
| M11 | `ls()` без пагинации — гигантские директории отдаются одним JSON | `src/MediaManager.php` | Производительность (зафиксировать лимитом конфига) |

### Низкие (LOW) / чистота

- L1: `URLGenerator::query()`/`extractQueryString()` — кандидат на мёртвый код (проверить использование, удалить)
- L2: PHPDoc отсутствуют у публичных методов `MediaManager`, `URLGenerator`, `MediaFormatter` (запрос пользователя №5)
- L3: `alt="Attachment"` в `MediaNavigator::getFilePreview` — захардкоженный английский
- L4: README/docs: проверить упоминание пути публикации (`vendor/moonshine-media-manager` vs фактический `vendor/media-manager`)

### Что уже хорошо (не трогаем)

- Path traversal: `sanitizePath` корректно вырезает `..`/`.`/бэкслеши; `sanitizeFileName` блокирует опасные расширения в любом сегменте имени
- XSS в UI: все динамические данные через `x-text`/`:src`; `Js::from()` в picker; `e()` в server-side preview; `{!! }` нигде не используется
- Frontend-гигиена: AbortController, `revokeObjectURL`, double-submit guards (`isSubmitting`), обработка 401/419, `$cleanup` листенеров
- CSRF: `X-CSRF-TOKEN` во всех запросах; маршруты под `withAuthenticate: true`

## Архитектурные улучшения

1. **Единая ошибка JSON:** каждый экшен контроллера = guard → нормализация входа → try{ construct+call } → JSON. Помощник `errorResponse` остаётся единственной точкой выхода ошибок.
2. **Домен без UI/глобального состояния:** `MediaManager` не зовёт `toast()`/`request()`; view передаётся параметром; вместо toast — исключения.
3. **Конфиг вместо хардкода:** `blocked_paths` (M5) в `config/media-manager.php` с текущими дефолтами; `allowed_ext` пустой = deny-all вместо allow-all.
4. **Блокировки консистентно:** один механизм `Cache::lock` c детекцией поддержки и WARN-fallback для upload/move/replace/newFolder.
5. **Strict-типизация контрактов:** controller нормализует `files/path/dir/name` к `(string)[]`/`string` до домена.

## Commit Plan

- **Commit 1** (после задач 1–4): `fix(security): strict extension policy, SVG sanitization, unified controller error handling, unicode filenames`
- **Commit 2** (после задач 5–8): `fix(reliability): race-condition locks, formatter edge cases, domain layering cleanup`
- **Commit 3** (после задач 9–11): `refactor: config-driven blocked paths, publish hardening, dead code removal, phpdoc`
- **Commit 4** (после задач 12–14): `chore: gitignore + lock file, testbench suite, CI test job`
- **Commit 5** (задача 15): `docs: security defaults, tests, blocked_paths`

## Tasks

### Phase 1: Критичные фиксы безопасности
- [x] Task 1: Единая обработка ошибок в контроллере — обернуть конструирование+вызов `MediaManager` в try/catch во ВСЕХ экшенах (index, upload, delete, move, newFolder, replace, download); нормализация входов: `files` → валидный `array<string>` (string/null/scalar → корректная 400-ошибка или одиночный массив), `path/dir/file/new/name` → `(string)` с приведением до домена. Файл: `src/Controllers/MediaManagerController.php`. Логирование: `[MediaManagerController] action=… path=… result=ok|error` на DEBUG, ошибочные типы входа на WARN. (закрывает C1, C4)
- [x] Task 2: Строгая политика расширений в `MediaValidator`: расширение итогового сохраняемого имени обязано быть в allowed; MIME-проверка по итоговому расширению; пустой `allowed_ext` → deny-all с локализованным сообщением (новые ключи в en+ru). Файлы: `src/Support/MediaValidator.php`, `src/MediaManager.php` (передача final extension), `resources/lang/{en,ru}/media-manager.php`. Логирование: DEBUG причина отказа (ext/mime/size). (закрывает C2)
- [x] Task 3: Санитизация SVG при загрузке: strip `<script>`, event-handler атрибутов (`on*`), `<foreignObject>`, `javascript:` href'ов через XML-парер (без внешних зависимостей — DOMDocument), с fallback-отказом при не-валидном XML. Новый `src/Support/SvgSanitizer.php` + интеграция в `MediaManager::upload()`/`replace()`. Логирование: WARN при санитизации/отказе. (закрывает C3)
- [x] Task 4: Unicode-имена файлов: транслитерация `Str::ascii()` перед фильтрацией в `sanitizeFileName`, сохранить блокировку опасных расширений. Файл: `src/Helpers/URLGenerator.php`. (закрывает M7)

### Phase 2: Надёжность и edge cases
- [x] Task 5: `MediaFormatter::formatBytes` — защита от выхода за массив единиц (clamp + «PB»/«EB» хвост); `formatTimestamp` → `Carbon::createFromTimestamp` c app TZ. Файл: `src/Support/MediaFormatter.php`. JS: `formatBytes` guard `i = Math.min(i, units.length-1)` в `media-manager.js`. (закрывает M6)
- [x] Task 6: Консистентные блокировки: вынести паттерн Cache::lock в приватный `withLock(string $key, Closure $op)`; покрыть move/replace/newFolder/upload; детекция поддержки lock (try/catch BadMethodCallException → WARN + выполнение без блокировки); LockTimeout → WARN + выполнение. Файл: `src/MediaManager.php`. Логирование: INFO захват/освобождение, WARN fallback. (закрывает M1, M2)
- [x] Task 7: Чистка домена: `ls()` бросает `MediaManagerException` вместо `toast()`+`[]`; `view` передаётся параметром (из controller), удалить `request()` из конструктора. Файлы: `src/MediaManager.php`, `src/Controllers/MediaManagerController.php`. (закрывает M3, M4)
- [x] Task 8: JS `loadMoveFolders` — тост об ошибке вместо пустого catch; там же погасить `console.debug` под флагом `window.mmDebug`. Файл: `resources/js/media-manager.js`. (закрывает M10)
- [x] Task 8a: `formatBytes` переведён на log()-подход (вариант пользователя) с epsilon-защитой от float-undershoot на степенях 1024; `<= 0` → «0 B»; clamp на PB.

### Phase 3: Архитектура и чистота
- [x] Task 9: `blocked_paths` → конфиг (ключ в `config/media-manager.php`, дефолт `['framework','logs']`), `MediaSecurity::assertNotBlockedPath` читает конфиг; задокументировать. Файлы: `config/media-manager.php`, `src/Support/MediaSecurity.php`. (закрывает M5)
- [x] Task 10: `autoPublishAssets`: обернуть в try/catch с ERROR-логом при неудаче копирования, убрать `@`-подавление (явная проверка filemtime), INFO-лог при публикации. Файл: `src/MediaManagerServiceProvider.php`. (закрывает M9)
- [x] Task 11: Чистота: проверить и удалить мёртвый `URLGenerator::query/extractQueryString` (если не используется — grep по пакету и sibling-модулям); PHPDoc ко всем публичным методам `MediaManager`, `URLGenerator`, `MediaFormatter`, `MediaValidator`, `SvgSanitizer`; `alt` из локализации. (закрывает L1–L3)

### Phase 4: Инфраструктура и тесты
- [x] Task 12: `.gitignore`: убрать `package-lock.json` из игнора, добавить `.codegraph/`, `.phpunit.cache/`, `.phpunit.result.cache`, `docs-html/`, `*.log`; убедиться что lock-файл закоммичен (решение пользователя). Файлы: `.gitignore`, `package-lock.json` (добавить в индекс). (закрывает M8)
- [x] Task 13: Testbench-инфраструктура: composer require-dev `phpunit/phpunit ^11`, `orchestra/testbench ^9`; `phpunit.xml.dist`; `tests/TestCase.php` с настроенным local-диском и конфигом пакета. Файлы: `composer.json`, `phpunit.xml.dist`, `tests/TestCase.php`.
- [x] Task 14: Тесты (зависит от 1-11): Unit — sanitizePath (traversal/бэкслеши/null-сегменты), sanitizeFileName (unicode→translit, опасные расширения, пустое → fallback), MediaValidator (обход C2 закрыт: content-клиентское расширение, пустой allowed → deny, MIME mismatch, size), MediaSecurity (blocked_paths из конфига), formatBytes (1EB — нет краша), SvgSanitizer (script strip, невалидный XML). Feature — media/list happy + blocked path → 400 JSON (не 500!), upload happy + запрещённое расширение, delete массивом/мусорным input → 400, move/newFolder/replace happy + конфликт имён. Файлы: `tests/Unit/*.php`, `tests/Feature/*.php`. Логирование тестов — стандартный вывод phpunit. (28/28 OK; дополнительно закрыт CRITICAL: delete('..') → deleteDirectory('/') — стирание корня диска)
- [x] Task 15: CI: добавить job `phpunit` в `.github/workflows/ci.yml` (setup-php 8.2+8.3 matrix в рамках одного job, composer install --prefer-dist, vendor/bin/phpunit); php-синтаксис job остаётся. (зависит от 13)

### Phase 5: Документация (Docs: yes)
- [x] Task 16: Чекпоинт через /aif-docs: `docs/configuration.md` — новые ключи (`blocked_paths`, политика SVG, пустой allowed_ext = deny), `docs/api.md` — гарантии JSON-контракта (500 больше не возникает), `docs/development.md` — как запускать тесты; README — синхронизировать путь публикации ассетов, если расхождение подтвердится. (закрывает L4; расхождение путей не подтвердилось — docs уже корректны; README фичи-список дополнен не был, т.к. изменения внутренние)

## Notes

- Правило минимальных правок: баг-фиксы не тянут рефакторинг сверх перечисленного; структура слоёв сохраняется (см. `.ai-factory/ARCHITECTURE.md`).
- «Финальный исправленный код» реализуется на этапе `/aif-implement` по этому плану; настоящий файл — авторитетный список проблем и задач.
- Все новые пользовательские строки — одновременно в `resources/lang/{en,ru}/media-manager.php`.
- Изменения `resources/js|css` → `npm run build` + перезапись `dist/` в том же коммите.
