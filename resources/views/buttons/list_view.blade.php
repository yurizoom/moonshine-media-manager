<x-moonshine::link-button
        href="{{ \YuriZoom\MoonShineMediaManager\Helpers\URLGenerator::query(url()->current(), ['path' => $path, 'view' => 'list']) }}"
>
    <x-moonshine::icon icon="squares-2x2"/>
</x-moonshine::link-button>
