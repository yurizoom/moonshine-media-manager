# Frontend Rules

> Area-specific conventions for frontend. Loaded after rules/base.md.

## Rules

- Один входной бандл `resources/js/media-manager.js` — vanilla JS без фреймворков; новые npm-зависимости только при реальной необходимости
- Фронтенд общается с бэкендом исключительно через JSON-эндпоинты `media/*` — никаких прямых обращений к PHP/blade-роутам из JS
- Кастомные z-index только через токены `--mm-z-browser` / `--mm-z-dialog` поверх шкалы ядра MoonShine — никаких «магических» чисел
- CSS-токены и классы пакета — с префиксом `mm-`, чтобы не конфликтовать со стилями хоста
- Blade-виды — только в namespace `moonshine-media-manager::`; переиспользуемые фрагменты — в `partials/`
- Пользовательские строки в UI — из lang-файлов (`resources/lang/{en,ru}/media-manager.php`), ключи добавлять в оба языка одновременно
- Состояние UI (последняя папка пикера, фильтр WebP/AVIF) — в localStorage с ключами пакета
- После любых изменений `resources/js|css` обязателен `npm run build` + `php artisan vendor:publish --tag=moonshine-media-manager-assets --force`
- Не ломать IIFE-обёртку бандла из `vite.config.js` — она изолирует JS пакета от глобальной области хоста
- Превью изображений — lazy-load (`loading="lazy"` / IntersectionObserver), не рендерить все сразу
