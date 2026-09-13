[← Разработка](development.md) · [Back to README](../README.md)

# Старые версии (v3 / v2)

## Настройка v3 (пакет 3.x, MoonShine 4)

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

## Настройка v2 (пакет 2.x, MoonShine 3)

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

## See Also

- [Установка](getting-started.md) — актуальная настройка v4
- [Конфигурация](configuration.md) — полный список параметров v4
