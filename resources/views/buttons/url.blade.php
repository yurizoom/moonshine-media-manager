<x-moonshine::modal :closeOutside="false" title="Url">
    <div class="flex flex-col gap-4">
        <div>
            {{ $url }}
        </div>
        <div>
            <x-moonshine::form.button @click.prevent="toggleModal">Close
            </x-moonshine::form.button>
        </div>
    </div>
    <x-slot name="outerHtml">
        <x-moonshine::link-button @click.prevent="toggleModal"
                                  icon="heroicons.outline.globe-alt"
                                  class="{{ $class }}"
        >
            {{ $label }}
        </x-moonshine::link-button>
    </x-slot>
</x-moonshine::modal>
