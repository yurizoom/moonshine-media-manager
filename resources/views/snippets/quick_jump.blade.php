<div class="flex">
    <x-moonshine::form.input
        name="path"
        placeholder="Title"
        value="{{ '/'.trim($url['path'], '/') }}"
    />
    <x-moonshine::link-button
        href="{{ url( parameters: ['path' => $url['path'], 'view' => 'list'])->current() }}"
        icon="heroicons.outline.arrow-small-right"
    ></x-moonshine::link-button>
</div>
