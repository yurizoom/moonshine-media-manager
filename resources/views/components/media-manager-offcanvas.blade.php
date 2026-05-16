@props([
    'urls' => [],
])

<x-moonshine::off-canvas
    name="media-manager"
    title="{{ __('moonshine-media-manager::media-manager.title') }}"
    :wide="true"
>
    <div x-data="mmBrowser({{ Js::from($urls) }})">

        @include('moonshine-media-manager::partials.browser-toolbar')

        {{-- Selected files bar --}}
        <div x-show="$store.mm.hasSelection && !loading" x-cloak class="mb-4">
            <div class="text-sm font-medium mb-2 text-gray-500">{{ __('moonshine-media-manager::media-manager.selected_files') }}</div>
            <div class="mm-selected-bar">
                <template x-for="(file, idx) in $store.mm.selected" :key="file.path">
                    <div :style="{ cursor: selectedDragIdx === idx ? 'grabbing' : 'grab', opacity: selectedDragIdx === idx ? 0.4 : 1 }"
                         class="mm-selected-item"
                         draggable="true"
                         @dragstart="dragSelectedStart(idx, $event)"
                         @dragover.prevent=""
                         @drop="dropSelectedTo(idx)"
                         @dragend="dragSelectedEnd()"
                    >
                        <div class="mm-selected-actions">
                            <button type="button"
                                    @click.prevent="navigateToFile(file.path)"
                                    class="mm-selected-btn mm-selected-btn--navigate"
                                    title="{{ __('moonshine-media-manager::media-manager.go_to_folder') }}"
                            >
                                <x-moonshine::icon icon="folder" class="mm-selected-btn--icon"/>
                            </button>
                            <button type="button"
                                    @click.prevent="$store.mm.selected.splice(idx, 1)"
                                    class="mm-selected-btn mm-selected-btn--remove"
                                    title="{{ __('moonshine-media-manager::media-manager.remove') }}"
                            >×</button>
                        </div>
                        <template x-if="isImageUrl(file.url) && !brokenSelectedPaths.includes(file.path)">
                            <div class="mm-preview">
                                <img :src="file.url"
                                     :alt="file.path.split('/').pop()"
                                     @@error="if(!brokenSelectedPaths.includes(file.path)) brokenSelectedPaths.push(file.path)"
                                >
                            </div>
                        </template>
                        <template x-if="isImageUrl(file.url) && brokenSelectedPaths.includes(file.path)">
                            <div class="mm-preview mm-preview--broken">
                                @include('moonshine-media-manager::partials.icon-broken')
                            </div>
                        </template>
                        <template x-if="!isImageUrl(file.url) && !brokenSelectedPaths.includes(file.path)">
                            <div class="mm-preview mm-preview--document">
                                <x-moonshine::icon icon="document" class="mm-preview--document-icon"/>
                            </div>
                        </template>
                        <template x-if="!isImageUrl(file.url) && brokenSelectedPaths.includes(file.path)">
                            <div class="mm-preview mm-preview--broken">
                                @include('moonshine-media-manager::partials.icon-broken')
                            </div>
                        </template>
                        <span class="mm-selected-name" x-text="file.path.split('/').pop()"></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- Always visible action buttons --}}
        <div class="mm-confirm-bar">
            <x-moonshine::form.button @click.prevent="$store.mm.confirm()" class="btn-primary btn-sm">
                {{ __('moonshine-media-manager::media-manager.save') }}
            </x-moonshine::form.button>
            <x-moonshine::form.button @click.prevent="$store.mm.close()" class="btn-secondary btn-sm">
                {{ __('moonshine-media-manager::media-manager.close') }}
            </x-moonshine::form.button>
        </div>

        @include('moonshine-media-manager::partials.browser-breadcrumbs')

        @include('moonshine-media-manager::partials.browser-loading')

        @include('moonshine-media-manager::partials.browser-table', ['showCheckboxes' => true])

        @include('moonshine-media-manager::partials.browser-list', ['showCheckboxes' => true, 'showSelection' => true, 'idPrefix' => 'oc-file-'])

        @include('moonshine-media-manager::partials.browser-empty-state')

        @include('moonshine-media-manager::partials.browser-modals')

    </div>
</x-moonshine::off-canvas>
