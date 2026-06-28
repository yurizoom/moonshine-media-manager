<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\MenuManager\MenuManagerContract;
use MoonShine\MenuManager\MenuItem;
use YuriZoom\MoonShineMediaManager\Contracts\MediaManagerRegistryInterface;
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;
use YuriZoom\MoonShineMediaManager\Support\MediaManagerRegistry;

final class MediaManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->loadTranslationsFrom(
            __DIR__.'/../resources/lang',
            'moonshine-media-manager'
        );

        $this->app->singleton(
            MediaManagerRegistryInterface::class,
            MediaManagerRegistry::class,
        );
    }

    /**
     * Resolve configuration from three sources with precedence:
     *   package defaults  <  legacy (config/moonshine.php → media_manager)  <  standalone (config/media-manager.php)
     *
     * Both namespaces are kept in sync so existing reads via
     * config('moonshine.media_manager.*') keep working, while users get a
     * dedicated config/media-manager.php file that survives republishing of
     * the main moonshine.php config.
     */
    private function registerConfig(): void
    {
        $defaults = require __DIR__.'/../config/media-manager.php';

        $legacy = (array) config('moonshine.media_manager', []);
        $standalone = (array) config('media-manager', []);

        $resolved = array_merge($defaults, $legacy, $standalone);

        config([
            'media-manager' => $resolved,
            'moonshine.media_manager' => $resolved,
        ]);
    }

    public function boot(CoreContract $core, MenuManagerContract $menu): void
    {
        $this->loadViewsFrom(
            __DIR__.'/../resources/views',
            'moonshine-media-manager'
        );

        $this->loadRoutesFrom(
            __DIR__.'/../routes/moonshine.php'
        );

        $this->publishes([
            __DIR__.'/../config/media-manager.php' => config_path('media-manager.php'),
        ], 'moonshine-media-manager-config');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/moonshine-media-manager'),
        ], 'moonshine-media-manager-lang');

        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/media-manager'),
        ], ['moonshine-media-manager-assets']);

        $this->registerViewComposer();

        $core->pages([
            MediaManagerPage::class,
        ]);

        if (config('moonshine.media_manager.auto_menu')) {
            $menu->add([
                MenuItem::make(MediaManagerPage::class),
            ]);
        }

        $this->autoPublishAssets();
    }

    private function autoPublishAssets(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $target = public_path('vendor/media-manager');
        $source = realpath(__DIR__.'/../dist');

        if (! $source || is_link($target)) {
            return;
        }

        if (! is_dir($target) || filemtime($source.'/media-manager.js') > @filemtime($target.'/media-manager.js')) {
            \Illuminate\Support\Facades\File::ensureDirectoryExists($target);
            \Illuminate\Support\Facades\File::copyDirectory($source, $target);
        }
    }

    private function registerViewComposer(): void
    {
        $views = [
            'moonshine-media-manager::manager',
            'moonshine-media-manager::components.media-manager-offcanvas',
            'moonshine-media-manager::partials.browser-toolbar',
            'moonshine-media-manager::partials.browser-table',
            'moonshine-media-manager::partials.browser-list',
        ];

        View::composer($views, function (\Illuminate\View\View $view): void {
            $registry = $this->app->make(MediaManagerRegistryInterface::class);

            $view->with('mmFileActions', $registry->getFileActions());
            $view->with('mmToolbarActions', $registry->getToolbarActions());
        });
    }
}