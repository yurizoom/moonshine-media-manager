<x-moonshine::box xmlns:x-moonshine="http://www.w3.org/1999/html"
                  class="flex justify-between"
>
    <div class="flex">
        <x-moonshine::link-button href="#"
                                  icon="heroicons.outline.arrow-path"
                                  title="Refresh"
        ></x-moonshine::link-button>
        <x-moonshine::link-button href="#"
                                  icon="heroicons.outline.trash"
                                  title="Delete"
        ></x-moonshine::link-button>
        <button class="btn">
            <x-moonshine::icon icon="heroicons.outline.cloud-arrow-up"/>
            {{ __('moonshine-media-manager::media-manager.upload') }}
            <form action="{{ $url['upload'] }}"
                  method="post"
                  class="file-upload-form"
                  enctype="multipart/form-data"
            >
                <input type="file" name="files[]" class="hidden file-upload" multiple/>
                <input type="hidden" name="dir" value="{{ $url['path'] }}"/>
                <form>
        </button>

        <x-moonshine::modal title="New folder">
            <x-moonshine::form raw>
                <x-moonshine::form.input name="name" value=""/>
                <x-moonshine::form.input type="hidden" name="dir" value="{{ $url['path'] }}"/>
                <div>
                    <x-moonshine::form.button @click.prevent="toggleModal">Close</x-moonshine::form.button>
                    <x-moonshine::form.button class="btn-primary">Submit</x-moonshine::form.button>
                </div>
            </x-moonshine::form>
            <x-slot name="outerHtml">
                <x-moonshine::link-button @click.prevent="toggleModal" icon="heroicons.outline.folder">
                    {{ __('moonshine-media-manager::media-manager.new_folder') }}
                </x-moonshine::link-button>
            </x-slot>
        </x-moonshine::modal>

        <x-moonshine::link-button
            href="{{ url()->query(url()->current(), ['path' => $url['path'], 'view' => 'table']) }}"
            icon="heroicons.outline.list-bullet"
        ></x-moonshine::link-button>
        <x-moonshine::link-button
            href="{{ url()->query(url()->current(), ['path' => $url['path'], 'view' => 'list']) }}"
            icon="heroicons.outline.squares-2x2"
        ></x-moonshine::link-button>
    </div>

    @include('moonshine-media-manager::snippets.quick_jump')
</x-moonshine::box>
