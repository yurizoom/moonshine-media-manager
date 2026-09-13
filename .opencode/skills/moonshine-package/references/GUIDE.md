# Справочник: разработка пакетов для MoonShine 4

Синтез официальной документации MoonShine 4.x и паттернов production-пакетов (moonshine-media-manager, moonshine-image-editor).

## 1. ServiceProvider

### Регистрация страниц и ресурсов

```php
namespace Author\MoonShineMyPackage;

use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;

class MyPackageServiceProvider extends ServiceProvider
{
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([MyPackageResource::class])
            ->pages([MyPackagePage::class]);
    }
}
```

### Меню

```php
use MoonShine\Contracts\MenuManager\MenuManagerContract;

public function boot(CoreContract $core, MenuManagerContract $menu): void
{
    $menu->add([MenuItem::make(MyPackagePage::class)]);
}
```

### Ассеты и цвета

```php
use MoonShine\Contracts\AssetManager\AssetManagerContract;
use MoonShine\AssetManager\InlineCss;

public function boot(CoreContract $core, AssetManagerContract $assets): void
{
    $assets->add([InlineCss::make('body {background: red;}')]);
}
```

```php
use MoonShine\Contracts\ColorManager\ColorManagerContract;

public function boot(CoreContract $core, ColorManagerContract $colors): void
{
    $colors->background('#A3C3D9')->text('#A3C3D9')->primary('#CCD6EB');
}
```

### Кастомные правила авторизации

```php
use MoonShine\Contracts\Core\DependencyInjection\ConfiguratorContract;

public function boot(ConfiguratorContract $configurator): void
{
    $configurator->authorizationRules(
        static function (ResourceContract $resource, Model $user, Ability $ability): bool {
            return true;
        }
    );
}
```

### Проталкивание компонентов на чужие страницы

```php
public function boot(): void
{
    ProfilePage::pushComponent(fn () => MyPackageComponent::make());
}
```

### Автообнаружение в composer.json (обязательно)

```json
"extra": {
    "laravel": {
        "providers": [
            "Author\\MoonShineMyPackage\\MyPackageServiceProvider"
        ]
    }
}
```

### Production-паттерн (из moonshine-image-editor)

```php
public function register(): void
{
    $this->mergeConfigFrom(__DIR__.'/../config/image-editor.php', 'moonshine.image_editor');
    $this->loadTranslationsFrom(__DIR__.'/../lang', 'image-editor');
    $this->app->singleton(SettingsRepositoryInterface::class, SettingsService::class);
}

public function boot(CoreContract $core, MenuManagerContract $menu): void
{
    $this->loadViewsFrom(__DIR__.'/../resources/views', 'image-editor');
    $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');

    $this->publishes([
        __DIR__.'/../dist/image-editor.js' => public_path('vendor/image-editor/image-editor.js'),
    ], 'image-editor-assets');

    Event::listen(MediaManagerFileUploaded::class, OptimizeUploadedImage::class);

    $core->pages([ImageSettingsPage::class]);
    $menu->add([MenuItem::make(ImageSettingsPage::class)]);

    if ($this->app->runningInConsole()) {
        $this->commands([MyCommand::class]);
    }
}
```

## 2. Поля

### Три визуальных режима

- **default** — элемент формы (`<input>`, `<select>`...)
- **preview** — отображение значения в таблицах (`resolvePreview()`)
- **raw** — исходное значение для экспорта (`resolveRawValue()`)

Зафиксировать режим: `->defaultMode()`, `->previewMode()`, `->rawMode()`.

### Жизненный цикл

1. Поле объявлено в ресурсе
2. `FormBuilder`/`TableBuilder` заполняет поле (`fill()`, `changeFill()`, `afterFill()`)
3. Рендер (смена состояний: `changePreview()`, `changeRender()`)
4. Apply при сохранении: `onBeforeApply()` → `onApply()` → `afterApply()`

```php
Text::make('Thumbnail', 'thumbnail')
    ->onApply(function (Model $item, $value, Text $field) {
        if ($value) {
            $item->thumbnail = Storage::put('thumbnail.jpg', file_get_contents($value));
        }
        return $item;
    });
```

### Кастомное поле (генерация: `php artisan moonshine:field`)

```php
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\UI\Fields\Textarea;

final class Quill extends Textarea
{
    protected string $view = 'moonshine-quill::fields.quill';

    public function assets(): array
    {
        return [
            Css::make('/css/moonshine/quill/quill.snow.css'),
            Js::make('/js/moonshine/quill/quill.js'),
            Js::make('/js/moonshine/quill/quill-init.js'),
        ];
    }
}
```

Blade-вид поля + Alpine.js инициализация:

```blade
<div x-data="quill">
    <div class="ql-editor" :id="$id('quill')">{!! $value ?? '' !!}</div>
    <x-moonshine::form.textarea
        :attributes="$attributes->merge(['class' => 'ql-textarea', 'style' => 'display: none;'])->except('x-bind:id')"
    >{!! $value ?? '' !!}</x-moonshine::form.textarea>
</div>
```

```js
document.addEventListener('alpine:init', () => {
    Alpine.data('quill', () => ({
        init() {
            const t = this;
            this.$nextTick(function () {
                const quill = new Quill(`#${t.$root.querySelector('.ql-editor').id}`, { theme: 'snow' });
                quill.on('text-change', () => {
                    const textarea = t.$root.querySelector('.ql-textarea');
                    textarea.value = t.$root.querySelector('.ql-editor').innerHTML || '';
                    textarea.dispatchEvent(new Event('change'));
                });
            });
        },
    }));
});
```

### Production-паттерн поля-пикера (MediaManagerPicker)

```php
class MediaManagerPicker extends Field
{
    protected string $view = 'moonshine-media-manager::fields.media-manager-picker';
    protected bool $isMultiple = false;
    protected array $allowedTypes = [];

