<x-moonshine::link-button
        href="{{ \YuriZoom\MoonShineMediaManager\Helpers\URLGenerator::query(url()->current(), ['path' => $path, 'view' => 'table']) }}"
>
    <x-moonshine::icon icon="list-bullet"/>
</x-moonshine::link-button>
