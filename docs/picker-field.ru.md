[← Конфигурация](configuration.ru.md) · [English](picker-field.md) | **Русский** · [Back to README](../README.ru.md) · [API и события →](api.ru.md)

# Поле MediaManagerPicker

Поле для выбора файлов из менеджера прямо в форме. Работает с обычными полями, Json и Layouts.

## Базовое использование

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

Для работы поля требуется подключённый `MediaManagerOffCanvas` в layout (см. [Установка](getting-started.ru.md)).

## Фильтрация файлов

По типу или по расширению, можно комбинировать:

```php
// По типу (из менеджера): image, video, audio, pdf, word, code, zip, txt, ppt
->allowedTypes(['image'])
->allowedTypes(['image', 'pdf'])

// По расширению (точный контроль):
->allowedExtensions(['jpg', 'png', 'webp'])
->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx'])
```

## С Json

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

## С Layouts

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

## Поведение

- **Фильтры enforced на upload** — файлы, загружаемые из открытого picker'ом менеджера, проверяются против `allowedTypes`/`allowedExtensions` на сервере: несовпадающий файл отклоняется с 400
- **Пикер запоминает папку** — при повторном открытии возвращается в последнюю папку
- **Drag-and-drop reorder** — перетаскивание для изменения порядка выбранных файлов
- **Фильтр WebP/AVIF** — для picker-полей с `allowedExtensions`, включающим webp/avif, фильтр «Скрыть WebP/AVIF» автоматически отключается, чтобы выбор таких файлов оставался возможным
- **Preview** — в превью (таблицы ресурсов) поле рендерит `Thumbnails` ядра MoonShine

## See Also

- [API и события](api.ru.md) — эндпоинты, которыми пользуется picker под капотом
- [Конфигурация](configuration.ru.md) — disk, allowed_ext и лимиты
- [Установка](getting-started.ru.md) — подключение MediaManagerOffCanvas
