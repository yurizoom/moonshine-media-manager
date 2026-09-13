<?php

declare(strict_types=1);

namespace Tests;

use MoonShine\Laravel\Models\MoonshineUser;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use YuriZoom\MoonShineMediaManager\MediaManagerServiceProvider;

abstract class TestCase extends TestbenchTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \MoonShine\Laravel\Providers\MoonShineServiceProvider::class,
            MediaManagerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Dedicated local disk so tests never touch the real storage tree.
        $app['config']->set('filesystems.disks.media-test', [
            'driver' => 'local',
            'root' => storage_path('app/media-test'),
            'throw' => false,
        ]);

        $app['config']->set('moonshine.media_manager.disk', 'media-test');
        $app['config']->set('moonshine.media_manager.allowed_ext', 'jpg,jpeg,png,gif,webp,svg,pdf,txt');
        $app['config']->set('moonshine.media_manager.max_file_size', 1024 * 1024);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true])->run();

        $this->resetTestDisk();

        $this->signIn();
    }

    /**
     * The test disk lives on the real filesystem and survives between runs,
     * so wipe it to keep every test hermetic.
     */
    protected function resetTestDisk(): void
    {
        $root = storage_path('app/media-test');

        if (is_dir($root)) {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }

        \Illuminate\Support\Facades\File::ensureDirectoryExists($root);
    }

    protected function signIn(): void
    {
        $user = MoonshineUser::query()->first() ?? MoonshineUser::query()->create([
            'name' => 'Media Tester',
            'email' => 'media@test.local',
            'password' => 'secret',
        ]);

        $this->actingAs($user, 'moonshine');
    }

    protected function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return $this->app['filesystem']->disk('media-test');
    }
}
