# MoonShine Media Manager

Файловый менеджер для [MoonShine](https://moonshine-laravel.com/).

### Поддержка версий

| MoonShine | Пакет | Документация |
|-----------|-------|-------------|
| 4.0+      | 4.x   | [Ниже ↓](#настройка-v4-moonshine-4) |
| 4.0+      | 3.x   | [Ниже ↓](#настройка-v3-moonshine-3) |
| 3.0+      | 2.x   | |
| 2.0+      | 1.x   | |

## Установка

```bash
composer require yurizoom/moonshine-media-manager
```

---

## Настройка v4 (MoonShine 4+)

Полностью AJAX — загрузка, удаление, переименование, навигация по папкам без перезагрузки страницы.

После установки опубликуйте JS-ассет:

```bash
php artisan vendor:publish --tag=media-manager-assets
```

### Конфигурация

Добавьте в `config/moonshine.php`:

```php
'media_manager' => [
    'auto_menu' => true,
    'disk' => config('filesystem.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,gif,webp,avif,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,mp3,mp4,wav,avi,mov',
    'default_view' => 'table',
],
```

### Подключение OffCanvas, JS и CSS

В `app/MoonShine/Layouts/MoonShineLayout.php`:

```php
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use YuriZoom\MoonShineMediaManager\Components\MediaManagerOffCanvas;

final class MoonShineLayout extends AppLayout
{
    protected function assets(): array
    {
        return [
            ...parent::assets(),
            Js::make('/vendor/media-manager/media-manager.js'),
            Css::make('/vendor/media-manager/media-manager.css'),
        ];
    }

    protected function getContentComponents(): array
    {
        return [
            ...parent::getContentComponents(),
            MediaManagerOffCanvas::make(),
        ];
    }
}
```

`MediaManagerOffCanvas` — глобальный компонент, рендерит offcanvas-панель с файловым менеджером. Именно через неё работают все picker-поля на страницах.

### Добавление в меню (опционально)

Если `auto_menu` включён (по умолчанию), пункт появится автоматически. Для ручного размещения:

```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
{
    return [
        MenuItem::make(MediaManagerPage::class),
    ];
}
```

### Поле MediaManagerPicker

Поле для выбора файлов из менеджера прямо в форме. Работает с обычными полями, Json и Layouts.

**Базовое использование:**

```php
use YuriZoom\MoonShineMediaManager\Fields\MediaManagerPicker;

// Одно изображение
MediaManagerPicker::make('Изображение', 'image')
    ->allowedTypes(['image']),

// Множественный выбор с перетаскиванием
MediaManagerPicker::make('Галерея', 'images')
    ->multiple()
    ->allowedTypes(['image']),
```

**Фильтрация файлов** — по типу или по расширению, можно комбинировать:

```php
// По типу (из менеджера): image, video, audio, pdf, word, code, zip, txt, ppt
->allowedTypes(['image'])
->allowedTypes(['image', 'pdf'])

// По расширению (точный контроль):
->allowedExtensions(['jpg', 'png', 'webp'])
->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx'])
```

**С Json:**

```php
use MoonShine\UI\Fields\Json;

Json::make('Мета', 'meta')
    ->fields([
        Text::make('Заголовок', 'title'),
        MediaManagerPicker::make('Изображение', 'image')
            ->allowedTypes(['image']),
        MediaManagerPicker::make('Документ', 'document')
            ->allowedExtensions(['pdf', 'doc', 'docx']),
        MediaManagerPicker::make('Файлы', 'files')
            ->multiple()
            ->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx']),
    ]),
```

**С Layouts:**

```php
use MoonShine\Layouts\Fields\Layouts;

Layouts::make('Контент', 'content')
    ->addLayout('Блок с изображением', 'image_block', [
        Text::make('Заголовок', 'title'),
        MediaManagerPicker::make('Изображение', 'image')
            ->allowedTypes(['image']),
    ])
    ->addLayout('Файловый блок', 'files_block', [
        Text::make('Заголовок', 'title'),
        MediaManagerPicker::make('Документы', 'documents')
            ->multiple()
            ->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx']),
    ]),
```

### Возможности v4

- **AJAX навигация** — переход по папкам без перезагрузки
- **Загрузка файлов** — множественная загрузка с проверкой расширений
- **Создание папок** — прямо из интерфейса
- **Переименование / перемещение** — через модалку с указанием нового пути
- **Удаление** — с подтверждением
- **Скачивание** — по клику
- **URL файла** — просмотр ссылки с копированием
- **Два вида** — таблица и сетка (grid)
- **Быстрый переход** — ввод пути вручную
- **Picker-поле** — выбор файлов из менеджера прямо в форме
- **Drag-and-drop** — перетаскивание для изменения порядка в picker
- **Подсветка навигации** — при переходе к файлу из picker
- **Проверка файлов** — детекция несуществующих файлов (broken state)
- **Превью** — клик по изображению открывает полноразмерный просмотр
- **Не-изображения** — отображение иконки с расширением для PDF, DOC и т.д.
- **Layouts / Json** — полная интеграция с moonshine/layouts-field и Json-полями

### Конфигурация v4

| Параметр | По умолчанию | Описание |
|----------|-------------|----------|
| `auto_menu` | `true` | Автоматически добавить в боковое меню |
| `disk` | `public` | Диск файлового хранилища (только локальный) |
| `allowed_ext` | `jpg,jpeg,png,...` | Разрешённые для загрузки расширения (серверная проверка) |
| `default_view` | `table` | Вид по умолчанию: `table` или `list` |

---

## Настройка v3 (MoonShine 3)

Добавьте в `config/moonshine.php`:

```php
'media_manager' => [
    'auto_menu' => true,
    'disk' => config('filesystem.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,pdf,doc,docx,zip',
    'default_view' => 'table',
],
```

Для добавления в меню:

```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
{
    return [
        MenuItem::make(new MediaManagerPage()),
    ];
}
```

---

## Настройка v2 (MoonShine 2)

Если необходимо изменить настройки, добавьте в файл `config/moonshine.php`:

```php
[
    'media_manager' => [
        // Автоматическое добавление в меню
        'auto_menu' => true,
        // Корневая директория
        'disk' => config('filesystem.default', 'public'),
        // Разрешенные для загрузки расширения файлов
        'allowed_ext' => 'jpg,jpeg,png,pdf,doc,docx,zip',
        // Вид менеджера по-умолчанию
        'default_view' => 'table',
    ]
]
```

Для добавления в меню в `app/MoonShine/Layouts/MoonShineLayout.php`:

```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
{
    return [
        MenuItem::make(new MediaManagerPage()),
    ];
}
```

---

## Разработка

Для сборки ассетов (JS + CSS) из исходников:

```bash
cd modules/moonshine-media-manager
npm install
npm run build
```

Готовые файлы появятся в `dist/`. Для публикации в проекте:

```bash
php artisan vendor:publish --tag=media-manager-assets --force
```

---

## Лицензия

[The MIT License (MIT)](LICENSE).
