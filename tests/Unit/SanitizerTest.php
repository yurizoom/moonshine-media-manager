<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;
use YuriZoom\MoonShineMediaManager\Helpers\URLGenerator;
use YuriZoom\MoonShineMediaManager\Support\MediaFormatter;
use YuriZoom\MoonShineMediaManager\Support\MediaSecurity;
use YuriZoom\MoonShineMediaManager\Support\MediaValidator;
use YuriZoom\MoonShineMediaManager\Support\SvgSanitizer;

class SanitizerTest extends TestCase
{
    public function test_sanitize_path_strips_traversal(): void
    {
        $this->assertSame('/etc/passwd', URLGenerator::sanitizePath('../../etc/passwd'));
        $this->assertSame('/safe/dir', URLGenerator::sanitizePath('\\..\\safe\\.\\dir'));
        $this->assertSame('/', URLGenerator::sanitizePath('/../../../'));
        $this->assertSame('/a/b', URLGenerator::sanitizePath('a//b'));
    }

    public function test_sanitize_file_name_transliterates_unicode(): void
    {
        // portable-ascii maps ч → c; transliteration is lossy but readable.
        $this->assertSame('Otcet.jpg', URLGenerator::sanitizeFileName('Отчёт.jpg'));
    }

    public function test_sanitize_file_name_blocks_dangerous_extensions_in_any_segment(): void
    {
        $this->expectException(MediaManagerException::class);

        URLGenerator::sanitizeFileName('shell.php.jpg');
    }

    public function test_sanitize_file_name_rejects_htaccess_name(): void
    {
        $this->expectException(MediaManagerException::class);

        URLGenerator::sanitizeFileName('.htaccess');
    }

    public function test_sanitize_file_name_falls_back_for_dot_only_names(): void
    {
        $this->assertSame('file', URLGenerator::sanitizeFileName('...'));
    }

    public function test_validator_rejects_client_extension_spoofing(): void
    {
        // Laravel's fake() derives MIME from the file name, so a real
        // UploadedFile over actual PHP content is required to exercise
        // the content sniffing path.
        $tmp = tempnam(sys_get_temp_dir(), 'mm-spoof');
        file_put_contents($tmp, '<?php echo "x"; ?>');
        $spoofed = new UploadedFile($tmp, 'payload.jpg', 'image/jpeg', null, true);

        $validator = MediaValidator::fromConfig(['jpg', 'png'], 1024 * 1024);

        try {
            $validator->validateUploadedFile($spoofed, 'jpg');
            $this->fail('Expected MediaManagerException for content/extension contradiction');
        } catch (MediaManagerException $e) {
            $this->assertStringContainsString('MIME', $e->getMessage());
        }
    }

    public function test_validator_denies_all_when_whitelist_empty(): void
    {
        $file = UploadedFile::fake()->create('image.png', 10, 'image/png');

        $validator = MediaValidator::fromConfig([], 1024 * 1024);

        $this->expectException(MediaManagerException::class);

        $validator->validateUploadedFile($file, 'png');
    }

    public function test_validator_rejects_non_whitelisted_extension(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 10, 'application/pdf');

        $validator = MediaValidator::fromConfig(['jpg'], 1024 * 1024);

        try {
            $validator->validateUploadedFile($file, 'pdf');
            $this->fail('Expected MediaManagerException for non-whitelisted extension');
        } catch (MediaManagerException $e) {
            $this->assertStringContainsString('pdf', $e->getMessage());
        }
    }

    public function test_validator_accepts_consistent_upload(): void
    {
        $file = UploadedFile::fake()->create('photo.png', 10, 'image/png');

        MediaValidator::fromConfig(['png'], 1024 * 1024)->validateUploadedFile($file, 'png');

        $this->assertTrue(true);
    }

    public function test_validator_enforces_size_limit(): void
    {
        $file = UploadedFile::fake()->create('photo.png', 100, 'image/png');

        $validator = MediaValidator::fromConfig(['png'], 10);

        $this->expectException(MediaManagerException::class);

        $validator->validateUploadedFile($file, 'png');
    }

    public function test_blocked_paths_come_from_config(): void
    {
        config(['moonshine.media_manager.blocked_paths' => ['private', 'Logs']]);

        $this->expectException(MediaManagerException::class);

        MediaSecurity::assertNotBlockedPath('/private/anything');
    }

    public function test_format_bytes_handles_extremes(): void
    {
        $this->assertSame('0 B', MediaFormatter::formatBytes(-5));
        $this->assertSame('0 B', MediaFormatter::formatBytes(0));
        $this->assertSame('1 KB', MediaFormatter::formatBytes(1024));
        $this->assertSame('1 MB', MediaFormatter::formatBytes(1048576));
        $this->assertSame('1 GB', MediaFormatter::formatBytes(1073741824));
        // No crash beyond PB — the M6 regression.
        $this->assertStringEndsWith('PB', MediaFormatter::formatBytes((int) 1e18));
    }

    public function test_svg_sanitizer_strips_scripts_and_handlers(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            .'<script>alert(2)</script>'
            .'<rect width="10" height="10" fill="red"/>'
            .'<a href="javascript:alert(3)"><text>x</text></a>'
            .'</svg>';

        $clean = SvgSanitizer::sanitize($svg);

        $this->assertStringNotContainsStringIgnoringCase('script', $clean);
        $this->assertStringNotContainsStringIgnoringCase('onload', $clean);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $clean);
        $this->assertStringContainsStringIgnoringCase('rect', $clean);
    }

    public function test_svg_sanitizer_rejects_malformed_xml(): void
    {
        $this->expectException(MediaManagerException::class);

        SvgSanitizer::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><broken>');
    }
}
