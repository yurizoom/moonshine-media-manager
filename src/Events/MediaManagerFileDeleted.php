<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class MediaManagerFileDeleted
{
    use Dispatchable;

    public function __construct(
        public string $path,
        public string $disk,
    ) {}
}
