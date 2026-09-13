<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract as MoonShineRequest;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;
use YuriZoom\MoonShineMediaManager\MediaManager;

final class MediaManagerController extends MoonShineController
{
    private function authorizeAction(): void
    {
        $ability = config('moonshine.media_manager.ability');

        if ($ability) {
            Gate::authorize($ability);
        }
    }

    /**
     * List files and directories of the requested path.
     */
    public function index(MoonShineRequest $request): JsonResponse
    {
        return $this->handle('list', function () use ($request): array {
            $path = $this->stringInput($request->get('path', '/'));
            $view = $this->stringInput($request->get('view', config('moonshine.media_manager.default_view', 'table')));
            $types = $this->stringList($request->get('types'));
            $extensions = $this->stringList($request->get('extensions'));

            $manager = new MediaManager($path, $view !== '' ? $view : null);

            $files = $manager->ls();

            if ($types !== [] || $extensions !== []) {
                $files = $this->filterFiles($files, $types, $extensions);
            }

            return [
                'status' => true,
                'files' => $files,
                'navigation' => $manager->navigation(),
                'urls' => $manager->urls(),
                'path' => $path,
                'view' => $view,
            ];
        });
    }

    /**
     * Download a file as a streamed response.
     */
    public function download(MoonShineRequest $request): JsonResponse|StreamedResponse
    {
        return $this->handle('download', fn (): StreamedResponse => (new MediaManager($this->stringInput($request->get('file', '/'))))->download());
    }

    /**
     * Upload one or more files into the requested directory.
     */
    public function upload(MoonShineRequest $request): JsonResponse
    {
        return $this->handle('upload', function () use ($request): array {
            $manager = new MediaManager($this->stringInput($request->get('dir', '/')));

            $uploaded = $request->file('files', []);

            if (! is_array($uploaded)) {
                $uploaded = [$uploaded];
            }

            $manager->upload(
                array_values(array_filter($uploaded)),
                $this->stringList($request->get('extensions')),
                $this->stringList($request->get('types')),
            );

            return [
                'status' => true,
                'message' => __('moonshine-media-manager::media-manager.uploaded_successfully'),
            ];
        });
    }

