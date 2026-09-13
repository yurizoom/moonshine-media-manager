<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MediaManagerEndpointsTest extends TestCase
{
    public function test_list_returns_json_contract(): void
    {
        $this->disk()->put('hello.txt', 'hi');

        $response = $this->get(route('moonshine.media.manager.index'), ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $response->assertJsonStructure(['status', 'files', 'navigation', 'urls', 'path', 'view']);
    }

    public function test_blocked_path_returns_400_json_not_500(): void
    {
        $response = $this->getJson(route('moonshine.media.manager.index').'?path=/framework');

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
    }

    public function test_missing_path_returns_400_json(): void
    {
        $response = $this->getJson(route('moonshine.media.manager.index').'?path=/nope');

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
    }

    public function test_delete_traversal_to_root_is_rejected(): void
    {
        $this->disk()->put('precious.txt', 'do not lose me');

        $response = $this->post(route('moonshine.media.manager.delete'), ['files' => ['..']]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
        $this->assertTrue($this->disk()->exists('precious.txt'));
    }

    public function test_delete_with_junk_input_returns_json_not_error(): void
    {
        // Scalar input is normalized to a single path; deleting a missing
        // path is an idempotent no-op — but it must stay a JSON response,
        // never a TypeError-fueled 500 (the C4 regression).
        $response = $this->post(route('moonshine.media.manager.delete'), ['files' => 123]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
    }

    public function test_delete_removes_file(): void
    {
        $this->disk()->put('gone.txt', 'bye');

        $response = $this->post(route('moonshine.media.manager.delete'), ['files' => ['gone.txt']]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $this->assertFalse($this->disk()->exists('gone.txt'));
    }

    public function test_upload_stores_valid_file(): void
    {
        $response = $this->post(route('moonshine.media.manager.upload'), [
            'dir' => '/',
            'files' => [UploadedFile::fake()->create('photo.png', 10, 'image/png')],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $this->assertTrue($this->disk()->exists('photo.png'));
    }

    public function test_upload_rejects_non_whitelisted_extension(): void
    {
        $response = $this->post(route('moonshine.media.manager.upload'), [
            'dir' => '/',
            'files' => [UploadedFile::fake()->create('payload.exe', 5, 'application/x-msdownload')],
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
        $this->assertFalse($this->disk()->exists('payload.exe'));
    }

    public function test_upload_sanitizes_svg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script>'
            .'<rect width="10" height="10"/></svg>';

        $response = $this->post(route('moonshine.media.manager.upload'), [
            'dir' => '/',
            'files' => [UploadedFile::fake()->createWithContent('icon.svg', $svg)],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);

        $stored = (string) $this->disk()->get('icon.svg');
        $this->assertStringNotContainsStringIgnoringCase('script', $stored);
        $this->assertStringContainsStringIgnoringCase('rect', $stored);
    }

    public function test_new_folder_creates_and_rejects_duplicate(): void
    {
        $first = $this->post(route('moonshine.media.manager.new.folder'), ['dir' => '/', 'name' => 'docs']);
        $first->assertOk();
        $first->assertJsonPath('status', true);
        $this->assertTrue($this->disk()->directoryExists('docs'));

        $second = $this->post(route('moonshine.media.manager.new.folder'), ['dir' => '/', 'name' => 'docs']);
        $second->assertStatus(400);
        $second->assertJsonPath('status', false);
    }

    public function test_new_folder_empty_or_dots_only_name_never_creates_file_folder(): void
    {
        // Regression: '' / '...' used to fall through sanitizeFileName's 'file'
        // fallback and reported 'Папка "file" уже существует'.
        foreach (['', '   ', '...', ' . . '] as $name) {
            $response = $this->post(route('moonshine.media.manager.new.folder'), ['dir' => '/', 'name' => $name]);

            $response->assertStatus(400);
            $response->assertJsonPath('status', false);
            $response->assertJsonPath('message', 'Please enter a name');
        }

        $this->assertFalse($this->disk()->directoryExists('file'));
    }

    public function test_move_renames_file(): void
    {
        $this->disk()->put('old-name.txt', 'data');

        $response = $this->post(route('moonshine.media.manager.move'), ['path' => 'old-name.txt', 'new' => 'new-name.txt']);

        $response->assertOk();
        $this->assertFalse($this->disk()->exists('old-name.txt'));
        $this->assertTrue($this->disk()->exists('new-name.txt'));
    }

    public function test_move_rejects_same_path(): void
    {
        $this->disk()->put('same.txt', 'x');

        $response = $this->post(route('moonshine.media.manager.move'), ['path' => 'same.txt', 'new' => 'same.txt']);

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
    }

    public function test_replace_updates_content_in_place(): void
    {
        $this->disk()->put('doc.txt', 'before');

        $response = $this->post(route('moonshine.media.manager.replace'), [
            'path' => 'doc.txt',
            'file' => UploadedFile::fake()->createWithContent('doc.txt', 'after'),
        ]);

        $response->assertOk();
        $this->assertSame('after', $this->disk()->get('doc.txt'));
    }

    public function test_replace_missing_target_returns_400(): void
    {
        $response = $this->post(route('moonshine.media.manager.replace'), [
            'path' => 'ghost.txt',
            'file' => UploadedFile::fake()->create('ghost.txt', 5, 'text/plain'),
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
    }

    public function test_download_streams_file(): void
    {
        $this->disk()->put('report.txt', 'hello');

        $response = $this->get(route('moonshine.media.manager.download', ['file' => 'report.txt']));

        $response->assertOk();
    }

    public function test_download_traversal_returns_400(): void
    {
        $response = $this->get(route('moonshine.media.manager.download', ['file' => '../']));

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
    }

    public function test_download_blocked_path_returns_400(): void
    {
        $response = $this->get(route('moonshine.media.manager.download', ['file' => 'framework/config.php']));

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
    }

    public function test_denied_ability_returns_403_json(): void
    {
        config()->set('moonshine.media_manager.ability', 'media-manager-access');
        \Illuminate\Support\Facades\Gate::define('media-manager-access', fn ($user): bool => false);

        $response = $this->getJson(route('moonshine.media.manager.index'));

        $response->assertStatus(403);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Access denied');
    }

    public function test_move_renames_directory_with_contents(): void
    {
        $this->disk()->makeDirectory('gallery');
        $this->disk()->put('gallery/a.txt', 'a');
        $this->disk()->put('gallery/b.txt', 'b');

        $response = $this->post(route('moonshine.media.manager.move'), ['path' => 'gallery', 'new' => 'gallery-2']);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $this->assertTrue($this->disk()->exists('gallery-2/a.txt'));
        $this->assertTrue($this->disk()->exists('gallery-2/b.txt'));
        $this->assertFalse($this->disk()->exists('gallery'));
    }

    public function test_upload_respects_picker_extension_filter(): void
    {
        $response = $this->post(route('moonshine.media.manager.upload'), [
            'dir' => '/',
            'files' => [UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg')],
            'extensions' => ['png'],
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
        $this->assertFalse($this->disk()->exists('photo.jpg'));
    }

    public function test_upload_respects_picker_type_filter(): void
    {
        $response = $this->post(route('moonshine.media.manager.upload'), [
            'dir' => '/',
            'files' => [UploadedFile::fake()->create('notes.txt', 5, 'text/plain')],
            'types' => ['image'],
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
        $this->assertFalse($this->disk()->exists('notes.txt'));
    }

    public function test_upload_with_matching_picker_filters_succeeds(): void
    {
        $response = $this->post(route('moonshine.media.manager.upload'), [
            'dir' => '/',
            'files' => [UploadedFile::fake()->create('photo.png', 10, 'image/png')],
            'types' => ['image'],
            'extensions' => ['png'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $this->assertTrue($this->disk()->exists('photo.png'));
    }
}
