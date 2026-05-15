# MoonShine Media Manager v4.0

Полный рефакторинг файлового менеджера под MoonShine 4.x с переходом на SPA-архитектуру на Alpine.js.

## Что изменилось

**Архитектура: от Blade-компонентов к Alpine.js**

Весь интерфейс переписан с нуля. Вместо 12 кнопок-компонентов, 5 view-классов и 7 blade-частей — единый JS-файл (~680 строк) с тремя Alpine-компонентами:

- `Alpine.store('mm')` — singleton-стейт (открытие/закрытие, выделение, конфиг)
- `Alpine.data('mmBrowser')` — файловый браузер (навигация, CRUD, загрузка)
- `Alpine.data('mmPicker')` — привязка к полю формы, превью, drag-drop

Все операции (загрузка, удаление, переименование, создание папок) — AJAX через `fetch()`, без перезагрузки страницы.

**Удалено 25 файлов, добавлено 4:**

| Было (удалено) | Стало |
|----------------|-------|
| 12 кнопок-компонентов (`Buttons/*`) | inline Alpine-действия в blade |
| 5 view-компонентов (`Components/*`) | `MediaManagerOffCanvas` + `MediaManagerPicker` |
| 7 blade-частей (`buttons/*`, `list.blade.php`, `table.blade.php`, ...) | 3 blade-шаблона (offcanvas, picker, manager) |
| Full-page reload на каждое действие | AJAX-запросы через fetch |

## Новые возможности

- **`MediaManagerPicker`** — поле для выбора файлов из менеджера прямо в форме
  - `allowedTypes()` — фильтр по типу (`image`, `pdf`, `video`, `audio`, `word`, `code`, `zip`, `txt`, `ppt`)
  - `allowedExtensions()` — фильтр по конкретным расширениям
  - `multiple()` — множественный выбор
  - Drag-and-drop reorder (перетаскивание для изменения порядка)
  - Проверка существования файлов (broken state с иконкой предупреждения)
  - Превью изображений (клик → MoonShine img-popup модалка)
  - Иконки с расширением для не-изображений (PDF, DOC, XLS и т.д.)

- **`MediaManagerOffCanvas`** — глобальный offcanvas-компонент
  - Рендерится один раз в layout, используется всеми picker-полями
  - Не дублируется на standalone-странице `/media` (модалки с уникальными префиксами)

- **Интеграция с Layouts и Json** — picker работает внутри `Layouts::make()` и `Json::make()`

- **Навигация** — подсветка файла при переходе из picker, breadcrumbs, быстрый переход по пути

- **Два вида** — таблица и сетка (CSS Grid), переключение без перезагрузки

## ⚠️ Breaking Changes

1. **Требуется подключить JS и OffCanvas в layout** — без этого picker-поля не работают. Старый подход (только страница `/media`) работает, но picker требует нового setup.

2. **Удалены классы кнопок** — `MediaManagerDeleteButton`, `MediaManagerUploadButton` и все остальные `Buttons/*` больше не существуют. Все действия теперь через Alpine.

3. **Удалены view-компоненты** — `MediaManagerComponent`, `MediaManagerItem`, `MediaManagerView`, `MediaManagerQuickJump` заменены на `mmBrowser` Alpine-компонент.

4. **Удалены blade-части** — `list.blade.php`, `table.blade.php`, `preview.blade.php`, `quick_jump.blade.php`, `buttons/*` — всё встроено в `manager.blade.php` и `media-manager-offcanvas.blade.php`.

5. **`MediaManager::upload()`** теперь бросает `RuntimeException` вместо тихого возврата `false`.

6. **Расширение `allowed_ext`** — если в конфиге были ограничения, нужно обновить список для поддержки новых типов файлов.

## Миграция с v2

**1. Опубликуйте JS-ассет:**

```bash
php artisan vendor:publish --tag=media-manager-assets
```

**2. Подключите в layout** (`MoonShineLayout.php`):

```php
use MoonShine\AssetManager\Js;
use YuriZoom\MoonShineMediaManager\Components\MediaManagerOffCanvas;

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
```

**3. Обновите `allowed_ext`** в `config/moonshine.php`:

```php
'allowed_ext' => 'jpg,jpeg,png,gif,webp,avif,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,mp3,mp4,wav,avi,mov',
```

**4. Замените удалённые компоненты** в коде, если использовали напрямую:

- Кнопки → не нужны, действия встроены в менеджер
- `MediaManagerComponent` → `MediaManagerOffCanvas`
- Для выбора файлов в формах → `MediaManagerPicker`

## Файловая структура v4

```
resources/
  js/media-manager.js                          ← ядро (store + browser + picker)
  views/
    manager.blade.php                           ← standalone страница /media
    components/
      media-manager-offcanvas.blade.php         ← offcanvas + модалки
    fields/
      media-manager-picker.blade.php            ← picker-поле
src/
  MediaManager.php                              ← backend (ls, upload, delete, move)
  MediaManagerServiceProvider.php               ← регистрация
  Controllers/MediaManagerController.php        ← AJAX endpoints
  Pages/MediaManagerPage.php                    ← страница /media
  Components/
    MediaManagerOffCanvas.php                   ← offcanvas компонент
  Fields/
    MediaManagerPicker.php                      ← поле для форм
resources/lang/{en,ru}/                         ← переводы
config/media-manager.php                        ← конфиг по умолчанию
```
