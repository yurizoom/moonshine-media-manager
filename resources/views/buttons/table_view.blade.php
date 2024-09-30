<x-moonshine::link-button
        href="{{ \YuriZoom\MoonShineMediaManager\Hellpers\URLGenerator::query(url()->current(), ['path' => $path, 'view' => 'table']) }}"
    icon="heroicons.outline.list-bullet"
></x-moonshine::link-button>