    /**
     * Delete files/directories by paths (supports bulk).
     */
    public function delete(MoonShineRequest $request): JsonResponse
    {
        return $this->handle('delete', function () use ($request): array {
            $files = $this->stringList($request->get('files'));

            if ($files === []) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.select_file')
                );
            }

            (new MediaManager)->delete($files);

            return [
                'status' => true,
                'message' => __('moonshine-media-manager::media-manager.deleted_successfully'),
            ];
        });
    }

    /**
     * Move/rename a file or directory to a new path.
     */
    public function move(MoonShineRequest $request): JsonResponse
    {
        return $this->handle('move', function () use ($request): array {
            (new MediaManager($this->stringInput($request->get('path', '/'))))
                ->move($this->stringInput($request->get('new', '/')));

            return [
                'status' => true,
                'message' => __('moonshine-media-manager::media-manager.moved_successfully'),
            ];
        });
    }

    /**
     * Create a new folder inside the requested directory.
     */
    public function newFolder(MoonShineRequest $request): JsonResponse
    {
        return $this->handle('new-folder', function () use ($request): array {
            (new MediaManager($this->stringInput($request->get('dir', '/'))))
                ->newFolder($this->stringInput($request->get('name'), ''));

            return [
                'status' => true,
                'message' => __('moonshine-media-manager::media-manager.folder_created_successfully'),
            ];
        });
    }

    /**
     * Replace an existing file with a newly uploaded one (same path, same URL).
     */
    public function replace(MoonShineRequest $request): JsonResponse
    {
        return $this->handle('replace', function () use ($request): array {
            $path = $this->stringInput($request->get('path', '/'));
            $file = $request->file('file');

            if (! $file) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.select_file')
                );
            }

            (new MediaManager('/'))->replace($path, $file);

            return [
                'status' => true,
                'message' => __('moonshine-media-manager::media-manager.replaced_successfully'),
            ];
        });
    }

    /**
     * Execute an action inside the unified error boundary so every failure
     * (including MediaManager constructor exceptions) becomes a JSON response
     * instead of an HTML error page.
     *
     * @template T
     * @param  callable(): T  $operation
     * @return T|JsonResponse
     */
    private function handle(string $action, callable $operation): mixed
    {
        try {
            // Inside the boundary so a denied ability becomes a JSON 403
            // with the package contract instead of Laravel's default page.
            $this->authorizeAction();

            $result = $operation();

            Log::debug('[MediaManagerController] action completed', [
                'action' => $action,
            ]);

            if ($result instanceof JsonResponse || $result instanceof StreamedResponse) {
                return $result;
            }

            return response()->json($result);
        } catch (Throwable $e) {
            $status = $this->errorStatus($e);

            if ($status === 403) {
                Log::warning('[MediaManagerController] action denied', [
                    'action' => $action,
                ]);
            }

            return $this->errorResponse($e, $status);
        }
    }

    /**
     * Map an exception to the response status: authorization failures are
     * 403, framework HTTP exceptions keep their client-error status, the
     * rest — 400.
     */
    private function errorStatus(Throwable $e): int
    {
        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof HttpException && $e->getStatusCode() >= 400 && $e->getStatusCode() < 500) {
            return $e->getStatusCode();
        }

        return 400;
    }

    /**
     * Normalize a scalar request value to a string. Non-scalar values
     * (null after ConvertEmptyStringsToNull, arrays, objects) fall back
     * to the given default — '/' for path-like fields, '' for names.
     */
    private function stringInput(mixed $value, string $default = '/'): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Normalize a list request value (array or scalar) to a list of
     * non-empty strings, dropping anything with an unexpected type.
     *
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];

        $strings = [];
        foreach ($items as $item) {
            if (is_scalar($item) && (string) $item !== '') {
                $strings[] = (string) $item;
            } elseif ($item !== null && ! is_scalar($item)) {
                Log::warning('[MediaManagerController] dropped non-scalar list item', [
                    'type' => get_debug_type($item),
                ]);
            }
        }

        return $strings;
    }

    /**
     * Apply picker type/extension filters to the file list.
     * Directories always pass so navigation keeps working.
     *
     * @param  array<int, array<string, mixed>>  $files
     * @param  list<string>  $types
     * @param  list<string>  $extensions
     * @return array<int, array<string, mixed>>
     */
    private function filterFiles(array $files, array $types, array $extensions): array
    {
        $typesLower = array_map('strtolower', $types);
        $extensionsLower = array_map('strtolower', $extensions);

        $filtered = array_filter($files, static function (array $file) use ($typesLower, $extensionsLower): bool {
            if ($file['isDir']) {
                return true;
            }

            if ($typesLower !== [] && ! in_array($file['type'] ?? '', $typesLower, true)) {
                return false;
            }

            if ($extensionsLower !== []) {
                $ext = strtolower(pathinfo((string) $file['path'], PATHINFO_EXTENSION));

                if ($ext === '' || ! in_array($ext, $extensionsLower, true)) {
                    return false;
                }
            }

            return true;
        });

        return array_values($filtered);
    }

    private function errorResponse(Throwable $e, int $status = 400): JsonResponse
    {
        if (! $e instanceof MediaManagerException) {
            report($e);
        }

        Log::debug('[MediaManagerController] action failed', [
            'exception' => $e::class,
            'domain' => $e instanceof MediaManagerException,
        ]);

        // Domain exceptions carry localized, user-safe messages and must stay
        // visible in production; auth failures get a dedicated message;
        // unexpected errors are reported and masked.
        $message = match (true) {
            $e instanceof MediaManagerException => $e->getMessage(),
            $status === 403 => __('moonshine-media-manager::media-manager.error.action_denied'),
            app()->isLocal() => $e->getMessage(),
            default => __('moonshine-media-manager::media-manager.error.operation_failed'),
        };

        return response()->json([
            'status' => false,
            'message' => $message,
        ], $status);
    }
}
