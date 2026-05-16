<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Fields;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Storage;
use MoonShine\UI\Components\Thumbnails;
use MoonShine\UI\Fields\Field;

class MediaManagerPicker extends Field
{
    protected string $view = 'moonshine-media-manager::fields.media-manager-picker';

    protected bool $isMultiple = false;

    protected array $allowedTypes = [];

    protected array $allowedExtensions = [];

    public function multiple(bool $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    public function allowedTypes(array $types): static
    {
        $this->allowedTypes = $types;

        return $this;
    }

    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    public function allowedExtensions(array $extensions): static
    {
        $this->allowedExtensions = $extensions;

        return $this;
    }

    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    protected function viewData(): array
    {
        return [
            'pickerConfig' => [
                'value' => $this->toValue(),
                'multiple' => $this->isMultiple,
                'allowedTypes' => $this->allowedTypes,
                'allowedExtensions' => $this->allowedExtensions,
                'baseUrl' => $this->getStorageUrl(),
            ],
        ];
    }

    protected function resolvePreview(): Renderable|string
    {
        $value = $this->toValue();

        if (blank($value)) {
            return '';
        }

        $baseUrl = $this->getStorageUrl();

        if ($this->isMultiple) {
            $paths = is_array($value) ? $value : json_decode((string) $value, true) ?? [];
            $urls = array_map(static fn (string $p): string => $baseUrl . '/' . $p, $paths);

            return Thumbnails::make($urls)->render();
        }

        return Thumbnails::make($baseUrl . '/' . $value)->render();
    }

    protected function resolveRawValue(): mixed
    {
        return $this->toValue();
    }

    private function getStorageUrl(): string
    {
        $disk = config('moonshine.media_manager.disk', 'public');

        try {
            return rtrim(Storage::disk($disk)->url(''), '/');
        } catch (\Throwable) {
            return url('/storage');
        }
    }
}
