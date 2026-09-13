---
name: moonshine-package
description: Разработка пакетов (модулей) для MoonShine 4 — админ-панели на Laravel. Use when creating or extending MoonShine 4 packages: ServiceProvider with CoreContract, custom Fields extending MoonShine\UI\Fields\Field, Pages, Components, MenuManager, AssetManager, blade views, localization, config publishing, events. Триггеры: MoonShine, moonshine-package, ServiceProvider, custom field, admin panel package, media manager, offcanvas, picker field.
license: MIT
metadata:
  author: ai-factory
  version: "1.0.0"
  category: backend
  language: ru
---

# Разработка пакетов для MoonShine 4

Паттерны создания и расширения пакетов MoonShine 4 (`moonshine/moonshine` ^4.0) на Laravel. Основано на официальной документации и проверенных паттернах реальных пакетов (moonshine-media-manager, moonshine-image-editor).

## Когда использовать

- Создание нового пакета/модуля для MoonShine 4
- Добавление кастомных полей, страниц, компонентов в пакет
- Интеграция пакета с ядром MoonShine (меню, ассеты, цвета, авторизация)
- Расширение чужих MoonShine-пакетов через события и реестры

## Базовая структура пакета

```
vendor/package-name/
├── composer.json              # PSR-4 + extra.laravel.providers (автообнаружение)
├── config/package-name.php    # публикуемый конфиг
├── routes/routes.php          # loadRoutesFrom
├── src/
│   ├── PackageServiceProvider.php
│   ├── Fields/                # кастомные поля (extend MoonShine\UI\Fields\*)
│   ├── Pages/                 # extend MoonShine\Laravel\Pages\Page
│   ├── Components/            # extend MoonShine\UI\Component
│   ├── Controllers/           # extend MoonShine\Laravel\Http\Controllers\MoonShineController
│   ├── Events/                # события для интеграции
│   ├── Contracts/             # интерфейсы реестров/сервисов
│   └── Support/               # хелперы, валидация, ассеты
├── resources/
│   ├── views/                 # blade, namespace 'package-name::'
│   ├── js/ + css/             # vanilla JS/CSS (Alpine-совместимые)
│   └── lang/{en,ru}/          # loadTranslationsFrom
└── dist/                      # собранные ассеты (Vite)
```

## Workflow создания пакета

1. **composer.json** — PSR-4 autoload + `extra.laravel.providers` для автообнаружения ServiceProvider (обязательно, иначе пакет не подхватится)
2. **ServiceProvider** — `boot(CoreContract $core)` для регистрации страниц/ресурсов; см. паттерны в [references/GUIDE.md](references/GUIDE.md)
3. **Конфиг** — `mergeConfigFrom()` в `register()`, `publishes([...], 'package-name-config')` в `boot()`
4. **Виды** — `loadViewsFrom(__DIR__.'/../resources/views', 'package-name')`; обращения `'package-name::view.name'`
5. **Локализация** — `loadTranslationsFrom(__DIR__.'/../resources/lang', 'package-name')`; в коде только `__('package-name::file.key')`, сырых строк в UI не должно быть
6. **Ассеты** — publishes в `public_path('vendor/package-name/')` тегом `package-name-assets`; поля подключают их через `assets(): array`
7. **Маршруты** — `loadRoutesFrom()`; контроллеры наследуют `MoonShineController`
8. **Авторизация** — опциональный Gate ability из конфига, проверка guard-клаузой в начале каждого экшена

## Ключевые паттерны ядра

### ServiceProvider с инъекцией менеджеров

```php
public function boot(CoreContract $core, MenuManagerContract $menu): void
{
    $core->pages([MyPage::class]);
    $menu->add([MenuItem::make(MyPage::class)]);
}
```

Доступные контракты: `CoreContract` (resources/pages), `MenuManagerContract` (меню), `AssetManagerContract` (Css/Js), `ColorManagerContract` (темы), `ConfiguratorContract` (authorizationRules).

### Кастомное поле

```php
final class MyField extends Field
{
    protected string $view = 'my-package::fields.my-field';

    public function assets(): array
    {
        return [/* Css::make(...), Js::make(...) */];
    }

    protected function viewData(): array { return []; }
    protected function resolvePreview(): Renderable|string { return ''; }
}
```

Fluent-сеттеры возвращают `static`. Поля имеют три режима: default (формы), preview (таблицы), raw (экспорт) — переопределяйте `resolvePreview()` для preview и `resolveRawValue()` для raw.

### Страница

```php
class MyPage extends Page
{
    protected string $title = 'My Page';
    protected function components(): iterable { return [...]; }
}
```

## Ограничения

### MUST DO

- `declare(strict_types=1)` в каждом PHP-файле
- Все пользовательские строки — через `__()` с namespace пакета (ru + en минимум)
- Ошибки контроллеров — JSON `{status: false, message}`; ожидаемые исключения через доменное `PackageException`, неожиданные — `report()`
- Ассеты полей/компонентов — через `assets()`, не через layout хоста
- Конфиг читается с fallback: `config('package-name.key', default)`

### MUST NOT DO

- Не хардкодить пути ассетов в blade — только через `MediaAssets`-подобные хелперы или publishes-конфиг
- Не полагаться на структуру `app/` хоста — пакет должен работать в любом Laravel-приложении с MoonShine 4
- Не выводить сырые сообщения исключений наружу в production (только local)
- Не обходить авторизацию экшенов, если в конфиге задан ability

## Подробнее

- [references/GUIDE.md](references/GUIDE.md) — полный справочник: ServiceProvider, поля, страницы, ассеты, события, реестры расширений, z-index слои
- [references/EXAMPLES.md](references/EXAMPLES.md) — рабочие примеры из реальных пакетов
