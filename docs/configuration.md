[← Installation](getting-started.md) · **English** | [Русский](configuration.ru.md) · [Back to README](../README.md) · [MediaManagerPicker field →](picker-field.md)

# Configuration

## Config file

The config is published to `config/media-manager.php` (a standalone file). A fallback is supported — keys found in `config/moonshine.php` → `media_manager` are applied too. Priority: standalone file > `moonshine.php` > package defaults.

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

## Options

| Option | Default | Description |
|--------|---------|-------------|
| `disk` | `public` | Storage disk (local only) |
| `allowed_ext` | `jpg,jpeg,png,...` | Allowed extensions (with MIME checks). **An empty value = uploads denied** (deny-by-default) |
| `max_file_size` | `10485760` (10 MB) | Maximum uploaded file size |
| `rename_duplicates` | `true` | Rename a duplicate (`file.jpg` → `file-1.jpg`) instead of overwriting |
| `auto_menu` | `true` | Add the manager to the sidebar automatically |
| `ability` | `null` | Gate ability for authorization (`null` = no check) |
| `blocked_paths` | `['framework', 'logs']` | Forbidden top-level disk directories (first path segment, case-insensitive) |
| `default_view` | `table` | Default view: `table` or `grid` |

## Upload policy (security)

- **The final extension must be whitelisted:** the stored file's extension has to be explicitly listed in `allowed_ext`; the client-side extension alone proves nothing.
- **Content is matched against the extension:** when the file content is detected as another known type (e.g. a PHP payload named `.jpg`), the upload is rejected.
- **An empty `allowed_ext` denies all uploads** with a localized error message.
- **SVG is sanitized:** on upload/replace, `<script>`, `<foreignObject>`, event attributes (`on*`) and `javascript:` links are stripped; malformed XML is rejected.
- **File names are transliterated:** `Отчёт.jpg` → `Otcet.jpg`; dangerous extensions (`php`, `phtml`, `phar`, `htaccess`…) are blocked in any segment of the name.
- **Mutations under a lock:** upload/move/replace/new-folder run under an atomic cache lock (with a graceful fallback on lock-less drivers).

## ENV variables

| Variable | Controls |
|----------|----------|
| `MOONSHINE_MEDIA_MANAGER_MAX_FILE_SIZE` | File size limit (bytes) |
| `MOONSHINE_MEDIA_MANAGER_RENAME_DUPLICATES` | Duplicate renaming |
| `MOONSHINE_MEDIA_MANAGER_AUTO_MENU` | Automatic menu item |
| `MOONSHINE_MEDIA_MANAGER_ABILITY` | Gate ability |

## Authorization (optional)

By default every authenticated MoonShine user has full access to the manager. To restrict it, set a Gate ability in `.env`:

```bash
MOONSHINE_MEDIA_MANAGER_ABILITY=manage-media
```

And define the Gate in `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage-media', function (User $user) {
    return $user->hasRole('admin');
});
```

Now only admins have access; everyone else gets a 403.

## Modal layers (z-index)

The manager uses dedicated layers above the core MoonShine scale, so its windows sit **always** above third-party overlays on the core modal layer (1100) — for example the `moonshine-flexible-layouts` picker:

| Element | Layer | z |
|---|---|---|
| Browser (off-canvas) | `--mm-z-browser` | `calc(var(--z-modal, 1100) + 50)` = 1150 |
| All manager dialogs (upload / rename / delete / preview / move / replace / url / new-folder) | `--mm-z-dialog` | `calc(var(--z-menu, 1200) + 50)` = 1250 |

Only the core toasts (`--z-toast: 1300`) stay above the manager — upload errors are always visible.

The values inherit from core tokens via `calc()`, so the layers recalculate automatically when the core scale changes. Both tokens can be overridden in the host's CSS:

```css
:root {
    --mm-z-browser: 1200;
    --mm-z-dialog: 1300;
}
```

## See Also

- [Installation](getting-started.md) — publishing the config and first run
- [API & events](api.md) — endpoint JSON contract
- [Legacy versions](legacy-versions.md) — configuration via `config/moonshine.php` (v3/v2)