    public function multiple(bool $condition = true): static
    {
        $this->isMultiple = $condition;
        return $this; // fluent
    }

    protected function viewData(): array
    {
        return ['pickerConfig' => [
            'value' => $this->toValue(),
            'multiple' => $this->isMultiple,
            'baseUrl' => $this->getStorageUrl(),
        ]];
    }

    protected function resolvePreview(): Renderable|string
    {
        return Thumbnails::make($this->getStorageUrl().'/'.$this->toValue())->render();
    }

    protected function assets(): array
    {
        return MediaAssets::get(); // централизованный хелпер путей
    }
}
```

## 3. Страницы

```php
use MoonShine\Laravel\Pages\Page;

class CustomPage extends Page
{
    protected string $title = 'CustomPage';
    protected string $subtitle = 'Subtitle';
    protected ?string $layout = AppLayout::class; // или #[Layout(AppLayout::class)]

    protected function components(): iterable
    {
        return [Grid::make([/* Column + Box */])];
    }
}
```

Хуки жизненного цикла:

- `prepareBeforeRender()` — проверки перед рендером (`abort(403)` при отсутствии доступа)
- `onLoad()` — страница активна; здесь добавляются ассеты через `$this->getAssetManager()->add(...)`
- `booted()` — момент создания инстанса
- `modifyResponse(): ?Response` — подмена ответа (redirect)
- `getBreadcrumbs(): array` — хлебные крошки
- `menu(): array` — доп. меню страницы (SecondBar)
- `modifyLayout(LayoutContract $layout)` — динамическая правка layout

Страница, созданная вручную (не через `moonshine:page`), обязана быть зарегистрирована в `$core->pages()`.

## 4. Traits для расширения ресурсов

```php
trait HasMyPackageTrait
{
    public function loadHasMyPackageTrait(): void  // magic load{TraitName}()
    {
        $this->getFormPage()->addAssets([
            Js::make('vendor/my-package/js/app.js'),
            Css::make('vendor/my-package/css/app.css'),
        ]);
    }

    public function modifyFormComponent(ComponentContract $component): ComponentContract
    {
        return parent::modifyFormComponent($component)->fields([
            Modal::make('This is my package modal.', ''),
            ...$component->getFields()->toArray(),
        ]);
    }
}
```

## 5. Интеграция между пакетами

### События

Пакет диспатчит доменные события; потребители вешают слушатели без жёсткой зависимости:

```php
// Пакет-поставщик
MediaManagerFileUploaded::class // -> dispatch($path, $disk)

// Пакет-потребитель (в своём ServiceProvider)
Event::listen(MediaManagerFileUploaded::class, OptimizeUploadedImage::class);
```

### Реестр расширений

```php
// Интерфейс в пакете-хосте
interface MediaManagerRegistryInterface
{
    public function addFileAction(string $name, array $definition): self;
    public function addToolbarAction(string $name, array $definition): self;
}

// Пакет-потребитель регистрирует действия через resolving()
$this->app->resolving(MediaManagerRegistryInterface::class,
    function (MediaManagerRegistryInterface $registry): void {
        $registry->addFileAction('image-editor', [
            'icon' => 'sparkles',
            'label' => __('image-editor::image-editor.edit_image'),
            'x-show' => '!file.isDir && file.type === "image"',
            'click' => '$store.ic.open(file)',
        ]);
    }
);
```

## 6. Контроллеры и JSON-ответы

```php
final class MyController extends MoonShineController
{
    private function authorizeAction(): void
    {
        $ability = config('moonshine.my_package.ability');
        if ($ability) {
            Gate::authorize($ability);
        }
    }

    public function index(MoonShineRequest $request): JsonResponse
    {
        $this->authorizeAction();
        // ... основная логика
        return response()->json(['status' => true, 'data' => $data]);
    }

    private function errorResponse(Throwable $e, int $status = 400): JsonResponse
    {
        if (! $e instanceof MyPackageException) {
            report($e); // неожиданные — в лог
        }
        return response()->json([
            'status' => false,
            'message' => app()->isLocal()
                ? $e->getMessage()
                : __('my-package::my-package.error.operation_failed'),
        ], $status);
    }
}
```

## 7. z-index слои поверх шкалы ядра

Пакетные offcanvas/модалы, которые должны быть выше чужих оверлеев, наследуют токены ядра через `calc()`:

```css
--mm-z-browser: calc(var(--z-modal, 1100) + 50);  /* 1150 */
--mm-z-dialog: calc(var(--z-menu, 1200) + 50);    /* 1250 */
```

Выше остаются только тосты ядра (`--z-toast: 1300`). При смене шкалы ядра сли пересчитаются автоматически.

## Источники

- https://getmoonshine.app/en/docs/4.x/advanced/package-development
- https://getmoonshine.app/en/docs/4.x/fields/index
- https://getmoonshine.app/en/docs/4.x/page/index
- https://moonshine-laravel.com/docs (RU)
- Кодовая база: modules/moonshine-media-manager (src/MediaManagerServiceProvider.php, src/Fields/MediaManagerPicker.php, src/Controllers/MediaManagerController.php)
- Кодовая база: modules/moonshine-image-editor (src/ImageEditorServiceProvider.php — registry integration)
