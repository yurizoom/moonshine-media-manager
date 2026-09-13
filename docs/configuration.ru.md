[← Установка](getting-started.ru.md) · [English](configuration.md) | **Русский** · [Back to README](../README.ru.md) · [Поле MediaManagerPicker →](picker-field.ru.md)

# Конфигурация

## Файл конфигурации

Конфиг публикуется в `config/media-manager.php` (корневой файл). Поддерживается fallback — если ключи найдены в `config/moonshine.php` → `media_manager`, они тоже применяются. Приоритет: standalone файл > `moonshine.php` > дефолты пакета.

```php
// config/media-manager.php
return [
    'disk' => config('filesystems.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,gif,webp,avif,svg,bmp,ico,heic,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,tar,gz,txt,md,csv,json,yaml,yml,mp3,wav,ogg,m4a,aac,flac,mp4,avi,mov,mkv,webm',
    'max_file_size' => env('MOONSHINE_MEDIA_MANAGER_MAX_FILE_SIZE', 10 * 1024 * 1024),
    'rename_duplicates' => env('MOONSHINE_MEDIA_MANAGER_RENAME_DUPLICATES', true),
    'auto_menu' => env('MOONSHINE_MEDIA_MANAGER_AUTO_MENU', true),
    'ability' => env('MOONSHINE_MEDIA_MANAGER_ABILITY'),
    'default_view' => 'table',
];
```

## Параметры

| Параметр | По умолчанию | Описание |
|----------|-------------|----------|
| `disk` | `public` | Диск файлового хранилища (только локальный) |
| `allowed_ext` | `jpg,jpeg,png,...` | Разрешённые расширения (с MIME-проверкой). **Пустое значение = загрузки запрещены** (deny-by-default) |
| `max_file_size` | `10485760` (10 MB) | Макс. размер загружаемого файла |
| `rename_duplicates` | `true` | Переименовать дубликат (`file.jpg` → `file-1.jpg`) вместо перезаписи |
| `auto_menu` | `true` | Автоматически добавить в боковое меню |
| `ability` | `null` | Gate ability для авторизации (`null` = без проверки) |
| `blocked_paths` | `['framework', 'logs']` | Запрещённые top-level каталоги диска (первый сегмент пути, без учёта регистра) |
| `default_view` | `table` | Вид по умолчанию: `table` или `grid` |

## Политика загрузок (security)

- **Итоговое расширение whitelisted обязательно:** расширение сохраняемого файла обязано явно входить в `allowed_ext`; клиентское расширение само по себе ничего не даёт.
- **Контент сверяется с расширением:** если содержимое файла распознаётся как другой известный тип (например, PHP-payload под именем `.jpg`) — загрузка отклоняется.
- **Пустой `allowed_ext` запрещает все загрузки** с локализованным сообщением об ошибке.
- **SVG санитизируется:** при загрузке/замене из SVG вырезаются `<script>`, `<foreignObject>`, event-атрибуты (`on*`) и `javascript:`-ссылки; повреждённый XML отклоняется.
- **Имена файлов транслитерируются:** `Отчёт.jpg` → `Otcet.jpg`; опасные расширения (`php`, `phtml`, `phar`, `htaccess`…) блокируются в любом сегменте имени.
- **Мутации под блокировкой:** upload/move/replace/new-folder выполняются под atomic cache-lock (с graceful-fallback на драйверах без поддержки блокировок).

## ENV-переменные

| Переменная | Что управляет |
|-----------|---------------|
| `MOONSHINE_MEDIA_MANAGER_MAX_FILE_SIZE` | Лимит размера файла (байты) |
| `MOONSHINE_MEDIA_MANAGER_RENAME_DUPLICATES` | Переименование дубликатов |
| `MOONSHINE_MEDIA_MANAGER_AUTO_MENU` | Автопункт меню |
| `MOONSHINE_MEDIA_MANAGER_ABILITY` | Gate ability |

## Authorization (опционально)

По умолчанию любой аутентифицированный юзер MoonShine имеет полный доступ к менеджеру. Для ограничения — задайте Gate ability в `.env`:

```bash
MOONSHINE_MEDIA_MANAGER_ABILITY=manage-media
```

И определите Gate в `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage-media', function (User $user) {
    return $user->hasRole('admin');
});
```

Теперь только админы имеют доступ. Остальные получают 403.

## Слои модальных окон (z-index)

Менеджер использует выделенные слои поверх шкалы ядра MoonShine, поэтому его окна **всегда** выше сторонних оверлеев на модальном слое ядра (1100) — например, picker'а `moonshine-flexible-layouts`:

| Элемент | Слой | z |
|---|---|---|
| Браузер (off-canvas) | `--mm-z-browser` | `calc(var(--z-modal, 1100) + 50)` = 1150 |
| Все диалоги менеджера (upload / rename / delete / preview / move / replace / url / new-folder) | `--mm-z-dialog` | `calc(var(--z-menu, 1200) + 50)` = 1250 |

Выше менеджера остаются только тосты ядра (`--z-toast: 1300`) — ошибки загрузки видны всегда.

Значения наследуются от токенов ядра через `calc()`, поэтому при смене шкалы ядра слои пересчитаются автоматически. Оба токена можно переопределить в CSS хоста:

```css
:root {
    --mm-z-browser: 1200;
    --mm-z-dialog: 1300;
}
```

## See Also

- [Установка](getting-started.ru.md) — публикация конфига и первый запуск
- [API и события](api.ru.md) — JSON-контракт эндпоинтов
- [Старые версии](legacy-versions.ru.md) — настройка через `config/moonshine.php` (v3/v2)
