<div>
    <form method="GET" action="{{ url()->current() }}" class="flex">
        <x-moonshine::form.input
            name="path"
            placeholder="Path"
            value="{{ '/'.trim($path, '/') }}"
        />
        <x-moonshine::form.input
            name="view"
            type="hidden"
            placeholder="Path"
            value="{{ $view }}"
        />
        <x-moonshine::link-button
            icon="heroicons.outline.arrow-small-right"
        ></x-moonshine::link-button>
    </form>
</div>
