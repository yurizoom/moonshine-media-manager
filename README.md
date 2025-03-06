Media manager for MoonShine 3
============================

Media manager в MoonShine.

### Поддержка версий MoonShine

| MoonShine   | Пакет       |
|-------------|-------------|
| 2.0+        | 1.0+        |
| 3.0+        | 2.0+        |

## Скриншот

![wx20170809-165644](https://raw.githubusercontent.com/yurizoom/moonshine-media-manager/main/blob/screenshot.png)

## Установка

```
$ composer require yurizoom/moonshine-media-manager
```

## Настройка

Если необходимо изменить настройки, добавьте в файле config/moonshine.php:

```php
[
    'media-manager' => [
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

### Добавление в меню

Для того чтобы добавить меню в другое место, вставьте следующий код в app/MoonShine/Layouts/MoonShineLayout.php:
```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
    {
        return [
            ...
            
            MenuItem::make(
                __('Media manager'),
                new MediaManagerPage(),
            ),
            
            ...
        ];
    }
```

Лицензия
------------
[The MIT License (MIT)](LICENSE).
