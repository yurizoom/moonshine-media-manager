<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileDeleted;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileReplaced;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileUploaded;

class MediaManagerEventsTest extends TestCase
{
    public function test_upload_dispatches_event_with_final_path(): void
    {
        Event::fake();

        $this->post(route('moonshine.media.manager.upload'), [
            'dir' => '/',
            'files' => [UploadedFile::fake()->create('photo.png', 10, 'image/png')],
        ])->assertOk();

        Event::assertDispatched(
            MediaManagerFileUploaded::class,
            fn (MediaManagerFileUploaded $e): bool => $e->path === '/photo.png' && $e->disk === 'media-test',
        );
    }

    public function test_upload_duplicate_dispatches_renamed_path(): void
    {
        Event::fake();
        $this->disk()->put('photo.png', 'original');

        $this->post(route('moonshine.media.manager.upload'), [
            'dir' => '/',
            'files' => [UploadedFile::fake()->create('photo.png', 10, 'image/png')],
        ])->assertOk();

        Event::assertDispatched(
            MediaManagerFileUploaded::class,
            fn (MediaManagerFileUploaded $e): bool => $e->path === '/photo-1.png',
        );
    }

    public function test_replace_dispatches_event(): void
    {
        Event::fake();
        $this->disk()->put('doc.txt', 'before');

        $this->post(route('moonshine.media.manager.replace'), [
            'path' => 'doc.txt',
            'file' => UploadedFile::fake()->createWithContent('doc.txt', 'after'),
        ])->assertOk();

        Event::assertDispatched(
            MediaManagerFileReplaced::class,
            fn (MediaManagerFileReplaced $e): bool => $e->path === '/doc.txt' && $e->disk === 'media-test',
        );
    }

    public function test_delete_file_dispatches_event(): void
    {
        Event::fake();
        $this->disk()->put('gone.txt', 'x');

        $this->post(route('moonshine.media.manager.delete'), ['files' => ['gone.txt']])->assertOk();

        Event::assertDispatched(
            MediaManagerFileDeleted::class,
            fn (MediaManagerFileDeleted $e): bool => $e->path === '/gone.txt',
        );
    }

    public function test_delete_folder_dispatches_event_per_inner_file(): void
    {
        Event::fake();
        $this->disk()->makeDirectory('docs');
        $this->disk()->put('docs/a.txt', 'a');
        $this->disk()->put('docs/b.txt', 'b');

        $this->post(route('moonshine.media.manager.delete'), ['files' => ['docs']])->assertOk();

        Event::assertDispatchedTimes(MediaManagerFileDeleted::class, 2);
        Event::assertDispatched(
            MediaManagerFileDeleted::class,
            fn (MediaManagerFileDeleted $e): bool => $e->path === '/docs/a.txt',
        );
        Event::assertDispatched(
            MediaManagerFileDeleted::class,
            fn (MediaManagerFileDeleted $e): bool => $e->path === '/docs/b.txt',
        );

        $this->assertFalse($this->disk()->exists('docs/a.txt'));
        $this->assertFalse($this->disk()->exists('docs/b.txt'));
    }
}
