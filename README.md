Media manager for MoonShine
============================

Media manager в MoonShine.

## Скриншот

![wx20170809-165644](https://raw.githubusercontent.com/yurizoom/moonshine-media-manager/main/blob/screenshot.jpg)

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
