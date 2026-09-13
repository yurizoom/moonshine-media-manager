[Back to README](../README.md) · [Конфигурация →](configuration.md)

# Установка и быстрый старт

## Требования

- PHP 8.2+
- Laravel с установленным MoonShine 4.0+
- Локальный диск Laravel Storage (по умолчанию `public`)

## Установка

```bash
composer require yurizoom/moonshine-media-manager
```

После установки опубликуйте ассеты и конфиг:

```bash
php artisan vendor:publish --tag=moonshine-media-manager-assets
php artisan vendor:publish --tag=moonshine-media-manager-config
```

## Подключение OffCanvas

Менеджер работает через глобальную offcanvas-панель. Подключите компонент в `app/MoonShine/Layouts/MoonShineLayout.php`:

```php
use YuriZoom\MoonShineMediaManager\Components\MediaManagerOffCanvas;

final class MoonShineLayout extends AppLayout
{
    protected function getContentComponents(): array
    {
        return [
            ...parent::getContentComponents(),
            MediaManagerOffCanvas::make(),
        ];
    }
}
```

`MediaManagerOffCanvas` — глобальный компонент, рендерит offcanvas-панель с файловым менеджером. Именно через неё работают все picker-поля на страницах. Assets загружаются автоматически через компонент — вручную подключать ничего не нужно.

## Добавление в меню (опционально)

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

## Проверка установки

1. Откройте админ-панель — в боковом меню появился пункт менеджера (при включённом `auto_menu`)
2. Загрузите файл через кнопку «Загрузить» — файл появится в списке
3. Файл физически лежит на диске из `config('filesystems.default')` (или из настройки `disk`)

## Что дальше

- [Конфигурация](configuration.md) — диск, расширения, лимиты, авторизация, z-index слои
- [Поле MediaManagerPicker](picker-field.md) — выбор файлов прямо в формах
- [API и события](api.md) — AJAX-эндпоинты и события для интеграции

## See Also

- [Конфигурация](configuration.md) — все параметры конфига и env-переменные
- [Поле MediaManagerPicker](picker-field.md) — интеграция менеджера в формы ресурсов
- [Разработка](development.md) — сборка ассетов из исходников
