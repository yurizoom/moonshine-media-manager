Media manager for MoonShine
============================

Media manager в MoonShine.

## Скриншот

![wx20170809-165644](https://raw.githubusercontent.com/yurizoom/moonshine-media-manager/main/blob/screenshot.png)

## Установка

```
$ composer require yurizoom/moonshine-media-manager
```

## Настройка

В файле config/moonshine.php добавьте конфигурации.

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

Для того чтобы добавить меню в другое место, вставьте следующий код в app/Providers/MoonShineServiceProvider.php:
```php
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

protected function menu(): array
    {
        return [
            ...
            
            MenuItem::make(
                static fn () => __('Media manager'),
                new MediaManagerPage(),
            ),
            
            ...
        ];
    }
```

Лицензия
------------
[The MIT License (MIT)](LICENSE).
