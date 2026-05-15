# MoonShine Media Manager

Файловый менеджер для [MoonShine](https://moonshine-laravel.com/) на базе Alpine.js.

Полностью AJAX — загрузка, удаление, переименование, навигация по папкам — всё без перезагрузки страницы.

### Поддержка версий

| MoonShine | Пакет |
|-----------|-------|
| 2.0+      | 1.x   |
| 3.0+      | 2.x   |
| 4.0+      | 3.x   |

## Установка

```bash
composer require yurizoom/moonshine-media-manager
```

Опубликуйте JS-ассет:

```bash
php artisan vendor:publish --tag=media-manager-assets
```

## Настройка

Добавьте в `config/moonshine.php`:

```php
'media_manager' => [
    'auto_menu' => true,
    'disk' => config('filesystem.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,gif,webp,avif,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,mp3,mp4,wav,avi,mov',
    'default_view' => 'table',
],
```

### Подключение OffCanvas и JS

В `app/MoonShine/Layouts/MoonShineLayout.php`:

```php
use MoonShine\AssetManager\Js;
use YuriZoom\MoonShineMediaManager\Components\MediaManagerOffCanvas;

final class MoonShineLayout extends AppLayout
{
    protected function assets(): array
    {
        return [
            ...parent::assets(),
            Js::make('/vendor/media-manager/media-manager.js'),
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

## Поле MediaManagerPicker

Поле для выбора файлов из менеджера прямо в форме. Работает с обычными полями, Json и Layouts.

### Базовое использование

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

### Фильтрация файлов

Два метода — по типу или по расширению. Можно комбинировать.

**По типу** (соответствует типам из менеджера):

```php
->allowedTypes(['image'])      // только изображения
->allowedTypes(['pdf'])        // только PDF
->allowedTypes(['image', 'pdf']) // изображения + PDF
```

Доступные типы: `image`, `video`, `audio`, `pdf`, `word`, `code`, `zip`, `txt`, `ppt`.

**По расширению** (точный контроль):

```php
->allowedExtensions(['jpg', 'png', 'webp'])
->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx'])
```

### Использование с Json

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

### Использование с Layouts

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

## Возможности

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
- **Подсветка навигации** — при переходе к файлу из picker, подсветка в менеджере
- **Проверка файлов** — детекция несуществующих изображений (broken state)
- **Превью** — клик по изображению открывает модалку с полноразмерным просмотром
- **Не-изображения** — отображение иконки с расширением для PDF, DOC и т.д.

## Конфигурация

| Параметр | По умолчанию | Описание |
|----------|-------------|----------|
| `auto_menu` | `true` | Автоматически добавить в боковое меню |
| `disk` | `public` | Диск файлового хранилища (только локальный) |
| `allowed_ext` | `jpg,jpeg,png,...` | Разрешённые для загрузки расширения (серверная проверка) |
| `default_view` | `table` | Вид по умолчанию: `table` или `list` |

## Лицензия

[The MIT License (MIT)](LICENSE).
